/**
 * printerDispatcher.js
 * 
 * Dynamic Multi-Printer ESC/POS TCP Socket Dispatcher & Template Engine.
 * 
 * Architectural Highlights:
 * 1. Template-Driven Formatting: Generates pure binary ESC/POS buffers dynamically
 *    based on user-configured receipt templates (custom store headers, alignments,
 *    font scalings, drawer kick, and paper cut flags).
 * 2. Dynamic Role-Based Routing: Multiplexes print jobs across arbitrary printer fleets
 *    (e.g. 4+ printers). Matches incoming roles ('cashier', 'barista', 'kitchen', 'all')
 *    and dispatches concurrently using Promise.allSettled.
 * 3. Proactive Socket Probing & Role Extraction: Probes all configured printers
 *    and extracts active roles for Laravel heartbeat syncing.
 * 4. Zero C++ Dependencies: Native Node.js `net` and `Buffer` primitives with strict
 *    leak-free socket destruction and timeout guards.
 */

const net = require('net');
const configManager = require('./configManager');
const { prepareArabicLine } = require('./arabicHelper');

// ESC/POS Binary Byte Primitives
const ESC = 0x1B;
const GS  = 0x1D;
const FS  = 0x1C;
const LF  = 0x0A;

const ALIGN_MAP = {
  left: Buffer.from([ESC, 0x61, 0x00]),
  center: Buffer.from([ESC, 0x61, 0x01]),
  right: Buffer.from([ESC, 0x61, 0x02])
};

const SIZE_MAP = {
  normal: Buffer.from([GS, 0x21, 0x00]),
  double: Buffer.from([GS, 0x21, 0x11]),
  double_height: Buffer.from([GS, 0x21, 0x01]),
  double_width: Buffer.from([GS, 0x21, 0x10])
};

const COMMANDS = {
  INIT: Buffer.from([ESC, 0x40]),
  ENABLE_UTF8: Buffer.from([FS, 0x26]), // FS & Enable UTF-8 / Multi-byte mode
  BOLD_ON: Buffer.from([ESC, 0x45, 0x01]),
  BOLD_OFF: Buffer.from([ESC, 0x45, 0x00]),
  DRAWER_KICK: Buffer.from([ESC, 0x70, 0x00, 0x19, 0xFA]), // Pin 2 Solenoid (50ms pulse)
  FEED_3_LINES: Buffer.from([ESC, 0x64, 0x03]),
  FEED_5_LINES: Buffer.from([ESC, 0x64, 0x05]),
  PAPER_CUT_PARTIAL: Buffer.from([GS, 0x56, 0x41, 0x03]) // Feed 3 lines & partial cut
};

class PrinterDispatcher {
  constructor() {
    this.defaultTimeoutMs = 4000;
  }

  /**
   * Probes a single printer TCP socket.
   */
  probePrinter(host, port, timeoutMs = 3000) {
    return new Promise((resolve) => {
      const startTime = Date.now();
      const socket = new net.Socket();
      let resolved = false;

      const finish = (online, error = null) => {
        if (resolved) return;
        resolved = true;
        const latencyMs = Date.now() - startTime;
        try {
          socket.removeAllListeners();
          socket.destroy();
        } catch (_) {}
        resolve({ online, latencyMs, error });
      };

      socket.setTimeout(timeoutMs);
      socket.on('connect', () => finish(true, null));
      socket.on('timeout', () => finish(false, `انتهت مهلة الاتصال (${timeoutMs}ms)`));
      socket.on('error', (err) => finish(false, err.message || 'خطأ في اتصال الشبكة'));

      try {
        socket.connect(Number(port), host);
      } catch (err) {
        finish(false, err.message);
      }
    });
  }

  /**
   * Probes all configured printers concurrently and extracts active roles.
   */
  async probeAllPrinters(printersList) {
    const list = Array.isArray(printersList) ? printersList : [];
    const healthMap = {};
    const activeRolesSet = new Set();

    const probePromises = list.map(async (printer) => {
      if (!printer.enabled) {
        healthMap[printer.id] = {
          name: printer.name,
          role: printer.role,
          host: printer.host,
          port: printer.port,
          online: false,
          latencyMs: 0,
          error: 'معطلة'
        };
        return;
      }

      const res = await this.probePrinter(printer.host, printer.port, printer.timeout_ms || 2500);
      healthMap[printer.id] = {
        name: printer.name,
        role: printer.role,
        host: printer.host,
        port: printer.port,
        online: res.online,
        latencyMs: res.latencyMs,
        error: res.error
      };

      if (res.online) {
        if (printer.role === 'all') {
          activeRolesSet.add('cashier');
          activeRolesSet.add('barista');
          activeRolesSet.add('kitchen');
        } else {
          activeRolesSet.add(printer.role);
        }
      }
    });

    await Promise.allSettled(probePromises);

    const enabledPrinters = list.filter(p => p.enabled);
    let overallStatus = 'ready';

    if (enabledPrinters.length === 0) {
      overallStatus = 'printers_unconfigured';
    } else {
      const anyOffline = enabledPrinters.some(p => !healthMap[p.id]?.online);
      if (anyOffline) {
        overallStatus = 'printer_offline';
      }
    }

    return {
      healthMap,
      activeRoles: Array.from(activeRolesSet),
      overallStatus
    };
  }

  /**
   * Transmits raw binary buffer over dedicated TCP socket with strict lifecycle teardown.
   */
  sendRawBuffer(host, port, buffer, timeoutMs = this.defaultTimeoutMs) {
    return new Promise((resolve, reject) => {
      const startTime = Date.now();
      const socket = new net.Socket();
      let resolved = false;

      const cleanup = () => {
        try {
          socket.removeAllListeners();
          socket.destroy();
        } catch (_) {}
      };

      socket.setTimeout(timeoutMs);

      socket.on('connect', () => {
        socket.write(buffer, (err) => {
          if (err) {
            cleanup();
            if (!resolved) {
              resolved = true;
              return reject(new Error(`فشل إرسال البيانات للطابعة: ${err.message}`));
            }
          }
          socket.end();
        });
      });

      socket.on('close', (hadError) => {
        cleanup();
        if (!resolved) {
          resolved = true;
          if (hadError) {
            reject(new Error('أُغلقت القناة قبل اكتمال إرسال البيانات.'));
          } else {
            resolve({
              success: true,
              bytesSent: buffer.length,
              durationMs: Date.now() - startTime
            });
          }
        }
      });

      socket.on('timeout', () => {
        cleanup();
        if (!resolved) {
          resolved = true;
          reject(new Error(`انتهت مهلة استجابة الطابعة (${timeoutMs}ms)`));
        }
      });

      socket.on('error', (err) => {
        cleanup();
        if (!resolved) {
          resolved = true;
          reject(new Error(`خطأ في التواصل مع الطابعة (${host}:${port}): ${err.message}`));
        }
      });

      try {
        socket.connect(Number(port), host);
      } catch (err) {
        cleanup();
        resolved = true;
        reject(new Error(`تعذر بدء اتصال الشبكة: ${err.message}`));
      }
    });
  }

  /**
   * Resolves target printers based on role binding.
   */
  resolveTargetPrinters(targetIdentifier, printersList) {
    const target = (targetIdentifier || 'cashier').toLowerCase();
    const list = Array.isArray(printersList) ? printersList : [];

    return list.filter(printer => {
      if (!printer.enabled) return false;

      // Exact ID match
      if (printer.id === target) return true;

      // Role match: cashier
      if (target === 'cashier' || target === 'customer') {
        return printer.role === 'cashier' || printer.role === 'all';
      }

      // Role match: barista or kitchen
      if (target === 'barista' || target === 'kitchen') {
        return printer.role === 'barista' || printer.role === 'kitchen' || printer.role === 'all';
      }

      // Role match: both / all
      if (target === 'both' || target === 'all') {
        return true;
      }

      return printer.role === target;
    });
  }

  /**
   * Main Job Dispatcher.
   * Matches matching printers and executes concurrent dispatch via Promise.allSettled.
   */
  async dispatchPrintJob(job, config, customTemplate = null) {
    const printerIdentifier = (job.printer_identifier || job.type || 'cashier').toLowerCase();
    const targetPrinters = this.resolveTargetPrinters(printerIdentifier, config.printers);

    if (targetPrinters.length === 0) {
      throw new Error(`لا توجد طابعات مفعلة مطابقة للدور المطلوب: "${printerIdentifier}"`);
    }

    console.log(`[PrinterDispatcher] جاري إرسال المهمة [${job.uuid || 'N/A'}] إلى ${targetPrinters.length} طابعة مطابقة.`);

    const templates = config.templates || configManager.getDefaultTemplates();
    const results = {};

    const tasks = targetPrinters.map(async (printer) => {
      try {
        // Choose template based on printer role or job type
        let buffer;
        if (printer.role === 'barista' || printer.role === 'kitchen' || printerIdentifier === 'barista' || printerIdentifier === 'kitchen') {
          const tpl = customTemplate || templates.barista;
          buffer = this.formatKitchenTicket(job, config, tpl);
        } else {
          const tpl = customTemplate || templates.cashier;
          buffer = this.formatCustomerReceipt(job, config, tpl);
        }

        const dispatchResult = await this.sendRawBuffer(printer.host, printer.port, buffer, printer.timeout_ms);
        return {
          printer_id: printer.id,
          printer_name: printer.name,
          role: printer.role,
          success: true,
          ...dispatchResult
        };
      } catch (err) {
        return {
          printer_id: printer.id,
          printer_name: printer.name,
          role: printer.role,
          success: false,
          error: err.message
        };
      }
    });

    const settled = await Promise.allSettled(tasks);

    settled.forEach((res, index) => {
      const printer = targetPrinters[index];
      if (res.status === 'fulfilled') {
        results[printer.id] = res.value;
      } else {
        results[printer.id] = {
          printer_id: printer.id,
          printer_name: printer.name,
          role: printer.role,
          success: false,
          error: res.reason.message
        };
      }
    });

    const anySuccessful = Object.values(results).some(r => r.success === true);
    const allSuccessful = Object.values(results).every(r => r.success === true);

    if (!anySuccessful) {
      const errors = Object.values(results).map(r => `${r.printer_name}: ${r.error}`).join(' | ');
      throw new Error(`فشلت الطباعة على جميع الطابعات المستهدفة: ${errors}`);
    }

    return {
      success: allSuccessful,
      partial: !allSuccessful && anySuccessful,
      results
    };
  }

  // ==========================================
  // Dynamic Template-Driven Buffer Builders
  // ==========================================

  /**
   * Formats a Customer Receipt using dynamic template settings.
   */
  formatCustomerReceipt(job, config, template) {
    const tpl = template || config.templates?.cashier || configManager.getDefaultTemplates().cashier;
    const header = tpl.header || {};
    const body = tpl.body || {};
    const footer = tpl.footer || {};

    const payload = job.payload || {};
    const items = payload.items || [];
    const chunks = [];

    const append = (buf) => chunks.push(buf);
    const appendLine = (text = '') => chunks.push(Buffer.from(prepareArabicLine(text) + '\n', 'utf8'));
    const appendRawLine = (text = '') => chunks.push(Buffer.from(text + '\n', 'utf8'));

    // 1. Hardware Initialization
    append(COMMANDS.INIT);
    append(COMMANDS.ENABLE_UTF8);

    // 2. Header
    append(ALIGN_MAP[header.align || 'center'] || ALIGN_MAP.center);
    if (header.store_name_size === 'double') {
      append(SIZE_MAP.double);
    } else {
      append(SIZE_MAP.normal);
    }
    append(COMMANDS.BOLD_ON);
    appendLine(header.store_name || 'CAFE MANAGEMENT');
    append(SIZE_MAP.normal);
    append(COMMANDS.BOLD_OFF);

    if (header.branch) appendLine(header.branch);
    if (header.tax_number) appendLine(`الرقم الضريبي: ${header.tax_number}`);
    if (header.phone) appendLine(`الهاتف: ${header.phone}`);
    appendLine('------------------------------------------');

    // 3. Receipt Metadata
    append(ALIGN_MAP.left);
    const orderNo = payload.order_number || payload.order_id || job.uuid?.substring(0, 8) || 'N/A';
    const dateStr = payload.date || new Date().toLocaleString();
    appendLine(`رقم الطلب: #${orderNo}`);
    appendLine(`التاريخ  : ${dateStr}`);

    if (body.show_table && (payload.table || payload.table_number)) {
      appendLine(`الطاولة  : ${payload.table || payload.table_number}`);
    }
    if (body.show_server && (payload.cashier_name || payload.server)) {
      appendLine(`الكاشير  : ${payload.cashier_name || payload.server}`);
    }
    appendLine('------------------------------------------');

    // 4. Tabular Items
    append(COMMANDS.BOLD_ON);
    appendRawLine(this.formatLineColumns('الكمية والصنف', 'السعر'));
    append(COMMANDS.BOLD_OFF);
    appendLine('- - - - - - - - - - - - - - - - - - - - - ');

    if (items.length > 0) {
      items.forEach(item => {
        const qty = item.quantity || item.qty || 1;
        const name = item.name || item.title || 'صنف';
        const price = Number(item.total || item.price || 0).toFixed(2);
        appendRawLine(this.formatLineColumns(`${qty}x ${name}`.substring(0, 30), price));

        if (item.notes || item.customization) {
          appendLine(`   * ${item.notes || item.customization}`);
        }
      });
    } else {
      appendRawLine(this.formatLineColumns('1x طلب خاص', Number(payload.total || 0).toFixed(2)));
    }

    appendLine('------------------------------------------');

    // 5. Totals & Tax Breakdown
    const subtotal = Number(payload.subtotal || payload.total || 0).toFixed(2);
    const tax = Number(payload.tax || 0).toFixed(2);
    const total = Number(payload.total || subtotal).toFixed(2);

    if (body.show_tax_breakdown && payload.tax) {
      appendRawLine(this.formatLineColumns('المجموع الفرعي:', `${subtotal} ج.م`));
      appendRawLine(this.formatLineColumns('ضريبة القيمة المضافة:', `${tax} ج.م`));
    }

    // Grand Total (Large Bold)
    append(SIZE_MAP.double_height);
    append(COMMANDS.BOLD_ON);
    appendRawLine(this.formatLineColumns('الإجمالي:', `${total} ج.م`));
    append(SIZE_MAP.normal);
    append(COMMANDS.BOLD_OFF);

    if (payload.payment_method) {
      appendRawLine(this.formatLineColumns('طريقة الدفع:', payload.payment_method));
    }
    appendLine('------------------------------------------');

    // 6. Footer
    append(ALIGN_MAP[footer.align || 'center'] || ALIGN_MAP.center);
    if (footer.thank_you_message) {
      appendLine(footer.thank_you_message);
    }
    if (footer.wifi_pass) {
      appendLine(`كلمة سر الواي فاي: ${footer.wifi_pass}`);
    }
    append(COMMANDS.FEED_3_LINES);

    // 7. Cash Drawer Kick (Strictly conditional)
    if (body.show_drawer_kick) {
      append(COMMANDS.DRAWER_KICK);
    }

    // 8. Paper Cut (Strictly conditional)
    if (footer.show_cut) {
      append(COMMANDS.PAPER_CUT_PARTIAL);
    }

    return Buffer.concat(chunks);
  }

  /**
   * Formats a Barista / Kitchen production ticket using dynamic template settings.
   */
  formatKitchenTicket(job, config, template) {
    const tpl = template || config.templates?.barista || configManager.getDefaultTemplates().barista;
    const header = tpl.header || {};
    const body = tpl.body || {};
    const footer = tpl.footer || {};

    const payload = job.payload || {};
    const items = payload.items || [];
    const chunks = [];

    const append = (buf) => chunks.push(buf);
    const appendLine = (text = '') => chunks.push(Buffer.from(prepareArabicLine(text) + '\n', 'utf8'));

    // 1. Hardware Initialization
    append(COMMANDS.INIT);
    append(COMMANDS.ENABLE_UTF8);

    // 2. Header
    append(ALIGN_MAP[header.align || 'center'] || ALIGN_MAP.center);
    if (header.title_size === 'double') {
      append(SIZE_MAP.double);
    } else {
      append(SIZE_MAP.normal);
    }
    append(COMMANDS.BOLD_ON);
    appendLine(header.title || '*** تكت التشغيل / الباريستا ***');
    append(SIZE_MAP.normal);
    append(COMMANDS.BOLD_OFF);
    appendLine('==========================================');

    // 3. Order Reference & Table
    append(ALIGN_MAP.left);
    const orderNo = payload.order_number || payload.order_id || job.uuid?.substring(0, 8) || 'N/A';
    append(SIZE_MAP.double);
    append(COMMANDS.BOLD_ON);
    appendLine(`طلب #${orderNo}`);

    if (body.show_table && (payload.table || payload.table_number)) {
      appendLine(`طاولة: ${payload.table || payload.table_number}`);
    }
    if (body.show_order_type && (payload.type || payload.order_type)) {
      appendLine(`النوع: ${payload.type || payload.order_type}`);
    }
    append(SIZE_MAP.normal);
    append(COMMANDS.BOLD_OFF);

    const timeStr = payload.time || new Date().toLocaleTimeString();
    appendLine(`الوقت: ${timeStr}`);
    appendLine('==========================================');

    // 4. Items List with Configurable Font Size
    const itemFontSize = body.item_font_size === 'double' ? SIZE_MAP.double_height : SIZE_MAP.normal;

    if (items.length > 0) {
      items.forEach((item, index) => {
        const qty = item.quantity || item.qty || 1;
        const name = item.name || item.title || 'صنف';

        append(itemFontSize);
        append(COMMANDS.BOLD_ON);
        appendLine(`[ ${qty}x ] ${name}`);
        append(SIZE_MAP.normal);
        append(COMMANDS.BOLD_OFF);

        if (body.show_notes && (item.notes || item.customization || item.modifiers)) {
          const notes = item.notes || item.customization || item.modifiers;
          append(COMMANDS.BOLD_ON);
          appendLine(`   >> ملاحظات: ${notes}`);
          append(COMMANDS.BOLD_OFF);
        }

        if (index < items.length - 1) {
          appendLine('- - - - - - - - - - - - - - - - - - - - - ');
        }
      });
    } else {
      append(itemFontSize);
      appendLine('1x صنف تشغيل');
      append(SIZE_MAP.normal);
    }

    appendLine('==========================================');

    // 5. Special Kitchen Notes
    if (payload.kitchen_notes) {
      append(COMMANDS.BOLD_ON);
      appendLine(`تعليمات خاصة: ${payload.kitchen_notes}`);
      append(COMMANDS.BOLD_OFF);
      appendLine('==========================================');
    }

    append(COMMANDS.FEED_5_LINES);

    // 6. Paper Cut
    if (footer.show_cut !== false) {
      append(COMMANDS.PAPER_CUT_PARTIAL);
    }

    return Buffer.concat(chunks);
  }

  /**
   * Helper utility to space-pad two strings across a 42-column line.
   */
  formatLineColumns(leftText, rightText, maxColumns = 42) {
    const shapedLeft = prepareArabicLine(leftText);
    const shapedRight = prepareArabicLine(rightText);
    const leftLen = shapedLeft.length;
    const rightLen = shapedRight.length;
    const spacesNeeded = maxColumns - (leftLen + rightLen);

    if (spacesNeeded <= 0) {
      return `${shapedLeft.substring(0, maxColumns - rightLen - 1)} ${shapedRight}`;
    }

    return shapedLeft + ' '.repeat(spacesNeeded) + shapedRight;
  }
}

module.exports = new PrinterDispatcher();
