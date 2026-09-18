/**
 * updater.js
 * 
 * Over-The-Air (OTA) Background Auto-Updater for POS Print Agent.
 * 
 * Architectural Highlights:
 * 1. Periodic Version Polling: Queries Laravel's `/api/pos/check-update` on startup and every 6 hours.
 * 2. Non-Blocking Chunked Download: Streams replacement binary directly to disk as `pos-agent-new.exe`.
 * 3. Self-Terminating Atomic Replacement: On Windows, generates and spawns a detached `updater.bat`
 *    which waits for file lock release, overwrites `pos-agent.exe`, starts the new process,
 *    and removes itself.
 * 4. Cross-Platform Guard: On macOS/Linux development environments, safely reports update availability
 *    without executing Windows batch routines.
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

class AutoUpdater {
  constructor() {
    this.config = null;
    this.isUpdating = false;
    this.checkIntervalTimer = null;
    this.lastCheck = {
      timestamp: null,
      update_available: false,
      latest_version: null,
      error: null
    };
  }

  /**
   * Initializes updater with configuration and schedules periodic polling.
   */
  init(config) {
    this.config = config;

    if (config.auto_update_enabled === false) {
      console.log('[AutoUpdater] التحديث التلقائي معطل في الإعدادات.');
      return;
    }

    console.log(`[AutoUpdater] تم تفعيل التحديث التلقائي (الإصدار الحالي: v${config.app_version || '1.0.0'})`);

    // Initial check after 10s delay to allow agent bootstrap
    setTimeout(() => {
      this.checkForUpdates();
    }, 10000);

    // Run check every 6 hours (6 * 3600 * 1000 ms)
    const SIX_HOURS = 6 * 60 * 60 * 1000;
    this.checkIntervalTimer = setInterval(() => {
      this.checkForUpdates();
    }, SIX_HOURS);
  }

  /**
   * Checks Laravel API for available updates.
   */
  async checkForUpdates() {
    if (!this.config || !this.config.laravel_backend_url) {
      return { update_available: false, error: 'عنوان السيرفر غير متوفر.' };
    }

    const currentVersion = this.config.app_version || '1.0.0';
    const baseUrl = this.config.laravel_backend_url.replace(/\/+$/, '');
    const endpoint = `${baseUrl}/api/pos/check-update?version=${encodeURIComponent(currentVersion)}`;

    console.log(`[AutoUpdater] فحص التحديثات من: ${endpoint}...`);

    try {
      const res = await fetch(endpoint, {
        headers: { 'Accept': 'application/json' },
        signal: AbortSignal.timeout(8000)
      });

      if (!res.ok) {
        throw new Error(`رد غير متوقع من السيرفر: HTTP ${res.status}`);
      }

      const data = await res.json();
      this.lastCheck = {
        timestamp: new Date().toISOString(),
        update_available: Boolean(data.update_available),
        latest_version: data.latest_version || currentVersion,
        error: null
      };

      console.log(`[AutoUpdater] نتيجة الفحص: النسخة الحالية = ${currentVersion} | أحدث نسخة = ${data.latest_version || 'N/A'} | تحديث متوفر = ${data.update_available}`);

      if (data.update_available && data.download_url) {
        console.log(`[AutoUpdater] تم العثور على إصدار أحدث v${data.latest_version}. رابط التحميل: ${data.download_url}`);
        
        // If on Windows and in production package, trigger auto-upgrade
        if (process.platform === 'win32' && process.pkg) {
          await this.downloadAndApplyUpdate(data.download_url, data.latest_version);
        } else {
          console.log('[AutoUpdater] تخطي تطبيق التحديث التلقائي (البيئة الحالية ليست بيئة تشغيل إنتاجية لويندوز .exe).');
        }
      }

      return this.lastCheck;
    } catch (err) {
      console.warn(`[AutoUpdater] فشل فحص التحديثات: ${err.message}`);
      this.lastCheck = {
        timestamp: new Date().toISOString(),
        update_available: false,
        latest_version: currentVersion,
        error: err.message
      };
      return this.lastCheck;
    }
  }

  /**
   * Downloads replacement binary and spawns detached Windows updater.bat
   */
  async downloadAndApplyUpdate(downloadUrl, newVersion) {
    if (this.isUpdating) {
      console.log('[AutoUpdater] التحديث قيد التحميل بالفعل.');
      return;
    }

    this.isUpdating = true;
    const baseDir = path.dirname(process.execPath);
    const tempExePath = path.join(baseDir, 'pos-agent-new.exe');
    const targetExeName = path.basename(process.execPath);
    const batchPath = path.join(baseDir, 'updater.bat');

    console.log(`[AutoUpdater] بدء تنزيل الإصدار الجديد إلى: ${tempExePath}...`);

    try {
      const response = await fetch(downloadUrl, {
        headers: { 'User-Agent': 'CafePOS-PrintAgent-Updater' }
      });

      if (!response.ok) {
        throw new Error(`فشل تحميل ملف التحديث: HTTP ${response.status}`);
      }

      const fileStream = fs.createWriteStream(tempExePath);
      const reader = response.body.getReader();

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        fileStream.write(Buffer.from(value));
      }

      fileStream.end();

      await new Promise((resolve) => fileStream.on('finish', resolve));
      console.log('[AutoUpdater] تم تنزيل الملف بنجاح. تجهيز سيناريو الترقية...');

      // اسم خدمة الويندوز المعتمد
      const SERVICE_NAME = 'CafePrintAgent';
      const targetExePath = path.join(baseDir, targetExeName);

      const batContent = `@echo off
chcp 65001 > NUL
echo [POS Updater] الانتظار لتحرير الملف...
timeout /t 3 /nobreak > NUL

:: محاولة إيقاف الخدمة لو شغالة كـ Windows Service
sc query "${SERVICE_NAME}" > NUL 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [POS Updater] إيقاف خدمة الويندوز...
    net stop "${SERVICE_NAME}" > NUL 2>&1
    timeout /t 2 /nobreak > NUL
)

echo [POS Updater] استبدال الملف التنفيذي...
move /y "${tempExePath}" "${targetExePath}"

:: إعادة التشغيل: كخدمة لو موجودة، أو كـ process عادي
sc query "${SERVICE_NAME}" > NUL 2>&1
if %ERRORLEVEL% EQU 0 (
    echo [POS Updater] إعادة تشغيل خدمة الويندوز...
    net start "${SERVICE_NAME}"
) else (
    echo [POS Updater] إعادة تشغيل التطبيق...
    start "" "${targetExePath}"
)

echo [POS Updater] تم التحديث بنجاح.
del "%~f0"
`;

      fs.writeFileSync(batchPath, batContent, 'utf8');

      console.log('[AutoUpdater] تشغيل سكريبت التحديث المنفصل وإغلاق التطبيق الحالي...');

      // Spawn detached batch script
      const child = spawn('cmd.exe', ['/c', batchPath], {
        detached: true,
        stdio: 'ignore',
        cwd: baseDir
      });
      child.unref();

      // Gracefully exit current process
      setTimeout(() => {
        process.exit(0);
      }, 500);

    } catch (err) {
      this.isUpdating = false;
      console.error(`[AutoUpdater] حدث خطأ أثناء تطبيق التحديث: ${err.message}`);
      try {
        if (fs.existsSync(tempExePath)) fs.unlinkSync(tempExePath);
        if (fs.existsSync(batchPath)) fs.unlinkSync(batchPath);
      } catch (_) {}
    }
  }

  getLastCheck() {
    return this.lastCheck;
  }
}

module.exports = new AutoUpdater();
