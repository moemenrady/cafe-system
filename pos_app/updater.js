/**
 * updater.js
 * 
 * Over-The-Air (OTA) Background & 1-Click Remote Auto-Updater for Cafe Print Agent.
 * 
 * Features:
 * 1. Automatic Periodic Checks & Instant On-Demand Manual Triggers.
 * 2. Real-time Download Progress Tracking (0-100%).
 * 3. Silent Windows Installer Execution (/S) with zero user prompts.
 * 4. Automatic Application Relaunch after upgrade.
 * 5. Reverb WebSocket remote trigger support (.pos.update).
 */

const fs = require('fs');
const path = require('path');
const os = require('os');
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
      download_url: null,
      release_notes: null,
      error: null
    };
    this.updateStatus = {
      state: 'idle', // 'idle' | 'checking' | 'downloading' | 'installing' | 'completed' | 'error'
      percent: 0,
      downloadedBytes: 0,
      totalBytes: 0,
      version: null,
      message: 'البرنامج محدث لآخر إصدار',
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

    console.log(`[AutoUpdater] تم تفعيل نظام التحديث الهوائي عن بُعد (الإصدار الحالي: v${config.app_version || '1.0.0'})`);

    // Initial check after 8s delay to allow agent bootstrap
    setTimeout(() => {
      this.checkForUpdates(false);
    }, 8000);

    // Run check every 1 hour (1 * 3600 * 1000 ms)
    const ONE_HOUR = 60 * 60 * 1000;
    if (this.checkIntervalTimer) clearInterval(this.checkIntervalTimer);
    this.checkIntervalTimer = setInterval(() => {
      this.checkForUpdates(false);
    }, ONE_HOUR);
  }

  isProductionWindows() {
    if (process.platform !== 'win32') return false;
    const execPath = (process.execPath || '').toLowerCase();
    // Packaged Electron or compiled executable
    if (execPath.endsWith('cafe print agent.exe') || execPath.endsWith('pos-agent.exe') || (process.versions?.electron && !execPath.includes('node_modules'))) {
      return true;
    }
    return false;
  }

  /**
   * Checks Laravel API for available updates.
   */
  async checkForUpdates(forceApply = false) {
    if (!this.config || !this.config.laravel_backend_url) {
      this.lastCheck.error = 'عنوان السيرفر غير متوفر في الإعدادات.';
      return { update_available: false, error: this.lastCheck.error };
    }

    const currentVersion = this.config.app_version || '1.0.0';
    const baseUrl = this.config.laravel_backend_url.replace(/\/+$/, '');
    const endpoint = `${baseUrl}/api/pos/check-update?version=${encodeURIComponent(currentVersion)}`;

    console.log(`[AutoUpdater] فحص التحديثات من السيرفر: ${endpoint}...`);
    this.updateStatus.state = 'checking';
    this.updateStatus.message = 'جاري فحص وجود تحديثات من السيرفر...';

    try {
      const res = await fetch(endpoint, {
        headers: { 'Accept': 'application/json' },
        signal: AbortSignal.timeout(10000)
      });

      if (!res.ok) {
        throw new Error(`رد غير متوقع من السيرفر: HTTP ${res.status}`);
      }

      const data = await res.json();
      const updateAvailable = Boolean(data.update_available);
      const latestVersion = data.latest_version || currentVersion;
      const downloadUrl = data.download_url || null;

      this.lastCheck = {
        timestamp: new Date().toISOString(),
        update_available: updateAvailable,
        latest_version: latestVersion,
        download_url: downloadUrl,
        release_notes: data.release_notes || null,
        error: null
      };

      console.log(`[AutoUpdater] نتيجة الفحص: النسخة الحالية = ${currentVersion} | أحدث نسخة = ${latestVersion} | يوجد تحديث = ${updateAvailable}`);

      if (updateAvailable && downloadUrl) {
        this.updateStatus.message = `يوجد إصدار جديد متاح: v${latestVersion}`;
        
        // Auto-apply if mandatory, or if forceApply requested, or if auto_update_enabled
        if (forceApply || data.mandatory || (this.config.auto_update_enabled && this.isProductionWindows())) {
          console.log(`[AutoUpdater] بدء تطبيق التحديث تلقائياً للإصدار v${latestVersion}...`);
          this.downloadAndApplyUpdate(downloadUrl, latestVersion).catch(e => {
            console.error('[AutoUpdater] خطأ أثناء تطبيق التحديث التلقائي:', e.message);
          });
        }
      } else {
        this.updateStatus.state = 'idle';
        this.updateStatus.message = `البرنامج محدث لآخر إصدار (v${currentVersion})`;
      }

      return this.lastCheck;
    } catch (err) {
      console.warn(`[AutoUpdater] تعذر فحص التحديثات: ${err.message}`);
      this.lastCheck = {
        timestamp: new Date().toISOString(),
        update_available: false,
        latest_version: currentVersion,
        download_url: null,
        release_notes: null,
        error: err.message
      };
      this.updateStatus.state = 'error';
      this.updateStatus.error = err.message;
      this.updateStatus.message = `تعذر فحص التحديثات: ${err.message}`;
      return this.lastCheck;
    }
  }

  /**
   * Downloads replacement binary and spawns detached Windows silent installer
   */
  async downloadAndApplyUpdate(downloadUrl, newVersion) {
    if (this.isUpdating) {
      console.log('[AutoUpdater] التحديث قيد التحميل والتثبيت بالفعل.');
      return;
    }

    this.isUpdating = true;
    this.updateStatus.state = 'downloading';
    this.updateStatus.percent = 0;
    this.updateStatus.version = newVersion;
    this.updateStatus.message = `جاري تنزيل التحديث v${newVersion}... 0%`;

    const tempDir = os.tmpdir();
    const isAsar = downloadUrl.toLowerCase().endsWith('.asar');
    const tempFileName = isAsar ? `pos_app_update_${Date.now()}.asar` : `pos_setup_update_${Date.now()}.exe`;
    const tempFilePath = path.join(tempDir, tempFileName);
    const batchPath = path.join(tempDir, `pos_apply_update_${Date.now()}.bat`);

    console.log(`[AutoUpdater] بدء تنزيل ملف التحديث من: ${downloadUrl}`);
    console.log(`[AutoUpdater] مسار الحفظ المؤقت: ${tempFilePath}`);

    try {
      const response = await fetch(downloadUrl, {
        headers: { 'User-Agent': 'CafePOS-PrintAgent-AutoUpdater' },
        signal: AbortSignal.timeout(180000) // 3 minutes timeout for download
      });

      if (!response.ok) {
        throw new Error(`فشل تحميل ملف التحديث من السيرفر: HTTP ${response.status}`);
      }

      const contentLength = Number(response.headers.get('content-length')) || 0;
      this.updateStatus.totalBytes = contentLength;

      const fileStream = fs.createWriteStream(tempFilePath);
      const reader = response.body.getReader();
      let downloadedBytes = 0;

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        downloadedBytes += value.length;
        fileStream.write(Buffer.from(value));

        if (contentLength > 0) {
          const percent = Math.min(99, Math.round((downloadedBytes / contentLength) * 100));
          this.updateStatus.percent = percent;
          this.updateStatus.downloadedBytes = downloadedBytes;
          this.updateStatus.message = `جاري تنزيل التحديث v${newVersion}... (${percent}%)`;
        }
      }

      fileStream.end();
      await new Promise((resolve) => fileStream.on('finish', resolve));

      console.log(`[AutoUpdater] اكتمل تنزيل ملف التحديث بنجاح (${downloadedBytes} بايت).`);
      this.updateStatus.percent = 100;
      this.updateStatus.state = 'installing';
      this.updateStatus.message = `تم التنزيل بنجاح! جاري تثبيت النسخة الجديدة v${newVersion} وإعادة التشغيل...`;

      // Check current executable path
      const currentExecPath = process.execPath;
      const currentExecDir = path.dirname(currentExecPath);
      const execName = path.basename(currentExecPath);

      let batContent = '';

      if (isAsar) {
        // Hot patch resources/app.asar
        const targetAsarPath = path.join(currentExecDir, 'resources', 'app.asar');
        batContent = `@echo off
chcp 65001 > NUL
echo [POS Updater] إغلاق البرنامج لتطبيق التحديث السريع...
timeout /t 2 /nobreak > NUL
taskkill /F /IM "${execName}" > NUL 2>&1
timeout /t 1 /nobreak > NUL

echo [POS Updater] استبدال ملفات الكود...
move /y "${tempFilePath}" "${targetAsarPath}"

echo [POS Updater] إعادة تشغيل البرنامج...
timeout /t 1 /nobreak > NUL
start "" "${currentExecPath}"
del "%~f0"
`;
      } else {
        // Full NSIS Silent Installer update
        batContent = `@echo off
chcp 65001 > NUL
echo ==============================================
echo    تحديث برنامج طباعة الكافيه تلقائياً عن بُعد  
echo ==============================================
echo [POS Updater] إغلاق التطبيق الحالي لتثبيت التحديث...
timeout /t 2 /nobreak > NUL
taskkill /F /IM "${execName}" > NUL 2>&1
taskkill /F /IM "Cafe Print Agent.exe" > NUL 2>&1
taskkill /F /IM "pos-agent.exe" > NUL 2>&1
timeout /t 2 /nobreak > NUL

echo [POS Updater] تشغيل التحديث الصامت للنسخة الجديدة...
start /wait "" "${tempFilePath}" /S
timeout /t 3 /nobreak > NUL

echo [POS Updater] إعادة تشغيل البرنامج المحدث تلقائياً...
if exist "%LOCALAPPDATA%\\Programs\\Cafe Print Agent\\Cafe Print Agent.exe" (
    start "" "%LOCALAPPDATA%\\Programs\\Cafe Print Agent\\Cafe Print Agent.exe"
) else if exist "${currentExecPath.replace(/\\/g, '\\\\')}" (
    start "" "${currentExecPath.replace(/\\/g, '\\\\')}"
)

timeout /t 2 /nobreak > NUL
del "${tempFilePath}" > NUL 2>&1
del "%~f0"
`;
      }

      fs.writeFileSync(batchPath, batContent, 'utf8');

      console.log('[AutoUpdater] تشغيل سكريبت التحديث الصامت وإعادة تشغيل التطبيق...');

      // Spawn detached batch script with completely independent process lifecycle
      const child = spawn('cmd.exe', ['/c', batchPath], {
        detached: true,
        stdio: 'ignore'
      });
      child.unref();

      // Exit current process cleanly so installer can overwrite files
      setTimeout(() => {
        process.exit(0);
      }, 1000);

    } catch (err) {
      this.isUpdating = false;
      this.updateStatus.state = 'error';
      this.updateStatus.error = err.message;
      this.updateStatus.message = `فشل تطبيق التحديث: ${err.message}`;
      console.error(`[AutoUpdater] حدث خطأ أثناء تطبيق التحديث: ${err.message}`);

      try {
        if (fs.existsSync(tempFilePath)) fs.unlinkSync(tempFilePath);
        if (fs.existsSync(batchPath)) fs.unlinkSync(batchPath);
      } catch (_) {}
    }
  }

  getLastCheck() {
    return this.lastCheck;
  }

  getStatus() {
    return {
      lastCheck: this.lastCheck,
      updateStatus: this.updateStatus,
      isUpdating: this.isUpdating
    };
  }
}

module.exports = new AutoUpdater();
