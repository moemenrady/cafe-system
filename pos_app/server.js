/**
 * server.js
 * 
 * Main Entry Point for Desktop POS Print Agent.
 * 
 * Features:
 * - Embedded Express Dashboard on http://localhost:3210
 * - Multi-Printer Dynamic Routing & Probing
 * - Visual Receipt Template Management & Factory Resets
 * - Proactive Laravel Heartbeat with Active Roles Extraction
 * - Over-The-Air (OTA) Background Auto-Updater
 */

const express = require('express');
const cors = require('cors');
const path = require('path');
const { exec } = require('child_process');

const configManager = require('./configManager');
const printerDispatcher = require('./printerDispatcher');
const reverbClient = require('./reverbClient');
const autoUpdater = require('./updater');
const printerDiscovery = require('./printerDiscovery');
const printHistory = require('./printHistory');

const app = express();
const startTime = Date.now();

// State tracking
let lastHeartbeatStatus = {
  success: false,
  last_sent_at: null,
  http_code: null,
  status_reported: 'offline',
  active_roles: [],
  error: null
};

let cachedPrinterHealth = {};
let cachedActiveRoles = [];
let heartbeatIntervalTimer = null;

app.use(cors());
app.use(express.json());

const PUBLIC_DIR = path.join(__dirname, 'public');
app.use(express.static(PUBLIC_DIR));

// ==========================================
// REST API Endpoints
// ==========================================

/**
 * GET /api/status
 */
app.get('/api/status', async (req, res) => {
  const config = configManager.getConfig();
  const wsStatus = reverbClient.getStatus();
  const updaterStatus = autoUpdater.getLastCheck();

  res.json({
    success: true,
    agent: {
      name: 'POS Print Agent',
      version: config.app_version || '1.0.0',
      uptime_seconds: Math.floor((Date.now() - startTime) / 1000),
      device_uuid: config.device_uuid,
      is_configured: configManager.isConfigured(),
      config_path: configManager.getPath()
    },
    websocket: wsStatus,
    backend_sync: lastHeartbeatStatus,
    printers: cachedPrinterHealth,
    active_roles: cachedActiveRoles,
    updater: updaterStatus
  });
});

/**
 * GET /api/config
 */
app.get('/api/config', (req, res) => {
  res.json({
    success: true,
    config: configManager.getConfig()
  });
});

/**
 * POST /api/config
 */
app.post('/api/config', async (req, res) => {
  try {
    const updated = configManager.updateConfig(req.body);
    console.log('[Server] تم تحديث الإعدادات عبر لوحة التحكم.');

    await runPrinterProbesAndHeartbeat();
    reverbClient.connect(updated);
    autoUpdater.init(updated);

    res.json({
      success: true,
      message: 'تم حفظ الإعدادات وتطبيقها بنجاح يا باشا.',
      config: updated
    });
  } catch (err) {
    console.error(`[Server] خطأ أثناء تحديث الإعدادات: ${err.message}`);
    res.status(400).json({ success: false, message: err.message });
  }
});

/**
 * POST /api/printers
 * Updates the printers fleet array.
 */
app.post('/api/printers', async (req, res) => {
  try {
    const { printers } = req.body;
    if (!Array.isArray(printers)) {
      return res.status(400).json({ success: false, message: 'قائمة الطابعات غير صالحة.' });
    }

    configManager.savePrinters(printers);
    await runPrinterProbesAndHeartbeat();

    res.json({
      success: true,
      message: 'تم تحديث قائمة الطابعات بنجاح.',
      printers: configManager.getConfig().printers
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * GET /api/printers/discover
 * Auto-discovers thermal printers on local ports (9100-9106) and local subnets,
 * as well as installed OS spooler printers.
 */
app.get('/api/printers/discover', async (req, res) => {
  try {
    const forceScan = req.query.refresh === 'true';
    let printers;
    if (forceScan) {
      printers = await printerDiscovery.discoverAllPrinters();
    } else {
      printers = printerDiscovery.getCached();
      if (!printers || printers.length === 0) {
        printers = await printerDiscovery.discoverAllPrinters();
      }
    }
    res.json({
      success: true,
      count: printers.length,
      last_scan: printerDiscovery.lastScanTime,
      printers
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * GET /api/templates
 */
app.get('/api/templates', (req, res) => {
  const config = configManager.getConfig();
  res.json({
    success: true,
    templates: config.templates || configManager.getDefaultTemplates()
  });
});

/**
 * POST /api/templates
 * Updates a specific template ('cashier' or 'barista').
 */
app.post('/api/templates', (req, res) => {
  try {
    const { type, template } = req.body;
    if (!type || !template) {
      return res.status(400).json({ success: false, message: 'نوع القالب والبيانات مطلوبة.' });
    }

    const updated = configManager.updateTemplate(type, template);
    res.json({
      success: true,
      message: `تم حفظ تصميم إيصال ${type === 'cashier' ? 'الكاشير' : 'الباريستا'} بنجاح.`,
      template: updated
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * POST /api/templates/reset
 * Restores a template to factory default.
 */
app.post('/api/templates/reset', (req, res) => {
  try {
    const { type } = req.body;
    if (!type) {
      return res.status(400).json({ success: false, message: 'حدد نوع القالب المطلوب استعادته.' });
    }

    const restored = configManager.resetTemplateToDefault(type);
    res.json({
      success: true,
      message: `تمت استعادة التصميم الافتراضي لقالب ${type === 'cashier' ? 'الكاشير' : 'الباريستا'}.`,
      template: restored
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * POST /api/printers/test-template
 * Prints a sample receipt using the customized template.
 */
app.post('/api/printers/test-template', async (req, res) => {
  try {
    const { type, template } = req.body;
    const config = configManager.getConfig();
    const targetType = type || 'cashier';

    // Build synthetic sample job
    const sampleJob = {
      uuid: `sample-${Date.now()}`,
      type: targetType,
      printer_identifier: targetType,
      payload: {
        order_number: "99",
        date: new Date().toLocaleDateString('ar-EG'),
        time: new Date().toLocaleTimeString('ar-EG'),
        table: "7",
        cashier_name: "أحمد",
        type: "صالة",
        items: [
          { quantity: 2, name: "كابتشينو كراميل", price: 35.00, notes: "حليب شوفان - سكر خفيف" },
          { quantity: 1, name: "مولتن كيك شوكولاتة", price: 45.00, notes: "ساخنة مع آيس كريم" }
        ],
        subtotal: 115.00,
        tax: 16.10,
        total: 131.10,
        payment_method: "فيزا"
      }
    };

    const dispatchResult = await printerDispatcher.dispatchPrintJob(sampleJob, config, template);
    printHistory.recordJob({ job: sampleJob, dispatchResult, status: 'printed', source: 'test_receipt' });

    res.json({
      success: true,
      message: `تم إرسال ورقة التجربة بنجاح لطابعات (${targetType === 'cashier' ? 'الكاشير' : 'الباريستا'}).`,
      results: dispatchResult.results
    });
  } catch (err) {
    res.status(500).json({
      success: false,
      message: `فشلت طباعة ورقة التجربة: ${err.message}`
    });
  }
});

/**
 * POST /api/printers/test-connection
 * Quick socket probe of specified host & port.
 */
app.post('/api/printers/test-connection', async (req, res) => {
  try {
    const { host, port } = req.body;
    if (!host || !port) {
      return res.status(400).json({ success: false, message: 'عنوان IP والمنفذ مطلوبان.' });
    }

    const result = await printerDispatcher.probePrinter(host, port, 3000);
    res.json({ success: true, host, port, result });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * GET /api/updater/check
 */
app.get('/api/updater/check', async (req, res) => {
  try {
    const checkResult = await autoUpdater.checkForUpdates();
    res.json({ success: true, result: checkResult });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * POST /api/jobs/manual-trigger
 */
app.post('/api/jobs/manual-trigger', async (req, res) => {
  try {
    const jobPayload = req.body.job || req.body;
    await reverbClient.handlePrintJobEvent({ job: jobPayload });
    res.json({ success: true, message: 'تم استلام أمر الطباعة بنجاح.' });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * GET /api/jobs/history
 * Returns paginated & filtered print job history.
 */
app.get('/api/jobs/history', (req, res) => {
  try {
    const jobs = printHistory.getJobs(req.query);
    const stats = printHistory.getStats();
    res.json({
      success: true,
      count: jobs.length,
      stats,
      jobs
    });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

/**
 * POST /api/jobs/reprint
 * 1-Click re-dispatches a previously recorded job.
 */
app.post('/api/jobs/reprint', async (req, res) => {
  try {
    const { jobId } = req.body;
    if (!jobId) {
      return res.status(400).json({ success: false, message: 'معرف الطلب مطلوب للإعادة.' });
    }

    const record = printHistory.getJobById(jobId);
    if (!record || !record.raw_job) {
      return res.status(404).json({ success: false, message: 'لم يتم العثور على أمر الطباعة في السجل.' });
    }

    const config = configManager.getConfig();
    const reprintJob = {
      ...record.raw_job,
      uuid: `reprint-${Date.now()}`
    };

    const dispatchResult = await printerDispatcher.dispatchPrintJob(reprintJob, config);

    printHistory.recordJob({
      job: reprintJob,
      dispatchResult,
      status: 'printed',
      source: 'reprint'
    });

    res.json({
      success: true,
      message: `تمت إعادة طباعة الطلب #${record.order_number} بنجاح.`,
      results: dispatchResult.results
    });
  } catch (err) {
    res.status(500).json({ success: false, message: `فشلت إعادة الطباعة: ${err.message}` });
  }
});

/**
 * POST /api/jobs/clear-history
 */
app.post('/api/jobs/clear-history', (req, res) => {
  try {
    printHistory.clearHistory();
    res.json({ success: true, message: 'تم تفريغ سجل الطباعة بنجاح.' });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.get('*', (req, res) => {
  res.sendFile(path.join(PUBLIC_DIR, 'index.html'));
});

// ==========================================
// Health Heartbeat & Laravel Sync Flow
// ==========================================

async function runPrinterProbesAndHeartbeat() {
  const config = configManager.getConfig();
  const printersList = config.printers || [];

  // Probe all configured printers
  const probeData = await printerDispatcher.probeAllPrinters(printersList);
  cachedPrinterHealth = probeData.healthMap;
  cachedActiveRoles = probeData.activeRoles;

  // Build Heartbeat Payload matching Laravel expectation
  const heartbeatPayload = {
    device_uuid: config.device_uuid,
    status: probeData.overallStatus,
    active_roles: probeData.activeRoles,
    printers: probeData.healthMap
  };

  // Dispatch HTTP POST to Laravel
  if (config.laravel_backend_url) {
    const backendUrl = config.laravel_backend_url.replace(/\/+$/, '');
    const endpoint = `${backendUrl}/api/pos/device-heartbeat`;

    try {
      const resp = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Device-UUID': config.device_uuid
        },
        body: JSON.stringify(heartbeatPayload),
        signal: AbortSignal.timeout(5000)
      });

      lastHeartbeatStatus = {
        success: resp.ok,
        last_sent_at: new Date().toISOString(),
        http_code: resp.status,
        status_reported: probeData.overallStatus,
        active_roles: probeData.activeRoles,
        error: resp.ok ? null : `HTTP ${resp.status}`
      };

      if (resp.ok) {
        console.log(`[Server] نبضات النظام متزامنة مع السيرفر: الحالة="${probeData.overallStatus}" | الأدوار النشطة=[${probeData.activeRoles.join(', ')}]`);
      } else {
        console.warn(`[Server] رفض السيرفر تقرير النبضات (${endpoint}): HTTP ${resp.status}`);
      }
    } catch (err) {
      lastHeartbeatStatus = {
        success: false,
        last_sent_at: new Date().toISOString(),
        http_code: null,
        status_reported: probeData.overallStatus,
        active_roles: probeData.activeRoles,
        error: err.message
      };
      console.warn(`[Server] تعذر إرسال نبضات النظام إلى السيرفر: ${err.message}`);
    }
  }
}

function openBrowser(url) {
  const platform = process.platform;
  let command = '';
  if (platform === 'darwin') command = `open "${url}"`;
  else if (platform === 'win32') command = `start "" "${url}"`;
  else command = `xdg-open "${url}"`;

  exec(command, (err) => {
    if (!err) console.log(`[Server] تم فتح المتصفح التلقائي على: ${url}`);
  });
}

// ==========================================
// Bootstrap
// ==========================================
const config = configManager.getConfig();
const PORT = config.server?.port || 3210;

const server = app.listen(PORT, async () => {
  console.log('====================================================');
  console.log('  نظام إدارة الكافيه - برنامج الطباعة المكتبي v1.0.0  ');
  console.log('====================================================');
  console.log(`[Server] لوحة التحكم متاحة على: http://localhost:${PORT}`);
  console.log(`[Server] معرف المحطة: "${config.device_uuid}"`);

  await runPrinterProbesAndHeartbeat();
  reverbClient.connect(config);
  autoUpdater.init(config);
  printerDiscovery.discoverAllPrinters().catch(() => {});

  const intervalMs = config.heartbeat_interval_ms || 30000;
  heartbeatIntervalTimer = setInterval(runPrinterProbesAndHeartbeat, intervalMs);

  if (!configManager.isConfigured()) {
    console.log('[Server] أول تشغيل للبرنامج. جاري فتح لوحة التحكم في المتصفح...');
    setTimeout(() => openBrowser(`http://localhost:${PORT}`), 1200);
  }
});

function handleShutdown(signal) {
  console.log(`\n[Server] استلام إشارة الإغلاق ${signal}...`);
  if (heartbeatIntervalTimer) clearInterval(heartbeatIntervalTimer);
  reverbClient.disconnect();
  server.close(() => {
    console.log('[Server] تم إيقاف الخدمة بسلام.');
    process.exit(0);
  });
  setTimeout(() => process.exit(1), 3000);
}

process.on('SIGINT', () => handleShutdown('SIGINT'));
process.on('SIGTERM', () => handleShutdown('SIGTERM'));

module.exports = { app, server };
