/**
 * windowsSpooler.js
 * 
 * Direct Windows Spooler Raw ESC/POS Printer Transport.
 * Transmits binary buffers directly to Windows USB/Spooler printers using winspool.drv.
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');
const os = require('os');

class WindowsSpooler {
  constructor() {
    this._cachedPrinters = null;
    this._cacheTime = 0;
  }

  /**
   * Retrieves all installed Windows printers with status and caching (TTL: 15s)
   */
  getInstalledWindowsPrinters(forceRefresh = false) {
    if (!forceRefresh && this._cachedPrinters && (Date.now() - this._cacheTime < 15000)) {
      return Promise.resolve(this._cachedPrinters);
    }

    return new Promise((resolve) => {
      if (process.platform !== 'win32') {
        return resolve([]);
      }

      const psScript = 'Get-Printer | Select-Object Name, PortName, DriverName, PrinterStatus | ConvertTo-Json -Compress';
      const child = spawn('powershell', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', psScript]);
      let stdout = '';
      let resolved = false;

      const timer = setTimeout(() => {
        if (!resolved) {
          resolved = true;
          try { child.kill(); } catch (_) {}
          resolve(this._cachedPrinters || []);
        }
      }, 7000);

      child.stdout.on('data', (d) => { stdout += d.toString(); });

      child.on('close', (code) => {
        if (resolved) return;
        resolved = true;
        clearTimeout(timer);
        if (code === 0 && stdout.trim()) {
          try {
            const parsed = JSON.parse(stdout.trim());
            const list = Array.isArray(parsed) ? parsed : [parsed];
            this._cachedPrinters = list;
            this._cacheTime = Date.now();
            return resolve(list);
          } catch (_) {}
        }
        resolve(this._cachedPrinters || []);
      });

      child.on('error', () => {
        if (resolved) return;
        resolved = true;
        clearTimeout(timer);
        resolve(this._cachedPrinters || []);
      });
    });
  }

  /**
   * Probes if a Windows printer is installed and ready.
   */
  async probePrinter(printerName) {
    const cleanName = (printerName || 'XP-80C').trim().toLowerCase();
    const startTime = Date.now();

    try {
      let printers = await this.getInstalledWindowsPrinters();
      let found = printers.find(p => (p.Name || '').trim().toLowerCase() === cleanName);

      if (!found) {
        // Retry with force refresh
        printers = await this.getInstalledWindowsPrinters(true);
        found = printers.find(p => (p.Name || '').trim().toLowerCase() === cleanName);
      }

      const latencyMs = Date.now() - startTime;
      if (found) {
        return { online: true, latencyMs, error: null };
      }

      return {
        online: false,
        latencyMs,
        error: `الطابعة '${printerName}' غير مثبتة في Windows`
      };
    } catch (err) {
      return { online: false, latencyMs: Date.now() - startTime, error: err.message };
    }
  }

  /**
   * Sends raw binary buffer to a Windows installed printer by name.
   */
  sendRawBuffer(printerName, buffer) {
    return new Promise((resolve, reject) => {
      const startTime = Date.now();
      const cleanName = (printerName || 'XP-80C').trim();
      const tempFile = path.join(os.tmpdir(), `pos_raw_${Date.now()}_${Math.random().toString(36).substring(2, 7)}.bin`);

      try {
        fs.writeFileSync(tempFile, buffer);
      } catch (err) {
        return reject(new Error(`فشل إنشاء ملف مؤقت للطباعة: ${err.message}`));
      }

      const escapedPrinterName = cleanName.replace(/'/g, "''");
      const psScript = `
$bytes = [System.IO.File]::ReadAllBytes('${tempFile.replace(/\\/g, '\\\\')}');
$code = @'
using System;
using System.IO;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFOW {
        [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
    }
    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPWStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOW di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static bool SendBytesToPrinter(string szPrinterName, byte[] bytes) {
        IntPtr hPrinter = new IntPtr(0);
        DOCINFOW di = new DOCINFOW();
        bool bSuccess = false;
        di.pDocName = "Cafe POS Receipt";
        di.pDataType = "RAW";
        if (OpenPrinter(szPrinterName, out hPrinter, IntPtr.Zero)) {
            if (StartDocPrinter(hPrinter, 1, di)) {
                if (StartPagePrinter(hPrinter)) {
                    IntPtr pUnmanagedBytes = Marshal.AllocCoTaskMem(bytes.Length);
                    Marshal.Copy(bytes, 0, pUnmanagedBytes, bytes.Length);
                    int dwWritten = 0;
                    bSuccess = WritePrinter(hPrinter, pUnmanagedBytes, bytes.Length, out dwWritten);
                    Marshal.FreeCoTaskMem(pUnmanagedBytes);
                    EndPagePrinter(hPrinter);
                }
                EndDocPrinter(hPrinter);
            }
            ClosePrinter(hPrinter);
        }
        return bSuccess;
    }
}
'@
Add-Type -TypeDefinition $code -ErrorAction SilentlyContinue
$res = [RawPrinterHelper]::SendBytesToPrinter('${escapedPrinterName}', $bytes)
if ($res) { Write-Output "PRINT_SUCCESS" } else { Write-Error "PRINT_FAILED_WIN32" }
`;

      const child = spawn('powershell', ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-Command', psScript]);
      let stdout = '';
      let stderr = '';

      child.stdout.on('data', (d) => { stdout += d.toString(); });
      child.stderr.on('data', (d) => { stderr += d.toString(); });

      child.on('close', (code) => {
        try { fs.unlinkSync(tempFile); } catch (_) {}
        const durationMs = Date.now() - startTime;
        if (stdout.includes('PRINT_SUCCESS')) {
          console.log(`[WindowsSpooler] تم إرسال ${buffer.length} بايت بنجاح إلى الطابعة [${cleanName}] عبر Windows Spooler (${durationMs}ms)`);
          resolve({
            success: true,
            transport: 'windows_spooler',
            printer_name: cleanName,
            bytesSent: buffer.length,
            durationMs
          });
        } else {
          const errDetail = stderr.trim() || stdout.trim() || `Exit code ${code}`;
          console.error(`[WindowsSpooler] فشل إرسال البيانات إلى [${cleanName}]: ${errDetail}`);
          reject(new Error(`فشل الطباعة على طابعة الويندوز (${cleanName}): ${errDetail}`));
        }
      });

      child.on('error', (err) => {
        try { fs.unlinkSync(tempFile); } catch (_) {}
        reject(new Error(`خطأ في تشغيل أمر الطباعة: ${err.message}`));
      });
    });
  }
}

module.exports = new WindowsSpooler();
