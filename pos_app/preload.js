/**
 * preload.js
 * 
 * Secure Preload Script for Cafe Print Agent Desktop Window.
 * Exposes a sandboxed IPC API to the Renderer Process via ContextBridge.
 */

const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('electronAPI', {
  // Telemetry & Status
  getStatus: () => ipcRenderer.invoke('get-status'),
  getConfig: () => ipcRenderer.invoke('get-config'),
  saveConfig: (newConfig) => ipcRenderer.invoke('save-config', newConfig),

  // Hardware Printers
  getPrinters: (forceScan = false) => ipcRenderer.invoke('get-printers', forceScan),
  assignPrinterRole: (printer, role) => ipcRenderer.invoke('assign-printer-role', { printer, role }),
  testPrint: (role) => ipcRenderer.invoke('test-print', role),

  // Connectivity
  reconnectReverb: () => ipcRenderer.invoke('reconnect-reverb'),
  triggerHeartbeat: () => ipcRenderer.invoke('trigger-heartbeat'),

  // Logs & Diagnostics
  getLogs: (limit = 100) => ipcRenderer.invoke('get-logs', limit),
  clearLogs: () => ipcRenderer.invoke('clear-logs'),
  openLogsFolder: () => ipcRenderer.invoke('open-logs-folder'),

  // Windows Integration
  getAutoStart: () => ipcRenderer.invoke('get-autostart'),
  setAutoStart: (enable) => ipcRenderer.invoke('set-autostart', enable),
  minimizeToTray: () => ipcRenderer.invoke('minimize-to-tray'),
  quitApp: () => ipcRenderer.invoke('quit-app'),
  restartAgent: () => ipcRenderer.invoke('restart-agent'),

  // Live Real-Time Events
  onLogEvent: (callback) => {
    const handler = (_event, data) => callback(data);
    ipcRenderer.on('log-event', handler);
    return () => ipcRenderer.removeListener('log-event', handler);
  },
  onStatusUpdate: (callback) => {
    const handler = (_event, data) => callback(data);
    ipcRenderer.on('status-update', handler);
    return () => ipcRenderer.removeListener('status-update', handler);
  }
});
