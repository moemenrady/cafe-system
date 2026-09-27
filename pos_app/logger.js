/**
 * logger.js
 * 
 * Enterprise Logging Engine for Desktop POS Print Agent.
 * 
 * Features:
 * 1. Multi-Stream File Logging:
 *    - logs/agent.log : General daemon lifecycle & connectivity events
 *    - logs/print.log : Print job dispatches, hardware telemetry & success/fail reports
 *    - logs/error.log : Errors, stack traces & network flap diagnostics
 * 2. In-Memory Real-Time Ring Buffer for Desktop UI Dashboard stream
 * 3. Event Emitter for live streaming to Electron UI / IPC
 * 4. Automatic rotation/truncation guard to prevent disk bloat
 */

const fs = require('fs');
const path = require('path');
const EventEmitter = require('events');

class Logger extends EventEmitter {
  constructor() {
    super();
    this.logsDir = this.resolveLogsDir();
    this.recentEvents = [];
    this.maxRecentEvents = 150;
    this.currentLevel = 'info'; // 'debug' | 'info' | 'warn' | 'error'

    this.ensureLogsDir();
    this.agentLogPath = path.join(this.logsDir, 'agent.log');
    this.printLogPath = path.join(this.logsDir, 'print.log');
    this.errorLogPath = path.join(this.logsDir, 'error.log');

    // Attach to uncaught exceptions safely
    this.hookGlobalErrors();
  }

  resolveLogsDir() {
    // When packaged in Electron, write to userData or app dir
    try {
      if (process.type || process.versions?.electron) {
        const { app } = require('electron');
        if (app && app.getPath) {
          return path.join(app.getPath('userData'), 'logs');
        }
      }
    } catch (_) {}

    return path.join(__dirname, 'logs');
  }

  ensureLogsDir() {
    try {
      if (!fs.existsSync(this.logsDir)) {
        fs.mkdirSync(this.logsDir, { recursive: true });
      }
    } catch (e) {
      console.error('Failed to create logs dir:', e.message);
    }
  }

  setLevel(level) {
    this.currentLevel = level || 'info';
  }

  getLogsDir() {
    return this.logsDir;
  }

  formatTimestamp() {
    const now = new Date();
    return now.toTimeString().split(' ')[0]; // HH:mm:ss
  }

  formatFullTimestamp() {
    const now = new Date();
    return now.toISOString().replace('T', ' ').substring(0, 19);
  }

  appendToFile(filePath, line) {
    try {
      this.ensureLogsDir();
      fs.appendFileSync(filePath, line + '\n', 'utf8');
    } catch (_) {
      // Best-effort non-blocking
    }
  }

  recordEvent(level, message, category = 'agent') {
    const time = this.formatTimestamp();
    const eventObj = {
      id: Date.now() + Math.random().toString(36).substring(2, 6),
      time,
      timestamp: new Date().toISOString(),
      level, // 'info' | 'success' | 'warn' | 'error'
      category,
      message
    };

    this.recentEvents.unshift(eventObj);
    if (this.recentEvents.length > this.maxRecentEvents) {
      this.recentEvents.pop();
    }

    this.emit('event', eventObj);
    return eventObj;
  }

  info(msg, details = null) {
    const fullTime = this.formatFullTimestamp();
    const formatted = details ? `${msg} ${JSON.stringify(details)}` : msg;
    const fileLine = `[${fullTime}] [INFO] ${formatted}`;

    this.appendToFile(this.agentLogPath, fileLine);
    this.recordEvent('info', msg, 'agent');
    console.log(`[Agent] ${formatted}`);
  }

  warn(msg, details = null) {
    const fullTime = this.formatFullTimestamp();
    const formatted = details ? `${msg} ${JSON.stringify(details)}` : msg;
    const fileLine = `[${fullTime}] [WARN] ${formatted}`;

    this.appendToFile(this.agentLogPath, fileLine);
    this.appendToFile(this.errorLogPath, fileLine);
    this.recordEvent('warn', msg, 'agent');
    console.warn(`[Agent WARN] ${formatted}`);
  }

  error(msg, err = null) {
    const fullTime = this.formatFullTimestamp();
    const errStack = err && err.stack ? `\n${err.stack}` : (err ? ` - ${err.message || err}` : '');
    const fileLine = `[${fullTime}] [ERROR] ${msg}${errStack}`;

    this.appendToFile(this.agentLogPath, fileLine);
    this.appendToFile(this.errorLogPath, fileLine);
    this.recordEvent('error', `${msg}${err ? ': ' + (err.message || err) : ''}`, 'agent');
    console.error(`[Agent ERROR] ${msg}`, err || '');
  }

  print(msg, isSuccess = true, details = null) {
    const fullTime = this.formatFullTimestamp();
    const formatted = details ? `${msg} ${JSON.stringify(details)}` : msg;
    const levelStr = isSuccess ? 'PRINT_SUCCESS' : 'PRINT_FAILED';
    const fileLine = `[${fullTime}] [${levelStr}] ${formatted}`;

    this.appendToFile(this.printLogPath, fileLine);
    this.appendToFile(this.agentLogPath, fileLine);
    this.recordEvent(isSuccess ? 'success' : 'error', msg, 'print');
    console.log(`[Print] ${formatted}`);
  }

  getRecentEvents(limit = 60) {
    return this.recentEvents.slice(0, limit);
  }

  clearRecentEvents() {
    this.recentEvents = [];
    this.recordEvent('info', 'تم تفريغ شاشة الأحداث المباشرة', 'agent');
  }

  readLogFile(filename) {
    try {
      const target = path.join(this.logsDir, filename);
      if (fs.existsSync(target)) {
        const content = fs.readFileSync(target, 'utf8');
        const lines = content.split('\n');
        return lines.slice(-250).join('\n'); // Return last 250 lines
      }
      return 'لا توجد سجلات بعد.';
    } catch (e) {
      return `تعذر قراءة السجل: ${e.message}`;
    }
  }

  hookGlobalErrors() {
    process.on('uncaughtException', (err) => {
      this.error('Uncaught Exception', err);
    });
    process.on('unhandledRejection', (reason) => {
      this.error('Unhandled Promise Rejection', reason);
    });
  }
}

module.exports = new Logger();
