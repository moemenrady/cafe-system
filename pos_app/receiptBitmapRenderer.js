/**
 * receiptBitmapRenderer.js
 * 
 * High-Fidelity Arabic ESC/POS Thermal Receipt Raster Renderer.
 * 
 * Uses @napi-rs/canvas (Skia + HarfBuzz engine) to render 100% correct
 * Arabic shaped, RTL, bidirectional thermal receipts into 1-bit monochrome
 * ESC/POS raster bitmaps (GS v 0).
 * 
 * Supports 4 levels of Font Size (عادي, متوسط, كبير, ضخم)
 * and 4 levels of Font Weight / Boldness (عادي, متوسط, عريض, فائق السُمك)
 * for ultimate readability on 80mm thermal receipts.
 */

const { createCanvas } = require('@napi-rs/canvas');

// Standard 80mm ESC/POS printable width
const PRINTER_WIDTH_DOTS = 576; // 80mm thermal paper standard (72 bytes / line)
const FONT_FAMILY = 'Arial, "Segoe UI", Tahoma, "Noto Sans Arabic", sans-serif';

/**
 * 4-Level Font Sizing Map (in pixels)
 */
const FONT_SIZES = {
  store_name: {
    normal: 26,    // المستوى 1: عادي
    medium: 30,    // المستوى 2: متوسط واضح
    large: 36,     // المستوى 3: كبير بارز
    double: 36,    // توافق مع الإعدادات القديمة
    xlarge: 44     // المستوى 4: ضخم أقصى حجم
  },
  title: {
    normal: 24,    // المستوى 1: عادي
    medium: 28,    // المستوى 2: متوسط واضح
    large: 34,     // المستوى 3: كبير بارز
    double: 34,    // توافق قديم
    xlarge: 42     // المستوى 4: ضخم فائق الوضوح
  },
  items: {
    normal: 20,    // المستوى 1: عادي
    medium: 24,    // المستوى 2: متوسط واضح
    large: 28,     // المستوى 3: كبير بارز
    double: 28,    // توافق قديم
    xlarge: 34     // المستوى 4: ضخم جداً لقراءة الباريستا السريعة
  },
  body: {
    normal: 18,    // المستوى 1: عادي
    medium: 21,    // المستوى 2: متوسط
    large: 24,     // المستوى 3: كبير
    double: 24,    // توافق قديم
    xlarge: 28     // المستوى 4: ضخم
  }
};

/**
 * 4-Level Font Weight Map
 * normal (400) -> medium (600) -> bold (700) -> extrabold (900 Black)
 */
const FONT_WEIGHTS = {
  normal: '400',       // المستوى 1: عادي (Normal)
  medium: '600',       // المستوى 2: سُمك متوسط (Semi-Bold)
  bold: '700',         // المستوى 3: عريض (Bold)
  double: '700',       // توافق قديم
  extrabold: '900'     // المستوى 4: فائق السُمك (Heavy Black)
};

function getFontSize(category, val, fallback = 'large') {
  const cat = FONT_SIZES[category] || FONT_SIZES.body;
  const key = (val || fallback).toString().toLowerCase();
  return cat[key] || cat.normal || 22;
}

function getFontWeight(val, fallback = 'bold') {
  const key = (val || fallback).toString().toLowerCase();
  return FONT_WEIGHTS[key] || '700';
}

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

  const threshold = options.darkness === 'ultra' ? 210 : (options.darkness === 'high' ? 190 : 175);

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
          if (alpha > 100 && lum < threshold) {
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

  // Font Size & Weight Configurations (4 Levels)
  const storeNameSize = getFontSize('store_name', header.store_name_size, 'large');
  const storeNameWeight = getFontWeight(header.store_name_weight, 'extrabold');

  const itemSize = getFontSize('items', body.item_font_size || header.item_font_size, 'medium');
  const itemWeight = getFontWeight(body.item_font_weight || header.item_font_weight, 'bold');
  const generalWeight = getFontWeight(body.general_font_weight, 'medium');

  // Estimate canvas height dynamically
  let estimatedHeight = 600;
  estimatedHeight += items.length * (itemSize + 32);
  if (payload.customer_name) estimatedHeight += 40;
  if (payload.customer_phone) estimatedHeight += 40;
  if (payload.invoice_number || payload.invoice) estimatedHeight += 40;
  estimatedHeight += 160;

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

  // Helper drawing functions with 4-level weight handling
  const drawCentered = (text, fontSize = 22, weight = '700') => {
    ctx.font = `${weight} ${fontSize}px ${FONT_FAMILY}`;
    ctx.textAlign = 'center';
    ctx.direction = 'rtl';
    ctx.fillText(text, centerX, y);

    // Extra subpixel pass for Level 4 (900 / Extra Bold) to ensure intense thermal blackness
    if (weight === '900') {
      ctx.fillText(text, centerX + 0.6, y);
      ctx.fillText(text, centerX - 0.6, y);
    }
    y += fontSize + 10;
  };

  const drawRow = (rightText, leftText, fontSize = 20, weight = '400') => {
    ctx.font = `${weight} ${fontSize}px ${FONT_FAMILY}`;
    ctx.direction = 'rtl';
    ctx.textAlign = 'right';
    ctx.fillText(rightText, rightX, y);

    if (weight === '900') {
      ctx.fillText(rightText, rightX + 0.6, y);
      ctx.fillText(rightText, rightX - 0.6, y);
    }

    ctx.direction = 'ltr';
    ctx.textAlign = 'left';
    ctx.fillText(leftText, leftX, y);

    if (weight === '900') {
      ctx.fillText(leftText, leftX + 0.6, y);
      ctx.fillText(leftText, leftX - 0.6, y);
    }

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
  drawCentered(storeName, storeNameSize, storeNameWeight);

  if (header.branch) drawCentered(header.branch, 20, '600');
  if (header.tax_number) drawCentered(`الرقم الضريبي: ${header.tax_number}`, 18, '400');
  if (header.phone) drawCentered(`الهاتف: ${header.phone}`, 18, '400');

  drawDivider('-');

  // 2. Order Metadata
  const orderNo = payload.order_number || payload.order_id || job.uuid?.substring(0, 8) || 'N/A';
  const invoiceNo = payload.invoice_number || payload.invoice;
  const dateStr = payload.date || payload.created_at || new Date().toLocaleString('ar-EG');

  if (invoiceNo) {
    drawRow(`رقم الفاتورة : #${invoiceNo}`, '', 20, generalWeight);
  }
  drawRow(`رقم الطلب    : #${orderNo}`, '', 22, '700');
  drawRow(`التاريخ      : ${dateStr}`, '', 19, '400');

  const tableName = payload.table_name || payload.table || payload.table_number;
  if (tableName) {
    drawRow(`الطاولة      : ${tableName}`, '', 22, '700');
  }
  if (payload.customer_name) {
    drawRow(`العميل       : ${payload.customer_name}`, '', 20, generalWeight);
  }
  if (payload.customer_phone) {
    drawRow(`الهاتف       : ${payload.customer_phone}`, '', 19, '400');
  }
  if (payload.cashier_name || payload.server) {
    drawRow(`الكاشير      : ${payload.cashier_name || payload.server}`, '', 20, generalWeight);
  }
  if (payload.type) {
    const typeLabel = payload.type === 'dine_in' ? 'صالة' : (payload.type === 'takeaway' ? 'تيك أواي' : 'توصيل');
    drawRow(`نوع الطلب    : ${typeLabel}`, '', 20, '700');
  }

  drawDivider('-');

  // 3. Tabular Items Header
  drawRow('الكمية والصنف', 'السعر', Math.max(20, itemSize - 2), '700');
  drawDivider('-');

  // 4. Line Items (with configurable 4-level size & weight)
  if (items.length > 0) {
    items.forEach(item => {
      const qty = item.quantity || item.qty || 1;
      const name = item.name || item.title || 'صنف';
      const price = Number(item.total || (item.price ? item.price * qty : 0)).toFixed(2);
      drawRow(`${qty}×  ${name}`, `${price} ج.م`, itemSize, itemWeight);

      if (item.notes || item.customization) {
        ctx.font = `italic ${Math.max(16, itemSize - 5)}px ${FONT_FAMILY}`;
        ctx.textAlign = 'right';
        ctx.direction = 'rtl';
        const noteText = `   * ${item.notes || item.customization}`;
        ctx.fillText(noteText, rightX, y);
        y += itemSize + 4;
      }
    });
  } else {
    drawRow('1× طلب خاص', `${Number(payload.total || 0).toFixed(2)} ج.م`, itemSize, itemWeight);
  }

  drawDivider('-');

  // 5. Totals
  const subtotal = Number(payload.subtotal || payload.total || 0).toFixed(2);
  const tax = Number(payload.tax ?? payload.tax_amount ?? 0).toFixed(2);
  const discount = Number(payload.discount || 0).toFixed(2);
  const total = Number(payload.total || subtotal).toFixed(2);

  drawRow('المجموع الفرعي:', `${subtotal} ج.م`, 20, '400');

  if (Number(discount) > 0) {
    drawRow('الخصم:', `${discount} ج.م`, 20, '700');
  }

  if (Number(tax) > 0) {
    const taxRate = payload.tax_rate !== undefined ? payload.tax_rate : 14;
    drawRow(`ضريبة القيمة المضافة (${taxRate}%):`, `${tax} ج.م`, 20, '400');
  }

  // Grand Total Box (Prominent & Extra Bold)
  y += 6;
  ctx.fillStyle = '#000000';
  ctx.fillRect(leftX, y, contentWidth, 54);
  ctx.fillStyle = '#ffffff';
  ctx.font = `900 28px ${FONT_FAMILY}`;
  ctx.direction = 'rtl';
  ctx.textAlign = 'right';
  ctx.fillText('الإجمالي:', rightX - 14, y + 37);
  ctx.fillText('الإجمالي:', rightX - 13.4, y + 37);
  ctx.direction = 'ltr';
  ctx.textAlign = 'left';
  ctx.fillText(`${total} ج.م`, leftX + 14, y + 37);
  ctx.fillText(`${total} ج.م`, leftX + 14.6, y + 37);
  y += 68;

  ctx.fillStyle = '#000000';
  if (payload.payment_method) {
    const payLabel = payload.payment_method === 'cash' ? 'نقدي' : (payload.payment_method === 'card' ? 'بطاقة بنكية' : payload.payment_method);
    drawRow('طريقة الدفع:', payLabel, 20, '600');
  }

  drawDivider('-');

  // 6. Footer
  if (footer.thank_you_message) {
    drawCentered(footer.thank_you_message, 20, '700');
  }
  if (footer.wifi_pass) {
    drawCentered(`كلمة سر الواي فاي: ${footer.wifi_pass}`, 18, '600');
  }

  y += 20;

  // Crop canvas to actual rendered content height
  const finalCanvas = createCanvas(width, y);
  const finalCtx = finalCanvas.getContext('2d');
  finalCtx.drawImage(canvas, 0, 0, width, y, 0, 0, width, y);

  return canvasToEscPosRaster(finalCanvas, {
    drawerKick: Boolean(body.show_drawer_kick),
    cut: footer.show_cut !== false,
    darkness: itemWeight === '900' || storeNameWeight === '900' ? 'ultra' : 'high'
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

  // Font Size & Weight Configurations (4 Levels)
  const titleSize = getFontSize('title', header.title_size, 'large');
  const titleWeight = getFontWeight(header.title_weight, 'extrabold');

  const itemSize = getFontSize('items', body.item_font_size, 'large');
  const itemWeight = getFontWeight(body.item_font_weight, 'extrabold');
  const notesWeight = getFontWeight(body.notes_font_weight, 'bold');

  let estimatedHeight = 350 + items.length * (itemSize + 45) + 120;
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

  const drawCentered = (text, fontSize = 24, weight = '700') => {
    ctx.font = `${weight} ${fontSize}px ${FONT_FAMILY}`;
    ctx.textAlign = 'center';
    ctx.direction = 'rtl';
    ctx.fillText(text, centerX, y);

    if (weight === '900') {
      ctx.fillText(text, centerX + 0.6, y);
      ctx.fillText(text, centerX - 0.6, y);
    }
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
  drawCentered(title, titleSize, titleWeight);
  drawDivider('=');

  // 2. Order Reference & Details (Large Bold)
  const orderNo = payload.order_number || payload.order_id || job.uuid?.substring(0, 8) || 'N/A';
  drawCentered(`طلب #${orderNo}`, 34, '900');

  const kTableName = payload.table_name || payload.table || payload.table_number;
  if (kTableName) {
    drawCentered(`طاولة: ${kTableName}`, 28, '900');
  }
  if (payload.is_addon) {
    drawCentered('*** أصناف إضافية للطاولة ***', 24, '900');
  }
  if (payload.type || payload.order_type) {
    const typeLabel = (payload.type || payload.order_type) === 'dine_in' ? 'صالة' : ((payload.type || payload.order_type) === 'takeaway' ? 'تيك أواي' : 'توصيل');
    drawCentered(`النوع: ${typeLabel}`, 24, '700');
  }

  const timeStr = payload.time || payload.created_at || new Date().toLocaleTimeString('ar-EG');
  drawCentered(`الوقت: ${timeStr}`, 20, '400');
  drawDivider('=');

  // 3. Items List with Configurable 4-Level Size & Weight
  if (items.length > 0) {
    items.forEach((item, index) => {
      const qty = item.quantity || item.qty || 1;
      const name = item.name || item.title || 'صنف';

      ctx.font = `${itemWeight} ${itemSize}px ${FONT_FAMILY}`;
      ctx.textAlign = 'right';
      ctx.direction = 'rtl';
      const itemText = `[ ${qty}× ]  ${name}`;
      ctx.fillText(itemText, rightX, y);

      // Subpixel thickening for Level 4 (Black / 900)
      if (itemWeight === '900') {
        ctx.fillText(itemText, rightX + 0.6, y);
        ctx.fillText(itemText, rightX - 0.6, y);
        ctx.fillText(itemText, rightX, y + 0.5);
      }
      y += itemSize + 12;

      if (item.notes || item.customization) {
        const noteFontSize = Math.max(18, itemSize - 6);
        ctx.font = `${notesWeight} ${noteFontSize}px ${FONT_FAMILY}`;
        const noteText = `     ملاحظات: ${item.notes || item.customization}`;
        ctx.fillText(noteText, rightX, y);
        if (notesWeight === '900') {
          ctx.fillText(noteText, rightX + 0.6, y);
          ctx.fillText(noteText, rightX - 0.6, y);
        }
        y += noteFontSize + 10;
      }
      y += 6;
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
    cut: footer.show_cut !== false,
    darkness: itemWeight === '900' || titleWeight === '900' ? 'ultra' : 'high'
  });
}

module.exports = {
  renderCustomerReceipt,
  renderKitchenTicket,
  canvasToEscPosRaster,
  FONT_SIZES,
  FONT_WEIGHTS
};
