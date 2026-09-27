/**
 * printerService.js
 * 
 * High-Level Printer Management & Abstraction Service.
 * 
 * Architectural Highlights:
 * 1. Windows Hardware Identity & Multi-Printer Disambiguation:
 *    Extracts unique PortName (e.g. USB001 vs USB002) and DriverName alongside
 *    PrinterName to distinguish multiple physical printers sharing identical models.
 * 2. Unified Probe & Health Telemetry: Probes Spooler and TCP endpoints seamlessly.
 * 3. 1-Click Role Binding: Safely binds 'cashier', 'kitchen', 'barista' roles to physical hardware.
 * 4. High-Fidelity Test Printing: Produces authentic Arabic RTL ESC/POS receipts
 *    with 4-level typography to immediately verify cutter, alignment, and darkness.
 */

const { exec } = require('child_process');
const configManager = require('./configManager');
const windowsSpooler = require('./windowsSpooler');
const printerDispatcher = require('./printerDispatcher');
const receiptBitmapRenderer = require('./receiptBitmapRenderer');
const printHistory = require('./printHistory');
const logger = require('./logger');

class PrinterService {
  constructor() {
    this.cachedPrinters = [];
    this.lastScanTime = null;
  }

  /**
   * Enumerates all Windows installed spooler printers with full hardware identity:
   * Name, PortName (USB001, USB002, etc.), DriverName, Status.
   */
  getWindowsPrinters() {
    return new Promise((resolve) => {
      if (process.platform !== 'win32') {
        return resolve([]);
      }

      const psScript = `
Get-Printer | Select-Object Name, PortName, DriverName, PrinterStatus, Shared | ConvertTo-Json -Compress
`;

      exec(`powershell -NoProfile -ExecutionPolicy Bypass -Command "${psScript.trim().replace(/\r?\n/g, ' ')}"`, { timeout: 4500 }, (err, stdout) => {
        if (err || !stdout || !stdout.trim()) {
          logger.warn('Failed or timed out querying Windows printers via PowerShell', { error: err?.message });
          return resolve([]);
        }

        try {
          const parsed = JSON.parse(stdout.trim());
          const list = Array.isArray(parsed) ? parsed : [parsed];

          const printers = list.map(p => {
            const name = p.Name || 'Printer';
            const port = p.PortName || 'USB';
            const driver = p.DriverName || 'Generic';
            const statusStr = (p.PrinterStatus === 0 || p.PrinterStatus === 3) ? 'Normal' : `Status ${p.PrinterStatus}`;

            return {
              id: `win-${encodeURIComponent(name)}`,
              name: name,
              windows_printer_name: name,
              port_name: port,
              driver_name: driver,
              type: 'windows',
              display_name: `${name}  [منفذ: ${port}]`,
              status: statusStr,
              is_usb: port.toLowerCase().startsWith('usb'),
              is_network: port.includes('.') || port.toLowerCase().startsWith('tcp')
            };
          });

          resolve(printers);
        } catch (parseErr) {
          logger.error('Error parsing Windows printers JSON output', parseErr);
          resolve([]);
        }
      });
    });
  }

  /**
   * Returns complete list of discovered hardware printers (Windows Spooler + Local TCP).
   */
  async enumeratePrinters(forceScan = false) {
    if (!forceScan && this.cachedPrinters.length > 0 && this.lastScanTime && (Date.now() - this.lastScanTime < 15000)) {
      return this.cachedPrinters;
    }

    try {
      const winPrinters = await this.getWindowsPrinters();
      this.cachedPrinters = winPrinters;
      this.lastScanTime = Date.now();
      return winPrinters;
    } catch (e) {
      logger.error('Printer enumeration failed', e);
      return this.cachedPrinters;
    }
  }

  /**
   * Retrieves configured printers with live probe health statuses.
   */
  async getConfiguredPrintersWithHealth() {
    const config = configManager.getConfig();
    const printersList = config.printers || [];

    const probeResult = await printerDispatcher.probeAllPrinters(printersList);

    return printersList.map(printer => {
      const health = probeResult.healthMap[printer.id] || {};
      return {
        ...printer,
        online: health.online === true,
        latency_ms: health.latencyMs || 0,
        error: health.error || null
      };
    });
  }

  /**
   * Binds selected printer hardware to a specific role ('cashier', 'kitchen', 'barista').
   * Supports identical models differentiated by port or name.
   */
  async assignPrinterRole(role, printerData) {
    if (!role || !printerData) {
      throw new Error('الدور وبيانات الطابعة مطلوبة.');
    }

    const config = configManager.getConfig();
    let currentPrinters = config.printers ? [...config.printers] : [];

    // Target roles to match
    const targetRole = role.toLowerCase();
    const printerName = printerData.windows_printer_name || printerData.name;
    const portName = printerData.port_name || '';

    // Check if an existing entry for this role exists
    const existingIndex = currentPrinters.findIndex(p => p.role === targetRole || (targetRole === 'kitchen' && p.role === 'barista'));

    const newPrinterEntry = {
      id: `printer-${targetRole}`,
      name: `${targetRole === 'cashier' ? 'طابعة الكاشير' : 'طابعة الباريستا / المطبخ'} (${printerName})`,
      type: printerData.type || 'windows',
      windows_printer_name: printerName,
      port_name: portName,
      role: targetRole === 'kitchen' ? 'barista' : targetRole, // Map kitchen to barista role
      enabled: true,
      timeout_ms: 4000
    };

    if (existingIndex >= 0) {
      currentPrinters[existingIndex] = { ...currentPrinters[existingIndex], ...newPrinterEntry };
    } else {
      currentPrinters.push(newPrinterEntry);
    }

    configManager.savePrinters(currentPrinters);
    logger.info(`تم تعيين طابعة ${targetRole}: "${printerName}" [منفذ: ${portName}]`);

    return this.getConfiguredPrintersWithHealth();
  }

  /**
   * Sends an authentic Arabic ESC/POS Test Receipt to the target role printer.
   */
  async testPrint(role = 'cashier', specificPrinterName = null) {
    const config = configManager.getConfig();
    const roleKey = role.toLowerCase();

    // 1. Resolve target printer
    let targetPrinter = null;
    if (specificPrinterName) {
      targetPrinter = {
        name: specificPrinterName,
        windows_printer_name: specificPrinterName,
        type: 'windows',
        enabled: true
      };
    } else {
      const printersList = config.printers || [];
      targetPrinter = printersList.find(p => p.role === roleKey || (roleKey === 'kitchen' && p.role === 'barista') || p.role === 'all');
      if (!targetPrinter && printersList.length > 0) {
        targetPrinter = printersList[0];
      }
    }

    if (!targetPrinter) {
      throw new Error(`لم يتم العثور على طابعة معينة لدور "${role}". يرجى تحديد طابعة من القائمة أولاً.`);
    }

    const pName = targetPrinter.windows_printer_name || targetPrinter.name || 'XP-80C';
    const roleArabic = roleKey === 'cashier' ? 'طابعة الكاشير' : 'طابعة الباريستا / المطبخ';
    const nowTime = new Date().toLocaleTimeString('ar-EG');
    const nowDate = new Date().toLocaleDateString('ar-EG');

    // 2. Build synthetic test job payload
    const testJob = {
      uuid: `test-${Date.now()}`,
      type: roleKey === 'cashier' ? 'customer' : 'kitchen',
      printer_identifier: roleKey,
      payload: {
        order_number: "TEST-88",
        date: nowDate,
        time: nowTime,
        table: "طاولة اختبار",
        cashier_name: "مدير النظام",
        type: "تجربة طباعة",
        items: [
          { quantity: 1, name: `اختبار ${roleArabic}`, price: 50.00, notes: `المنفذ: ${targetPrinter.port_name || 'USB'}` },
          { quantity: 2, name: "طباعة عربية واضحة (RTL)", price: 40.00, notes: "المستوى الرابع - Heavy Black 900" }
        ],
        subtotal: 130.00,
        tax: 0.00,
        total: 130.00,
        payment_method: "تجربة نظام"
      }
    };

    logger.info(`بدء إرسال ورقة تجربة طباعة إلى [${roleArabic} -> "${pName}"]`);

    try {
      let buffer;
      const templates = config.templates || configManager.getDefaultTemplates();

      if (roleKey === 'kitchen' || roleKey === 'barista') {
        buffer = receiptBitmapRenderer.renderKitchenTicket(testJob, config, templates.barista);
      } else {
        buffer = receiptBitmapRenderer.renderCustomerReceipt(testJob, config, templates.cashier);
      }

      let dispatchResult;
      if (printerDispatcher.isWindowsPrinter(targetPrinter)) {
        dispatchResult = await windowsSpooler.sendRawBuffer(pName, buffer);
      } else {
        dispatchResult = await printerDispatcher.sendRawBuffer(targetPrinter.host, targetPrinter.port, buffer);
      }

      printHistory.recordJob({
        job: testJob,
        dispatchResult: { results: { [targetPrinter.id || 'test']: dispatchResult } },
        status: 'printed',
        source: 'desktop_test_print'
      });

      logger.print(`نجحت تجربة الطباعة على [${pName}] (${dispatchResult.bytesSent} بايت)`, true);

      return {
        success: true,
        printer_name: pName,
        role: roleKey,
        bytes_sent: dispatchResult.bytesSent,
        duration_ms: dispatchResult.durationMs,
        message: `تمت طباعة ورقة التجربة بنجاح على طابعة (${pName})`
      };
    } catch (err) {
      logger.print(`فشلت تجربة الطباعة على [${pName}]: ${err.message}`, false);
      throw err;
    }
  }
}

module.exports = new PrinterService();
