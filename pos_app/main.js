/**
 * main.js
 * 
 * Electron Main Process for Cafe Print Agent Desktop Application.
 * 
 * Key Architecture:
 * 1. Single Instance Guard (prevents port 3210 collisions and duplicate daemons)
 * 2. AppData persistent configuration persistence for Windows installs
 * 3. Windows System Tray with dynamic status and quick diagnostics
 * 4. Background Express API server (3210) & Reverb WebSocket listener
 * 5. Windows Auto-Start (Run on startup with silent background mode)
 */

const { app, BrowserWindow, Tray, Menu, ipcMain, shell, nativeImage } = require('electron');
const path = require('path');
const fs = require('fs');

// Ensure single instance
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
  console.log('[Main] Another instance of Cafe Print Agent is already running. Quitting.');
  app.quit();
  process.exit(0);
}

// Global references
let mainWindow = null;
let tray = null;
let isQuitting = false;

// Determine writable user data path for config and logs in packaged Windows environment
const isPackaged = app.isPackaged;
const userDataDir = app.getPath('userData');
if (!fs.existsSync(userDataDir)) {
  fs.mkdirSync(userDataDir, { recursive: true });
}

const userConfigPath = path.join(userDataDir, 'config.json');
const defaultBundledConfig = path.join(__dirname, 'config.json');

// Ensure config.json exists in AppData
if (!fs.existsSync(userConfigPath) && fs.existsSync(defaultBundledConfig)) {
  try {
    fs.copyFileSync(defaultBundledConfig, userConfigPath);
  } catch (err) {
    console.error('[Main] Failed to copy initial config to AppData:', err);
  }
}

// Point environment variable to persistent config path
process.env.POS_CONFIG_PATH = userConfigPath;

// Load application services
const logger = require('./logger');
const configManager = require('./configManager');
const printerService = require('./printerService');
const reverbClient = require('./reverbClient');
const serverModule = require('./server'); // Starts Express on 3210

logger.info(`تشغيل تطبيق طباعة الكافيه المكتبي (حالة الحزمة: ${isPackaged ? 'Packaged / مثبت' : 'Development'})`);

// Icon paths
const iconPngPath = path.join(__dirname, 'assets', 'icon.png');
const iconIcoPath = path.join(__dirname, 'assets', 'icon.ico');
const appIcon = fs.existsSync(iconPngPath) ? nativeImage.createFromPath(iconPngPath) : null;

/**
 * Creates the primary Desktop Window.
 */
function createMainWindow() {
  const isHiddenStart = process.argv.includes('--hidden') || process.argv.includes('--minimized');

  mainWindow = new BrowserWindow({
    width: 1080,
    height: 760,
    minWidth: 880,
    minHeight: 640,
    title: 'Cafe Print Agent | نظام الطباعة المكتبي',
    icon: appIcon || iconIcoPath,
    show: !isHiddenStart,
    backgroundColor: '#0f172a',
    autoHideMenuBar: true,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      sandbox: false,
      devTools: !isPackaged
    }
  });

  const port = configManager.getConfig().server?.port || 3210;
  const targetUrl = `http://127.0.0.1:${port}`;

  function loadAppUrl() {
    if (!mainWindow || mainWindow.isDestroyed()) return;
    mainWindow.loadURL(targetUrl).catch((err) => {
      logger.info(`[Main] في انتظار تشغيل السيرفر المحلي على ${targetUrl}... (${err.message})`);
      setTimeout(loadAppUrl, 400);
    });
  }

  loadAppUrl();

  mainWindow.once('ready-to-show', () => {
    if (!isHiddenStart) {
      mainWindow.show();
      mainWindow.focus();
    }
  });

  // Intercept window close -> hide to system tray
  mainWindow.on('close', (event) => {
    if (!isQuitting) {
      event.preventDefault();
      mainWindow.hide();
      if (tray) {
        tray.displayBalloon?.({
          title: 'Cafe Print Agent',
          content: 'تم تصغير التطبيق لشريط المهام. خدمة الطباعة مستمرة بالعمل في الخلفية.',
          iconType: 'info'
        });
      }
    }
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

/**
 * Builds and mounts the System Tray icon and context menu.
 */
function createSystemTray() {
  try {
    let trayIcon = null;
    if (process.platform === 'win32' && fs.existsSync(iconIcoPath)) {
      trayIcon = nativeImage.createFromPath(iconIcoPath);
    } else if (appIcon) {
      trayIcon = appIcon.resize({ width: 16, height: 16 });
    } else if (fs.existsSync(iconPngPath)) {
      trayIcon = nativeImage.createFromPath(iconPngPath).resize({ width: 16, height: 16 });
    }
    tray = new Tray(trayIcon || iconIcoPath);
    tray.setToolTip('Cafe Print Agent | نظام الطباعة المكتبي');

    updateTrayMenu();

    tray.on('click', () => {
      if (mainWindow) {
        if (mainWindow.isVisible()) {
          mainWindow.focus();
        } else {
          mainWindow.show();
          mainWindow.focus();
        }
      }
    });

    tray.on('double-click', () => {
      if (mainWindow) {
        mainWindow.show();
        mainWindow.focus();
      }
    });
  } catch (err) {
    logger.error('Failed to create System Tray icon:', err);
  }
}

/**
 * Updates dynamic tray context menu based on live connection states.
 */
function updateTrayMenu() {
  if (!tray) return;

  const wsStatus = reverbClient.getStatus();
  const isWsConnected = wsStatus.status === 'connected';
  const autoStart = app.getLoginItemSettings().openAtLogin;

  const contextMenu = Menu.buildFromTemplate([
    {
      label: 'فتح لوحة التحكم',
      click: () => {
        if (mainWindow) {
          mainWindow.show();
          mainWindow.focus();
        }
      }
    },
    { type: 'separator' },
    {
      label: `حالة Reverb: ${isWsConnected ? 'متصل بنجاح ✓' : 'غير متصل ✕'}`,
      enabled: false
    },
    {
      label: 'إعادة الاتصال بـ Reverb',
      click: () => {
        logger.info('إعادة الاتصال بـ Reverb من شريط المهام...');
        reverbClient.connect(configManager.getConfig());
        updateTrayMenu();
      }
    },
    {
      label: 'فحص الطابعات الآن',
      click: async () => {
        logger.info('فحص الطابعات من شريط المهام...');
        const printers = await printerService.enumeratePrinters(true);
        logger.info(`تم العثور على ${printers.length} طابعة على النظام.`);
      }
    },
    {
      label: 'طباعة تجريبية للكاشير',
      click: async () => {
        try {
          const res = await printerService.testPrint('cashier');
          logger.print(`نتيجة الفحص للكاشير: ${res.success ? 'نجاح' : 'فشل'}`, res.success);
        } catch (e) {
          logger.error('خطأ في الطباعة التجريبية للكاشير:', e);
        }
      }
    },
    {
      label: 'طباعة تجريبية للباريستا / المطبخ',
      click: async () => {
        try {
          const res = await printerService.testPrint('barista');
          logger.print(`نتيجة الفحص للباريستا: ${res.success ? 'نجاح' : 'فشل'}`, res.success);
        } catch (e) {
          logger.error('خطأ في الطباعة التجريبية للباريستا:', e);
        }
      }
    },
    { type: 'separator' },
    {
      label: 'التشغيل التلقائي مع الويندوز',
      type: 'checkbox',
      checked: autoStart,
      click: (item) => {
        app.setLoginItemSettings({
          openAtLogin: item.checked,
          args: ['--hidden']
        });
        logger.info(`تم ${item.checked ? 'تفعيل' : 'تعطيل'} التشغيل التلقائي مع الويندوز`);
      }
    },
    {
      label: 'فتح مجلد السجلات (Logs)',
      click: () => {
        shell.openPath(logger.getLogsDir());
      }
    },
    { type: 'separator' },
    {
      label: 'إعادة تشغيل البرنامج',
      click: () => {
        app.relaunch();
        isQuitting = true;
        app.quit();
      }
    },
    {
      label: 'إغلاق نهائي للتطبيق',
      click: () => {
        isQuitting = true;
        app.quit();
      }
    }
  ]);

  tray.setContextMenu(contextMenu);
}

// Forward real-time logger events to Renderer window
logger.on('event', (logEvent) => {
  if (mainWindow && !mainWindow.isDestroyed()) {
    mainWindow.webContents.send('log-event', logEvent);
  }
});

// Second instance focus
app.on('second-instance', () => {
  if (mainWindow) {
    if (mainWindow.isMinimized()) mainWindow.restore();
    mainWindow.show();
    mainWindow.focus();
  }
});

// App Lifecycle
app.whenReady().then(() => {
  createMainWindow();
  createSystemTray();

  // Register Windows Auto-Start by default in packaged production mode
  if (app.isPackaged) {
    try {
      const loginSettings = app.getLoginItemSettings();
      if (!loginSettings.openAtLogin) {
        app.setLoginItemSettings({
          openAtLogin: true,
          args: ['--hidden']
        });
        logger.info('[Main] تم تسجيل التشغيل التلقائي مع بدء تشغيل الويندوز بنجاح.');
      }
    } catch (e) {
      logger.warn('[Main] تعذر ضبط إعدادات بدء التشغيل التلقائي:', e.message);
    }
  }

  // Periodically update tray state every 10s
  setInterval(() => {
    updateTrayMenu();
  }, 10000);
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createMainWindow();
  }
});

app.on('before-quit', () => {
  isQuitting = true;
});

// ==========================================
// IPC Handlers (Renderer <-> Main)
// ==========================================

ipcMain.handle('get-status', async () => {
  const config = configManager.getConfig();
  const wsStatus = reverbClient.getStatus();
  const statusData = serverModule.getStatusData ? serverModule.getStatusData() : {};
  const printers = await printerService.enumeratePrinters();

  return {
    success: true,
    agent: {
      name: 'Cafe Print Agent',
      version: config.app_version || '1.0.0',
      device_uuid: config.device_uuid,
      port: config.server?.port || 3210,
      config_path: configManager.getPath(),
      logs_path: logger.getLogsDir()
    },
    websocket: wsStatus,
    backend_sync: statusData.lastHeartbeatStatus || {},
    printers: printers,
    configured_printers: config.printers || []
  };
});

ipcMain.handle('get-config', () => {
  return {
    success: true,
    config: configManager.getConfig(),
    path: configManager.getPath()
  };
});

ipcMain.handle('save-config', (_event, newConfig) => {
  try {
    const updated = configManager.saveConfig(newConfig);
    logger.info('تم حفظ وتحديث إعدادات النظام بنجاح');
    // Re-connect Reverb if settings changed
    reverbClient.connect(updated);
    updateTrayMenu();
    return { success: true, config: updated };
  } catch (err) {
    logger.error('فشل حفظ إعدادات النظام:', err);
    return { success: false, error: err.message };
  }
});

ipcMain.handle('get-printers', async (_event, forceScan) => {
  try {
    const list = await printerService.enumeratePrinters(forceScan);
    return { success: true, printers: list };
  } catch (err) {
    return { success: false, error: err.message, printers: [] };
  }
});

ipcMain.handle('assign-printer-role', async (_event, { printer, role }) => {
  try {
    const result = await printerService.assignRole(printer, role);
    logger.info(`تم تعيين الطابعة "${printer.name}" للدور [${role}]`);
    return result;
  } catch (err) {
    logger.error(`فشل تعيين الدور [${role}] للطابعة:`, err);
    return { success: false, error: err.message };
  }
});

ipcMain.handle('test-print', async (_event, role) => {
  try {
    logger.info(`بدء طباعة تجريبية للدور [${role}]...`);
    const result = await printerService.testPrint(role);
    logger.print(`اكتملت الطباعة التجريبية [${role}]: ${result.success ? 'نجاح' : 'فشل'}`, result.success, result);
    return result;
  } catch (err) {
    logger.error(`فشل الطباعة التجريبية للدور [${role}]:`, err);
    return { success: false, error: err.message };
  }
});

ipcMain.handle('reconnect-reverb', () => {
  try {
    logger.info('إعادة الاتصال بـ Reverb بناءً على طلب المستخدم...');
    reverbClient.connect(configManager.getConfig());
    updateTrayMenu();
    return { success: true };
  } catch (err) {
    return { success: false, error: err.message };
  }
});

ipcMain.handle('trigger-heartbeat', async () => {
  try {
    if (serverModule.runPrinterProbesAndHeartbeat) {
      await serverModule.runPrinterProbesAndHeartbeat();
    }
    return { success: true };
  } catch (err) {
    return { success: false, error: err.message };
  }
});

ipcMain.handle('get-logs', (_event, limit) => {
  return {
    success: true,
    logs: logger.getRecentEvents(limit || 100)
  };
});

ipcMain.handle('clear-logs', () => {
  logger.clearRecentEvents();
  return { success: true };
});

ipcMain.handle('open-logs-folder', () => {
  shell.openPath(logger.getLogsDir());
  return { success: true };
});

ipcMain.handle('get-autostart', () => {
  return {
    success: true,
    enabled: app.getLoginItemSettings().openAtLogin
  };
});

ipcMain.handle('set-autostart', (_event, enable) => {
  app.setLoginItemSettings({
    openAtLogin: !!enable,
    args: ['--hidden']
  });
  logger.info(`تم ضبط التشغيل التلقائي مع الويندوز: ${enable ? 'مفعل' : 'معطل'}`);
  updateTrayMenu();
  return { success: true, enabled: !!enable };
});

ipcMain.handle('minimize-to-tray', () => {
  if (mainWindow) {
    mainWindow.hide();
  }
  return { success: true };
});

ipcMain.handle('quit-app', () => {
  isQuitting = true;
  app.quit();
});

ipcMain.handle('restart-agent', () => {
  app.relaunch();
  isQuitting = true;
  app.quit();
});
