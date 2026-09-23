/**
 * virtualPrinter.js
 * 
 * Local TCP ESC/POS Thermal Printer Simulator (4 Virtual Printers).
 * 
 * Simulated Printers:
 * - Port 9101: Cashier Printer & Cash Drawer Solenoid (CASHIER)
 * - Port 9102: Barista & Beverage Printer (BARISTA)
 * - Port 9103: Kitchen & Hot Food Station (KITCHEN)
 * - Port 9104: Delivery & Floor Orders Printer (ALL / DELIVERY)
 * 
 * Features:
 * - Real-time ASCII receipt rendering in terminal
 * - Cash drawer kick pulse detection (1B 70 ...)
 * - Paper cut command detection (1D 56 ...)
 * - Multi-port concurrent TCP listening
 */

const net = require('net');

const PRINTERS = [
  { id: 'printer-1', name: 'طابعة الكاشير الرئيسية', port: 9101, role: 'CASHIER' },
  { id: 'printer-2', name: 'طابعة الباريستا والمشروبات', port: 9102, role: 'BARISTA' },
  { id: 'printer-3', name: 'طابعة المطبخ والمأكولات', port: 9103, role: 'KITCHEN' },
  { id: 'printer-4', name: 'طابعة الصالة والتوصيل (عامة)', port: 9104, role: 'ALL' }
];

function createPrinterServer(config) {
  const server = net.createServer((socket) => {
    const clientAddress = `${socket.remoteAddress}:${socket.remotePort}`;
    let bufferStream = Buffer.alloc(0);

    socket.on('data', (chunk) => {
      bufferStream = Buffer.concat([bufferStream, chunk]);
    });

    socket.on('end', () => {
      if (bufferStream.length > 0) {
        renderEscPosReceipt(config, bufferStream);
      }
    });

    socket.on('error', (err) => {
      console.error(`[${config.role}] خطأ في السوكيت من ${clientAddress}: ${err.message}`);
    });
  });

  server.listen(config.port, '0.0.0.0', () => {
    console.log(`[VirtualPrinter] ${config.name} (${config.role}) جاهزة وتستمع على البورت TCP :${config.port}`);
  });

  return server;
}

/**
 * Parses raw ESC/POS buffer and renders an ASCII visual receipt in the terminal.
 */
function renderEscPosReceipt(printer, buffer) {
  const timeStr = new Date().toLocaleTimeString();
  const banner = `=== [VIRTUAL THERMAL PRINTER: ${printer.role} (Port ${printer.port}) - ${printer.name}] ${timeStr} ===`;
  const footer = '='.repeat(banner.length);

  console.log('\n' + banner);
  console.log(`حجم البيانات المستلمة: ${buffer.length} بايت`);

  let drawerKicked = false;
  let paperCut = false;
  const textBytes = [];
  let i = 0;

  while (i < buffer.length) {
    const byte = buffer[i];

    // Check for Drawer Kick: 1B 70 m t1 t2 (5 bytes)
    if (byte === 0x1B && buffer[i + 1] === 0x70) {
      drawerKicked = true;
      i += 5;
      continue;
    }

    // Check for Paper Cut: 1D 56 m n (4 bytes) or 1D 56 m (3 bytes)
    if (byte === 0x1D && buffer[i + 1] === 0x56) {
      paperCut = true;
      const m = buffer[i + 2];
      i += (m === 0x41 || m === 0x42) ? 4 : 3;
      continue;
    }

    // Check for FS & (Enable UTF-8 / Multi-byte mode: 1C 26 - 2 bytes)
    if (byte === 0x1C && buffer[i + 1] === 0x26) {
      i += 2;
      continue;
    }

    // Check for ESC commands (Init, Align, Bold, Line Feed)
    if (byte === 0x1B) {
      const next = buffer[i + 1];
      if (next === 0x40) { // ESC @ (Init)
        i += 2;
        continue;
      }
      if (next === 0x61 || next === 0x45 || next === 0x2D || next === 0x64) {
        i += 3;
        continue;
      }
      i += 2;
      continue;
    }

    // Check for GS commands (Font size GS ! n)
    if (byte === 0x1D) {
      const next = buffer[i + 1];
      if (next === 0x21) {
        i += 3;
        continue;
      }
      i += 2;
      continue;
    }

    // Accumulate printable characters and newlines for full UTF-8 multi-byte decoding
    if (byte === 0x0A || byte >= 0x20 || byte === 0x09) {
      textBytes.push(byte);
    }

    i++;
  }

  // Decode whole accumulated buffer as valid UTF-8 string
  const textOutput = Buffer.from(textBytes).toString('utf8');

  // Display receipt output
  console.log('┌────────────────────────────────────────────┐');
  const lines = textOutput.split('\n');
  lines.forEach(line => {
    if (line.trim().length > 0) {
      const charCount = [...line].length;
      const pad = Math.max(0, 42 - charCount);
      console.log(`│ ${line}${' '.repeat(pad)} │`);
    }
  });
  console.log('└────────────────────────────────────────────┘');

  if (drawerKicked) {
    console.log('⚡ [إشارة هاردوير] تم إطلاق نبضة فتح درج الكاشير (Pin 2 Solenoid Fired)');
  }
  if (paperCut) {
    console.log('✂️  [إشارة هاردوير] تم تنفيذ أمر قطع الورقة (Partial Paper Cut Executed)');
  }

  console.log(footer + '\n');
}

// Start all 4 virtual printer servers
console.log('===========================================================');
console.log('  تشغيل محاكي الطابعات الحرارية (4 طابعات TCP افتراضية)  ');
console.log('===========================================================');

const servers = PRINTERS.map(p => createPrinterServer(p));

process.on('SIGINT', () => {
  console.log('\n[VirtualPrinter] إيقاف محاكي الطابعات...');
  servers.forEach(s => s.close());
  process.exit(0);
});
