/**
 * windowsSpooler.js
 * 
 * Direct Windows Spooler Raw ESC/POS Printer Transport.
 * Transmits binary buffers directly to Windows USB/Spooler printers using winspool.drv.
 */

const { spawn, exec } = require('child_process');
const path = require('path');
const fs = require('fs');
const os = require('os');

class WindowsSpooler {
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

      const psScript = `
$bytes = [System.IO.File]::ReadAllBytes('${tempFile.replace(/\\/g, '\\\\')}');
$code = @'
using System;
using System.IO;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)]
    public class DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }
    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);

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
        DOCINFOA di = new DOCINFOA();
        bool bSuccess = false;
        di.pDocName = "Cafe POS Receipt";
        di.pDataType = "RAW";
        if (OpenPrinter(szPrinterName.Normalize(), out hPrinter, IntPtr.Zero)) {
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
$res = [RawPrinterHelper]::SendBytesToPrinter('${cleanName}', $bytes)
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

  /**
   * Probes if a Windows printer is installed and ready.
   */
  probePrinter(printerName) {
    return new Promise((resolve) => {
      const cleanName = (printerName || 'XP-80C').trim();
      const startTime = Date.now();
      const cmd = `powershell -NoProfile -ExecutionPolicy Bypass -Command "$p = Get-Printer -Name '${cleanName}' -ErrorAction SilentlyContinue; if ($p) { Write-Output ('STATUS:' + $p.PrinterStatus) } else { Write-Output 'NOT_FOUND' }"`;

      exec(cmd, { timeout: 3000 }, (err, stdout) => {
        const latencyMs = Date.now() - startTime;
        if (err || !stdout) {
          return resolve({ online: false, latencyMs, error: err ? err.message : 'لم يتم العثور على الطابعة' });
        }
        const text = stdout.trim();
        if (text.includes('NOT_FOUND')) {
          return resolve({ online: false, latencyMs, error: `الطابعة '${cleanName}' غير مثبتة في Windows` });
        }
        return resolve({ online: true, latencyMs, error: null });
      });
    });
  }
}

module.exports = new WindowsSpooler();
