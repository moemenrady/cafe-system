/**
 * reverbClient.js
 * 
 * Production WebSocket Daemon for Laravel Reverb.
 * 
 * Architectural Highlights:
 * 1. Node.js Polyfill Integration: Injects 'ws' into the global scope to enable
 *    browser-oriented Pusher-JS / Laravel-Echo client libraries inside standalone Node binaries.
 * 2. Exponential Reconnection Backoff with Jitter: Prevents thundering herd problems during network
 *    flaps or Laravel backend restarts.
 * 3. Heartbeat Watchdog & Stale Connection Pruning: Detects half-open TCP connections that fail to
 *    receive websocket frames and forces proactive reconnection.
 * 4. Two-Way Sync Handshake: Automatically reports job status transitions back to Laravel:
 *    - POST /api/print-agent/jobs/{uuid}/processing
 *    - POST /api/print-agent/jobs/{uuid}/complete
 *    - POST /api/print-agent/jobs/{uuid}/failed (with detailed error diagnostics)
 *    Includes 'X-Device-UUID' security and correlation headers on all HTTP sync requests.
 */

const Pusher = require('pusher-js');
const WebSocket = require('ws');
const printerDispatcher = require('./printerDispatcher');
const printHistory = require('./printHistory');

// Polyfill global WebSocket for Pusher-JS in pure Node.js runtime
global.WebSocket = WebSocket;

class ReverbClient {
  constructor() {
    this.echo = null;
    this.pusher = null;
    this.channel = null;
    this.currentConfig = null;
    this.status = 'disconnected'; // 'connected' | 'connecting' | 'reconnecting' | 'disconnected' | 'error'
    this.lastConnectedAt = null;
    this.lastError = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 30;
    this.baseBackoffMs = 1500;
    this.maxBackoffMs = 30000;
    this.watchdogTimer = null;
    this.lastHeartbeatReceivedAt = null;
    this.recentJobs = []; // In-memory ring buffer for UI dashboard activity feed
    this.maxRecentJobs = 50;
    this.processingJobUuids = new Set();
    this.pollingTimer = null;
  }

  /**
   * Initializes or re-initializes connection with new configuration parameters.
   */
  connect(config) {
    this.currentConfig = config;

    // Teardown existing connection if active
    this.disconnect();

    const reverb = config.reverb || {};
    const appKey = reverb.app_key;
    const host = reverb.host || '127.0.0.1';
    const port = Number(reverb.port) || 8080;
    const scheme = reverb.scheme || 'http';
    const isTls = scheme === 'https' || port === 443;
    const deviceUuid = config.device_uuid;

    if (!appKey || !deviceUuid) {
      this.status = 'disconnected';
      this.lastError = 'Missing Reverb App Key or Device UUID';
      console.warn(`[ReverbClient] Cannot connect: ${this.lastError}`);
      return;
    }

    this.status = 'connecting';
    this.lastError = null;
    console.log(`[ReverbClient] Initializing Reverb connection to ws://${host}:${port} (Key: ${appKey.substring(0, 6)}..., Device: ${deviceUuid})`);

    try {
      this.pusher = new Pusher(appKey, {
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: isTls,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        cluster: 'mt1', // Reverb uses cluster as placeholder
        activityTimeout: 30000,
        pongTimeout: 10000
      });

      this.bindPusherEvents(deviceUuid);
      this.startWatchdog();
      this.startPollingWatchdog(2500);
    } catch (err) {
      this.status = 'error';
      this.lastError = err.message;
      console.error(`[ReverbClient] Failed to instantiate Pusher instance: ${err.message}`);
      this.startPollingWatchdog(2500);
      this.scheduleReconnect();
    }
  }

  /**
   * Binds connection lifecycle and channel event handlers.
   */
  bindPusherEvents(deviceUuid) {
    if (!this.pusher) return;

    // Connection state listeners
    this.pusher.connection.bind('connected', () => {
      this.status = 'connected';
      this.lastConnectedAt = new Date().toISOString();
      this.reconnectAttempts = 0;
      this.lastError = null;
      console.log(`[ReverbClient] WebSocket connected successfully to Laravel Reverb (Socket ID: ${this.pusher.connection.socket_id})`);

      // Subscribe to device-specific print agent channel
      const channelName = `print-agent.${deviceUuid}`;
      console.log(`[ReverbClient] Subscribing to channel: "${channelName}"`);
      this.channel = this.pusher.subscribe(channelName);

      this.channel.bind('pusher:subscription_succeeded', () => {
        console.log(`[ReverbClient] Subscribed successfully to "${channelName}". Ready to receive print jobs.`);
      });

      this.channel.bind('pusher:subscription_error', (status) => {
        console.error(`[ReverbClient] Subscription failed for "${channelName}": Status ${status}`);
      });

      // Listen for print job events
      // We bind both '.print.job' (custom broadcastAs) and 'print.job' for universal compatibility
      this.channel.bind('.print.job', (data) => this.handlePrintJobEvent(data));
      this.channel.bind('print.job', (data) => this.handlePrintJobEvent(data));
      this.channel.bind('App\\Events\\PrinterJobCreated', (data) => this.handlePrintJobEvent(data));
    });

    this.pusher.connection.bind('connecting', () => {
      this.status = 'connecting';
      console.log('[ReverbClient] WebSocket connecting...');
    });

    this.pusher.connection.bind('unavailable', () => {
      this.status = 'reconnecting';
      console.warn('[ReverbClient] Reverb server unavailable. Triggering backoff reconnection...');
      this.scheduleReconnect();
    });

    this.pusher.connection.bind('failed', () => {
      this.status = 'error';
      this.lastError = 'WebSocket connection handshake failed';
      console.error('[ReverbClient] WebSocket connection failed.');
      this.scheduleReconnect();
    });

    this.pusher.connection.bind('disconnected', () => {
      if (this.status !== 'error') {
        this.status = 'disconnected';
      }
      console.log('[ReverbClient] WebSocket disconnected.');
    });

    this.pusher.connection.bind('error', (err) => {
      this.lastError = err.error?.data?.message || err.message || 'Unknown WebSocket Error';
      console.error(`[ReverbClient] Pusher Error: ${this.lastError}`);
    });
  }

  /**
   * Processes incoming print job event received over WebSocket.
   */
  async handlePrintJobEvent(eventData) {
    console.log('[ReverbClient] Inbound print job event received:', JSON.stringify(eventData));

    // Handle both direct job payload and nested job structures
    const job = eventData.job || eventData;
    if (!job) {
      console.error('[ReverbClient] Received malformed event payload: missing job object');
      return;
    }

    const jobUuid = job.uuid || `local-${Date.now()}`;

    // Deduplication guard against concurrent WebSocket and poller delivery
    if (this.processingJobUuids.has(jobUuid)) {
      console.log(`[ReverbClient] Job [${jobUuid}] already processed or in progress. Skipping duplicate.`);
      return;
    }
    this.processingJobUuids.add(jobUuid);
    if (this.processingJobUuids.size > 2000) {
      const [oldest] = this.processingJobUuids;
      this.processingJobUuids.delete(oldest);
    }

    const printerIdentifier = (job.printer_identifier || job.type || 'cashier').toLowerCase();

    const jobRecord = {
      uuid: jobUuid,
      type: job.type || 'customer',
      printer_identifier: printerIdentifier,
      received_at: new Date().toISOString(),
      status: 'received',
      error: null
    };
    this.recordJob(jobRecord);

    // 1. Notify Laravel backend that job is now being processed
    await this.notifyBackendJobStatus(jobUuid, 'processing');

    try {
      // 2. Dispatch ESC/POS buffer over TCP socket
      jobRecord.status = 'dispatching';
      const dispatchResult = await printerDispatcher.dispatchPrintJob(job, this.currentConfig);

      jobRecord.status = 'printed';
      jobRecord.completed_at = new Date().toISOString();
      jobRecord.details = dispatchResult.results;
      printHistory.recordJob({ job, dispatchResult, status: 'printed', source: 'reverb' });
      console.log(`[ReverbClient] Print job [${jobUuid}] completed successfully.`);

      // 3. Notify Laravel backend of job completion
      await this.notifyBackendJobStatus(jobUuid, 'complete');
    } catch (err) {
      jobRecord.status = 'failed';
      jobRecord.error = err.message;
      jobRecord.failed_at = new Date().toISOString();
      printHistory.recordJob({ job, dispatchResult: null, status: 'failed', source: 'reverb', error: err.message });
      console.error(`[ReverbClient] Print job [${jobUuid}] failed: ${err.message}`);

      // 4. Notify Laravel backend of failure with error diagnostic
      await this.notifyBackendJobStatus(jobUuid, 'failed', err.message);
    }
  }

  /**
   * Sends HTTP status synchronization updates to the Laravel backend.
   */
  async notifyBackendJobStatus(jobUuid, status, errorMessage = null) {
    if (!this.currentConfig || !this.currentConfig.laravel_backend_url) {
      return;
    }

    // Don't attempt HTTP sync for synthetic local test jobs
    if (jobUuid.startsWith('local-') || jobUuid.startsWith('test-')) {
      return;
    }

    const backendUrl = this.currentConfig.laravel_backend_url.replace(/\/+$/, '');
    const endpoint = `${backendUrl}/api/print-agent/jobs/${encodeURIComponent(jobUuid)}/${status}`;
    const deviceUuid = this.currentConfig.device_uuid;

    try {
      const bodyPayload = errorMessage ? JSON.stringify({ error_message: errorMessage }) : undefined;
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Device-UUID': deviceUuid
        },
        body: bodyPayload,
        signal: AbortSignal.timeout(5000)
      });

      if (!response.ok) {
        console.warn(`[ReverbClient] Failed to notify Laravel of job status (${status}): HTTP ${response.status}`);
      } else {
        console.log(`[ReverbClient] Notified Laravel of job [${jobUuid}] status -> "${status}"`);
      }
    } catch (err) {
      console.warn(`[ReverbClient] Network error syncing job status to Laravel: ${err.message}`);
    }
  }

  /**
   * Recovers any pending or unacknowledged print jobs from Laravel on boot/reconnect.
   */
  async syncPendingJobs() {
    if (!this.currentConfig || !this.currentConfig.laravel_backend_url || !this.currentConfig.device_uuid) {
      return;
    }

    const backendUrl = this.currentConfig.laravel_backend_url.replace(/\/+$/, '');
    const endpoint = `${backendUrl}/api/print-agent/jobs`;
    const deviceUuid = this.currentConfig.device_uuid;

    try {
      const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Device-UUID': deviceUuid
        },
        signal: AbortSignal.timeout(5000)
      });

      if (response.ok) {
        const json = await response.json();
        const pendingJobs = json.data || [];
        for (const job of pendingJobs) {
          if (job.uuid && !this.processingJobUuids.has(job.uuid)) {
            console.log(`[ReverbClient Watchdog] Found new pending job [${job.uuid}] (${job.type}) for order #${job.payload?.order_number || job.order_id || 'N/A'}`);
            await this.handlePrintJobEvent({ job });
          }
        }
      }
    } catch (err) {
      // Quietly ignore transient network failures during polling
    }
  }

  /**
   * Continuous high-frequency watchdog poller (dual-mode backup to WebSockets).
   */
  startPollingWatchdog(intervalMs = 2500) {
    this.stopPollingWatchdog();
    console.log(`[ReverbClient] Starting real-time pending jobs watchdog poller (every ${intervalMs}ms)...`);
    this.syncPendingJobs().catch(() => {});
    this.pollingTimer = setInterval(() => {
      this.syncPendingJobs().catch(() => {});
    }, intervalMs);
  }

  stopPollingWatchdog() {
    if (this.pollingTimer) {
      clearInterval(this.pollingTimer);
      this.pollingTimer = null;
    }
  }

  /**
   * Exponential backoff scheduler with randomized jitter.
   */
  scheduleReconnect() {
    if (this.reconnectTimer) return;
    this.reconnectAttempts++;

    // Calculate exponential delay: base * 2^(attempts-1) + jitter
    const delay = Math.min(
      this.maxBackoffMs,
      Math.round((this.baseBackoffMs * Math.pow(1.5, this.reconnectAttempts - 1)) + (Math.random() * 1000))
    );

    console.log(`[ReverbClient] Will attempt reconnection in ${(delay / 1000).toFixed(1)}s (Attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
    this.status = 'reconnecting';

    this.reconnectTimer = setTimeout(() => {
      this.reconnectTimer = null;
      if (this.currentConfig) {
        this.connect(this.currentConfig);
      }
    }, delay);
  }

  /**
   * Heartbeat watchdog to detect silent network drops.
   */
  startWatchdog() {
    this.stopWatchdog();
    this.watchdogTimer = setInterval(() => {
      if (this.pusher && this.pusher.connection) {
        const state = this.pusher.connection.state;
        if (!this.reconnectTimer && (state === 'disconnected' || state === 'failed')) {
          console.warn(`[ReverbClient Watchdog] Detected state "${state}". Triggering reconnection.`);
          this.scheduleReconnect();
        }
      }
    }, 20000);
  }

  stopWatchdog() {
    if (this.watchdogTimer) {
      clearInterval(this.watchdogTimer);
      this.watchdogTimer = null;
    }
  }

  /**
   * Disconnects active Pusher / Reverb socket and clears all timers.
   */
  disconnect() {
    this.stopWatchdog();
    this.stopPollingWatchdog();
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }
    if (this.channel) {
      try {
        this.channel.unbind_all();
        if (this.pusher) {
          this.pusher.unsubscribe(this.channel.name);
        }
      } catch (_) {}
      this.channel = null;
    }
    if (this.pusher) {
      try {
        this.pusher.disconnect();
      } catch (_) {}
      this.pusher = null;
    }
    this.status = 'disconnected';
  }

  recordJob(jobRecord) {
    this.recentJobs.unshift(jobRecord);
    if (this.recentJobs.length > this.maxRecentJobs) {
      this.recentJobs.pop();
    }
  }

  getStatus() {
    return {
      status: this.status,
      connected: this.status === 'connected',
      polling_active: !!this.pollingTimer,
      last_connected_at: this.lastConnectedAt,
      last_error: this.lastError,
      reconnect_attempts: this.reconnectAttempts,
      recent_jobs: this.recentJobs.slice(0, 15)
    };
  }
}

module.exports = new ReverbClient();
