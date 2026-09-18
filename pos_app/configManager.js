/**
 * configManager.js
 * 
 * Enterprise Configuration Manager for POS Print Agent.
 * Handles loading, validation, multi-printer dynamic routing,
 * receipt layout templates, atomic disk persistence, and factory resets.
 */

const fs = require('fs');
const path = require('path');

// Determine the persistent storage path for config.json
const CONFIG_FILE_PATH = process.env.POS_CONFIG_PATH || (
  process.pkg 
    ? path.join(path.dirname(process.execPath), 'config.json')
    : path.join(__dirname, 'config.json')
);

// Factory default receipt templates
const DEFAULT_TEMPLATES = {
  cashier: {
    header: {
      store_name: "كافيه X",
      branch: "فرع X",
      tax_number: "TR-9821389",
      phone: "01000000000",
      align: "center", // 'left' | 'center' | 'right'
      store_name_size: "double" // 'normal' | 'double'
    },
    body: {
      show_table: true,
      show_server: true,
      show_tax_breakdown: true,
      show_drawer_kick: true
    },
    footer: {
      thank_you_message: "شكراً لزيارتكم! نتشرف بكم دائماً",
      wifi_pass: "royal_guest_2026",
      align: "center",
      show_cut: true
    }
  },
  barista: {
    header: {
      title: "تكت باريستا - مشروبات",
      align: "center",
      title_size: "double"
    },
    body: {
      show_table: true,
      show_order_type: true,
      show_notes: true,
      item_font_size: "double" // 'normal' | 'double'
    },
    footer: {
      show_cut: true
    }
  }
};

// Fallback configuration defaults
const DEFAULT_CONFIG = {
  device_uuid: "pos-cashier-01",
  laravel_backend_url: "http://127.0.0.1:8000",
  reverb: {
    host: "127.0.0.1",
    port: 8080,
    scheme: "http",
    app_key: "4m1fxfohhbtyb2cxn45b"
  },
  printers: [
    {
      id: "printer-1",
      name: "طابعة الكاشير الرئيسية",
      role: "cashier", // 'cashier' | 'barista' | 'kitchen' | 'all'
      host: "127.0.0.1",
      port: 9101,
      enabled: true,
      timeout_ms: 4000
    },
    {
      id: "printer-2",
      name: "طابعة الباريستا والمشروبات",
      role: "barista",
      host: "127.0.0.1",
      port: 9102,
      enabled: true,
      timeout_ms: 4000
    },
    {
      id: "printer-3",
      name: "طابعة المطبخ والمأكولات",
      role: "kitchen",
      host: "127.0.0.1",
      port: 9103,
      enabled: true,
      timeout_ms: 4000
    },
    {
      id: "printer-4",
      name: "طابعة الصالة والتوصيل",
      role: "all",
      host: "127.0.0.1",
      port: 9104,
      enabled: true,
      timeout_ms: 4000
    }
  ],
  templates: JSON.parse(JSON.stringify(DEFAULT_TEMPLATES)),
  server: {
    port: 3210
  },
  heartbeat_interval_ms: 30000,
  app_version: "1.0.0",
  auto_update_enabled: true,
  is_configured: false
};

class ConfigManager {
  constructor() {
    this.configPath = CONFIG_FILE_PATH;
    this.currentConfig = null;
    this.init();
  }

  /**
   * Initializes configuration from disk or writes defaults if missing.
   * Also migrates legacy object-based printers schema to dynamic array.
   */
  init() {
    try {
      if (!fs.existsSync(this.configPath)) {
        console.log(`[ConfigManager] No config.json found at ${this.configPath}. Initializing with default schema.`);
        this.currentConfig = JSON.parse(JSON.stringify(DEFAULT_CONFIG));
        this.saveConfig(this.currentConfig);
      } else {
        const raw = fs.readFileSync(this.configPath, 'utf8');
        const parsed = JSON.parse(raw);
        
        // Migrate legacy printers object { cashier: {...}, barista: {...} } to dynamic array
        if (parsed.printers && !Array.isArray(parsed.printers)) {
          console.log('[ConfigManager] Migrating legacy printer configuration to dynamic multi-printer array...');
          const migratedPrinters = [];
          if (parsed.printers.cashier) {
            migratedPrinters.push({
              id: "printer-1",
              name: parsed.printers.cashier.name || "طابعة الكاشير الرئيسية",
              role: "cashier",
              host: parsed.printers.cashier.host || "127.0.0.1",
              port: Number(parsed.printers.cashier.port) || 9101,
              enabled: parsed.printers.cashier.enabled !== false,
              timeout_ms: parsed.printers.cashier.timeout_ms || 4000
            });
          }
          if (parsed.printers.barista) {
            migratedPrinters.push({
              id: "printer-2",
              name: parsed.printers.barista.name || "طابعة الباريستا والمشروبات",
              role: "barista",
              host: parsed.printers.barista.host || "127.0.0.1",
              port: Number(parsed.printers.barista.port) || 9102,
              enabled: parsed.printers.barista.enabled !== false,
              timeout_ms: parsed.printers.barista.timeout_ms || 4000
            });
          }
          parsed.printers = migratedPrinters;
        }

        // Deep merge with defaults to ensure all required fields exist
        this.currentConfig = this.deepMerge(DEFAULT_CONFIG, parsed);

        // Ensure templates exist
        if (!this.currentConfig.templates) {
          this.currentConfig.templates = JSON.parse(JSON.stringify(DEFAULT_TEMPLATES));
        }

        // Persist migrated config
        this.saveConfig(this.currentConfig);
        console.log(`[ConfigManager] Configuration loaded and validated successfully from ${this.configPath}`);
      }
    } catch (err) {
      console.error(`[ConfigManager] Error reading config file (${err.message}). Falling back to safe defaults.`);
      this.currentConfig = JSON.parse(JSON.stringify(DEFAULT_CONFIG));
    }
  }

  deepMerge(target, source) {
    const output = Object.assign({}, target);
    if (this.isObject(target) && this.isObject(source)) {
      Object.keys(source).forEach(key => {
        // Special case: arrays should be replaced, not merged by keys
        if (Array.isArray(source[key])) {
          output[key] = source[key];
        } else if (this.isObject(source[key])) {
          if (!(key in target)) {
            Object.assign(output, { [key]: source[key] });
          } else {
            output[key] = this.deepMerge(target[key], source[key]);
          }
        } else {
          Object.assign(output, { [key]: source[key] });
        }
      });
    }
    return output;
  }

  isObject(item) {
    return (item && typeof item === 'object' && !Array.isArray(item));
  }

  /**
   * Returns a copy of the active configuration.
   */
  getConfig() {
    return JSON.parse(JSON.stringify(this.currentConfig));
  }

  /**
   * Validates and updates configuration, persisting atomically to disk.
   */
  updateConfig(updates) {
    if (!updates || typeof updates !== 'object') {
      throw new Error('بيانات الإعدادات غير صالحة.');
    }

    const merged = this.deepMerge(this.currentConfig, updates);

    // Validate essential fields
    if (!merged.device_uuid || typeof merged.device_uuid !== 'string') {
      throw new Error('معرف الجهاز (Device UUID) مطلوب.');
    }
    if (!merged.laravel_backend_url || typeof merged.laravel_backend_url !== 'string') {
      throw new Error('رابط السيرفر الرئيسي (Laravel URL) مطلوب.');
    }
    if (!merged.reverb || !merged.reverb.host || !merged.reverb.app_key) {
      throw new Error('بيانات اتصال Reverb (Host & App Key) مطلوبة.');
    }

    // Validate printers array
    if (!Array.isArray(merged.printers)) {
      throw new Error('قائمة الطابعات يجب أن تكون مصفوفة صالحة.');
    }

    merged.is_configured = true;
    this.saveConfig(merged);
    this.currentConfig = merged;
    return this.getConfig();
  }

  /**
   * Updates or resets templates
   */
  updateTemplate(type, templateData) {
    if (!this.currentConfig.templates) {
      this.currentConfig.templates = JSON.parse(JSON.stringify(DEFAULT_TEMPLATES));
    }
    if (!this.currentConfig.templates[type]) {
      this.currentConfig.templates[type] = {};
    }
    this.currentConfig.templates[type] = this.deepMerge(
      DEFAULT_TEMPLATES[type] || {},
      templateData
    );
    this.saveConfig(this.currentConfig);
    return this.currentConfig.templates[type];
  }

  /**
   * Resets a specific template (cashier or barista) to factory defaults.
   */
  resetTemplateToDefault(type) {
    if (!DEFAULT_TEMPLATES[type]) {
      throw new Error(`نوع القالب غير معروف: ${type}`);
    }
    if (!this.currentConfig.templates) {
      this.currentConfig.templates = {};
    }
    this.currentConfig.templates[type] = JSON.parse(JSON.stringify(DEFAULT_TEMPLATES[type]));
    this.saveConfig(this.currentConfig);
    return this.currentConfig.templates[type];
  }

  /**
   * Replaces the printers array.
   */
  savePrinters(printersArray) {
    if (!Array.isArray(printersArray)) {
      throw new Error('قائمة الطابعات غير صالحة.');
    }
    this.currentConfig.printers = printersArray;
    this.saveConfig(this.currentConfig);
    return this.currentConfig.printers;
  }

  /**
   * Performs an atomic file write to prevent half-written corruptions.
   */
  saveConfig(data) {
    const tempPath = `${this.configPath}.tmp`;
    const serialized = JSON.stringify(data, null, 2);

    try {
      fs.writeFileSync(tempPath, serialized, 'utf8');
      fs.renameSync(tempPath, this.configPath);
    } catch (err) {
      console.error(`[ConfigManager] Error persisting config: ${err.message}`);
      fs.writeFileSync(this.configPath, serialized, 'utf8');
    }
  }

  isConfigured() {
    return Boolean(this.currentConfig && this.currentConfig.is_configured);
  }

  getPath() {
    return this.configPath;
  }

  getDefaultTemplates() {
    return JSON.parse(JSON.stringify(DEFAULT_TEMPLATES));
  }
}

module.exports = new ConfigManager();
