/**
 * receiptBitmapRenderer.js
 * 
 * High-Fidelity Arabic ESC/POS Thermal Receipt Raster Renderer.
 * 
 * Uses @napi-rs/canvas (Skia + HarfBuzz engine) to render 100% correct
 * Arabic shaped, RTL, bidirectional thermal receipts into 1-bit monochrome
 * ESC/POS raster bitmaps (GS v 0).
 */

const { createCanvas } = require('@napi-rs/canvas');

// Standard 80mm ESC/POS printable width
const PRINTER_WIDTH_DOTS = 576; // 80mm thermal paper standard (72 bytes / line)
const FONT_FAMILY = 'Arial, "Segoe UI", Tahoma, "Noto Sans Arabic", sans-serif';

/**
 * Packs 32-bit RGBA canvas pixels into a standard 1-bit ESC/POS GS v 0 raster buffer.
 */
function canvasToEscPosRaster(canvas, options = {}) {
  const width = canvas.width;
  const height = canvas.height;
  const ctx = canvas.getContext('2d');
  const imgData = ctx.getImageData(0, 0, width, height);
  const data = imgData.data;

  const widthInBytes = Math.ceil(width / 8);
  const xL = widthInBytes % 256;
  const xH = Math.floor(widthInBytes / 256);
  const yL = height % 256;
  const yH = Math.floor(height / 256);

  // GS v 0 m xL xH yL yH
  const rasterHeader = Buffer.from([0x1D, 0x76, 0x30, 0x00, xL, xH, yL, yH]);
  const rasterData = Buffer.alloc(widthInBytes * height);

  let byteIdx = 0;
  for (let y = 0; y < height; y++) {
    for (let xByte = 0; xByte < widthInBytes; xByte++) {
      let b = 0;
      for (let bit = 0; bit < 8; bit++) {
        const x = xByte * 8 + bit;
        if (x < width) {
          const idx = (y * width + x) * 4;
          const red = data[idx];
          const green = data[idx + 1];
          const blue = data[idx + 2];
          const alpha = data[idx + 3];

          // Luminance calculation
          const lum = 0.299 * red + 0.587 * green + 0.114 * blue;
          // Black pixel threshold
          if (alpha > 128 && lum < 170) {
            b |= (0x80 >> bit);
          }
        }
      }
      rasterData[byteIdx++] = b;
    }
  }

  const chunks = [];
  // 1. ESC @ (Initialize)
  chunks.push(Buffer.from([0x1B, 0x40]));

  // 2. Drawer Kick (if requested)
  if (options.drawerKick) {
    chunks.push(Buffer.from([0x1B, 0x70, 0x00, 0x19, 0xFA]));
  }

  // 3. Raster Bitmap
  chunks.push(rasterHeader);
  chunks.push(rasterData);

  // 4. Feed & Cut
  chunks.push(Buffer.from([0x1B, 0x64, 0x03])); // Feed 3 lines
  if (options.cut !== false) {
    chunks.push(Buffer.from([0x1D, 0x56, 0x41, 0x03])); // Partial cut
  }

  return Buffer.concat(chunks);
}

/**
 * Renders a Customer Receipt into a pure ESC/POS raster binary Buffer.
 */
function renderCustomerReceipt(job, config, template) {
  const tpl = template || config.templates?.cashier || {};
  const header = tpl.header || {};
  const body = tpl.body || {};
  const footer = tpl.footer || {};

  const payload = job.payload || {};
  const items = payload.items || [];

  // Estimate canvas height
  let estimatedHeight = 420;
  estimatedHeight += items.length * 50;
  if (payload.customer_name) estimatedHeight += 40;
  if (payload.customer_phone) estimatedHeight += 40;
  if (payload.invoice_number || payload.invoice) estimatedHeight += 40;
  estimatedHeight += 120; // tax and subtotal
  if (payload.payment_method) estimatedHeight += 40;
  if (footer.thank_you_message) estimatedHeight += 40;
  if (footer.wifi_pass) estimatedHeight += 40;
  estimatedHeight += 120; // margins & padding

  const width = PRINTER_WIDTH_DOTS;
  const canvas = createCanvas(width, estimatedHeight);
  const ctx = canvas.getContext('2d');

  // Background white
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, width, estimatedHeight);
  ctx.fillStyle = '#000000';

  let y = 35;
  const marginX = 20;
  const contentWidth = width - marginX * 2;
  const rightX = width - marginX;
  const leftX = marginX;
  const centerX = width / 2;

  // Helper drawing functions
  const drawCentered = (text, fontSize = 22, isBold = false) => {
    ctx.font = `${isBold ? 'bold ' : ''}${fontSize}px ${FONT_FAMILY}`;
    ctx.textAlign = 'center';
    ctx.direction = 'rtl';
    ctx.fillText(text, centerX, y);
    y += fontSize + 10;
  };

  const drawRow = (rightText, leftText, fontSize = 20, isBold = false) => {
    ctx.font = `${isBold ? 'bold ' : ''}${fontSize}px ${FONT_FAMILY}`;
    ctx.direction = 'rtl';
    ctx.textAlign = 'right';
    ctx.fillText(rightText, rightX, y);

    ctx.direction = 'ltr';
    ctx.textAlign = 'left';
    ctx.fillText(leftText, leftX, y);
    y += fontSize + 10;
  };

  const drawDivider = (pattern = '-') => {
    ctx.font = `18px ${FONT_FAMILY}`;
    ctx.textAlign = 'center';
    ctx.direction = 'ltr';
    const text = pattern === '=' ? '='.repeat(44) : '- '.repeat(22);
    ctx.fillText(text, centerX, y);
    y += 24;
  };

  // 1. Header
  const storeName = header.store_name || 'CAFE SYSTEM';
  drawCentered(storeName, 32, true);

  if (header.branch) drawCentered(header.branch, 20);
  if (header.tax_number) drawCentered(`الرقم الضريبي: ${header.tax_number}`, 18);
  if (header.phone) drawCentered(`الهاتف: ${header.phone}`, 18);

  drawDivider('-');

  // 2. Order Metadata
  const orderNo = payload.order_number || payload.order_id || job.uuid?.substring(0, 8) || 'N/A';
  const invoiceNo = payload.invoice_number || payload.invoice;
  const dateStr = payload.date || payload.created_at || new Date().toLocaleString('ar-EG');

  if (invoiceNo) {
    drawRow(`رقم الفاتورة : #${invoiceNo}`, '');
  }
  drawRow(`رقم الطلب    : #${orderNo}`, '');
  drawRow(`التاريخ      : ${dateStr}`, '');

  const tableName = payload.table_name || payload.table || payload.table_number;
  if (tableName) {
    drawRow(`الطاولة      : ${tableName}`, '', 22, true);
  }
  if (payload.customer_name) {
    drawRow(`العميل       : ${payload.customer_name}`, '');
  }
  if (payload.customer_phone) {
    drawRow(`الهاتف       : ${payload.customer_phone}`, '');
  }
  if (payload.cashier_name || payload.server) {
    drawRow(`الكاشير      : ${payload.cashier_name || payload.server}`, '');
  }
  if (payload.type) {
    const typeLabel = payload.type === 'dine_in' ? 'صالة' : (payload.type === 'takeaway' ? 'تيك أواي' : 'توصيل');
    drawRow(`نوع الطلب    : ${typeLabel}`, '');
  }

  drawDivider('-');

  // 3. Tabular Items Header
  drawRow('الكمية والصنف', 'السعر', 20, true);
  drawDivider('-');

  // 4. Line Items
  if (items.length > 0) {
    items.forEach(item => {
      const qty = item.quantity || item.qty || 1;
      const name = item.name || item.title || 'صنف';
      const price = Number(item.total || (item.price ? item.price * qty : 0)).toFixed(2);
      drawRow(`${qty}×  ${name}`, `${price} ج.م`, 22, false);

      if (item.notes || item.customization) {
        ctx.font = `italic 18px ${FONT_FAMILY}`;
        ctx.textAlign = 'right';
        ctx.direction = 'rtl';
        ctx.fillText(`   * ${item.notes || item.customization}`, rightX, y);
        y += 26;
      }
    });
  } else {
    drawRow('1× طلب خاص', `${Number(payload.total || 0).toFixed(2)} ج.م`, 22, false);
  }

  drawDivider('-');

  // 5. Totals
  const subtotal = Number(payload.subtotal || payload.total || 0).toFixed(2);
  const tax = Number(payload.tax ?? payload.tax_amount ?? 0).toFixed(2);
  const discount = Number(payload.discount || 0).toFixed(2);
  const total = Number(payload.total || subtotal).toFixed(2);

  drawRow('المجموع الفرعي:', `${subtotal} ج.م`, 20, false);

  if (Number(discount) > 0) {
    drawRow('الخصم:', `${discount} ج.م`, 20, false);
  }

  if (Number(tax) > 0) {
    const taxRate = payload.tax_rate !== undefined ? payload.tax_rate : 14;
    drawRow(`ضريبة القيمة المضافة (${taxRate}%):`, `${tax} ج.م`, 20, false);
  }

  // Grand Total Box
  y += 6;
  ctx.fillStyle = '#000000';
  ctx.fillRect(leftX, y, contentWidth, 48);
  ctx.fillStyle = '#ffffff';
  ctx.font = `bold 26px ${FONT_FAMILY}`;
  ctx.direction = 'rtl';
  ctx.textAlign = 'right';
  ctx.fillText('الإجمالي:', rightX - 12, y + 33);
  ctx.direction = 'ltr';
  ctx.textAlign = 'left';
  ctx.fillText(`${total} ج.م`, leftX + 12, y + 33);
  y += 60;

  ctx.fillStyle = '#000000';
  if (payload.payment_method) {
    const payLabel = payload.payment_method === 'cash' ? 'نقدي' : (payload.payment_method === 'card' ? 'بطاقة بنكية' : payload.payment_method);
    drawRow('طريقة الدفع:', payLabel, 20, false);
  }

  drawDivider('-');

  // 6. Footer
  if (footer.thank_you_message) {
    drawCentered(footer.thank_you_message, 20, true);
  }
  if (footer.wifi_pass) {
    drawCentered(`كلمة سر الواي فاي: ${footer.wifi_pass}`, 18, false);
  }

  y += 20;

  // Crop canvas to actual rendered content height
  const finalCanvas = createCanvas(width, y);
  const finalCtx = finalCanvas.getContext('2d');
  finalCtx.drawImage(canvas, 0, 0, width, y, 0, 0, width, y);

  return canvasToEscPosRaster(finalCanvas, {
    drawerKick: Boolean(body.show_drawer_kick),
    cut: footer.show_cut !== false
  });
}

/**
 * Renders a Barista / Kitchen Production Ticket into a pure ESC/POS raster binary Buffer.
 */
function renderKitchenTicket(job, config, template) {
  const tpl = template || config.templates?.barista || {};
  const header = tpl.header || {};
  const body = tpl.body || {};
  const footer = tpl.footer || {};

  const payload = job.payload || {};
  const items = payload.items || [];

  let estimatedHeight = 280 + items.length * 55 + 100;
  const width = PRINTER_WIDTH_DOTS;
  const canvas = createCanvas(width, estimatedHeight);
  const ctx = canvas.getContext('2d');

  ctx.fillStyle = '#ffffff';
  ctx.fillRect(0, 0, width, estimatedHeight);
  ctx.fillStyle = '#000000';

  let y = 35;
  const marginX = 20;
  const rightX = width - marginX;
  const centerX = width / 2;

  const drawCentered = (text, fontSize = 24, isBold = false) => {
    ctx.font = `${isBold ? 'bold ' : ''}${fontSize}px ${FONT_FAMILY}`;
    ctx.textAlign = 'center';
    ctx.direction = 'rtl';
    ctx.fillText(text, centerX, y);
    y += fontSize + 12;
  };

  const drawDivider = (pattern = '=') => {
    ctx.font = `20px ${FONT_FAMILY}`;
    ctx.textAlign = 'center';
    ctx.direction = 'ltr';
    const text = pattern === '=' ? '='.repeat(40) : '- '.repeat(20);
    ctx.fillText(text, centerX, y);
    y += 26;
  };

  // 1. Header
  const title = header.title || '*** تكت التشغيل / الباريستا ***';
  drawCentered(title, 28, true);
  drawDivider('=');

  // 2. Order Reference & Details (Large Bold)
  const orderNo = payload.order_number || payload.order_id || job.uuid?.substring(0, 8) || 'N/A';
  drawCentered(`طلب #${orderNo}`, 32, true);

  const kTableName = payload.table_name || payload.table || payload.table_number;
  if (kTableName) {
    drawCentered(`طاولة: ${kTableName}`, 26, true);
  }
  if (payload.is_addon) {
    drawCentered('*** أصناف إضافية للطاولة ***', 22, true);
  }
  if (payload.type || payload.order_type) {
    const typeLabel = (payload.type || payload.order_type) === 'dine_in' ? 'صالة' : ((payload.type || payload.order_type) === 'takeaway' ? 'تيك أواي' : 'توصيل');
    drawCentered(`النوع: ${typeLabel}`, 22, false);
  }

  const timeStr = payload.time || payload.created_at || new Date().toLocaleTimeString('ar-EG');
  drawCentered(`الوقت: ${timeStr}`, 20, false);
  drawDivider('=');

  // 3. Items List with Large Legible Font
  if (items.length > 0) {
    items.forEach((item, index) => {
      const qty = item.quantity || item.qty || 1;
      const name = item.name || item.title || 'صنف';

      ctx.font = `bold 26px ${FONT_FAMILY}`;
      ctx.textAlign = 'right';
      ctx.direction = 'rtl';
      ctx.fillText(`[ ${qty}× ]  ${name}`, rightX, y);
      y += 34;

      if (item.notes || item.customization) {
        ctx.font = `italic 20px ${FONT_FAMILY}`;
        ctx.fillText(`     ملاحظات: ${item.notes || item.customization}`, rightX, y);
        y += 28;
      }
      y += 8;
    });
  }

  drawDivider('=');
  y += 20;

  // Crop canvas
  const finalCanvas = createCanvas(width, y);
  const finalCtx = finalCanvas.getContext('2d');
  finalCtx.drawImage(canvas, 0, 0, width, y, 0, 0, width, y);

  return canvasToEscPosRaster(finalCanvas, {
    drawerKick: false,
    cut: footer.show_cut !== false
  });
}

module.exports = {
  renderCustomerReceipt,
  renderKitchenTicket,
  canvasToEscPosRaster
};
