/**
 * printHistory.js
 * 
 * Persistent Print Jobs History Manager for Desktop POS Print Agent.
 * 
 * Features:
 * 1. In-Memory Circular Buffer (Max 100 jobs) with persistent JSON backing.
 * 2. Windows Service Safe: Resolves storage path relative to process.execPath.
 * 3. 1-Click Reprint Support: Preserves full job payloads for re-dispatching.
 * 4. Rich Metadata: Order number, cashier, table, items summary, totals,
 *    and per-printer TCP dispatch statuses.
 */

const fs = require('fs');
const path = require('path');

const BASE_DIR = process.pkg ? path.dirname(process.execPath) : __dirname;
const HISTORY_FILE = path.join(BASE_DIR, 'history.json');
const MAX_HISTORY = 100;

class PrintHistory {
  constructor() {
    this.jobs = [];
    this.loadHistory();
  }

  loadHistory() {
    try {
      if (fs.existsSync(HISTORY_FILE)) {
        const raw = fs.readFileSync(HISTORY_FILE, 'utf8');
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
          this.jobs = parsed.slice(0, MAX_HISTORY);
          return;
        }
      }
    } catch (err) {
      console.warn(`[PrintHistory] تعذر قراءة سجل الطباعة السابق: ${err.message}`);
    }
    this.jobs = [];
  }

  saveHistory() {
    try {
      const serialized = JSON.stringify(this.jobs.slice(0, MAX_HISTORY), null, 2);
      fs.writeFileSync(HISTORY_FILE, serialized, 'utf8');
    } catch (err) {
      console.warn(`[PrintHistory] تعذر حفظ سجل الطباعة: ${err.message}`);
    }
  }

  /**
   * Adds or updates a job record in history.
   */
  recordJob({ job, dispatchResult, status = 'printed', source = 'reverb', error = null }) {
    const payload = job.payload || {};
    const items = payload.items || [];
    const jobUuid = job.uuid || `job-${Date.now()}`;
    const orderNo = payload.order_number || payload.order_id || jobUuid.substring(0, 8);

    // Build human-friendly items summary
    const itemsSummary = items.map(it => {
      const q = it.quantity || it.qty || 1;
      const n = it.name || it.title || 'صنف';
      return `${q}x ${n}`;
    }).join(', ') || 'طلب بدون تفاصيل أصناف';

    // Extract per-printer dispatch results
    const printersStatus = [];
    if (dispatchResult && dispatchResult.results) {
      Object.entries(dispatchResult.results).forEach(([pId, r]) => {
        printersStatus.push({
          id: pId,
          name: r.printer_name,
          host: r.host,
          port: r.port,
          success: r.success,
          latencyMs: r.latencyMs,
          error: r.error
        });
      });
    }

    const record = {
      id: jobUuid,
      order_number: String(orderNo),
      type: job.type || 'cashier',
      source: source, // 'reverb' | 'test_receipt' | 'reprint' | 'manual'
      status: status, // 'printed' | 'failed' | 'partial'
      timestamp: new Date().toISOString(),
      formatted_time: new Date().toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
      formatted_date: new Date().toLocaleDateString('ar-EG'),
      table: payload.table || payload.table_number || payload.type || 'صالة',
      cashier: payload.cashier_name || payload.server || 'كاشير',
      total: Number(payload.total || payload.subtotal || 0).toFixed(2),
      items_count: items.length,
      items_summary: itemsSummary,
      printers: printersStatus,
      raw_job: job,
      error: error
    };

    // Prepend to top of list
    const existingIdx = this.jobs.findIndex(j => j.id === jobUuid);
    if (existingIdx >= 0) {
      this.jobs[existingIdx] = record;
    } else {
      this.jobs.unshift(record);
      if (this.jobs.length > MAX_HISTORY) {
        this.jobs.pop();
      }
    }

    this.saveHistory();
    return record;
  }

  getJobs(filters = {}) {
    let result = [...this.jobs];

    if (filters.status && filters.status !== 'all') {
      result = result.filter(j => j.status === filters.status);
    }
    if (filters.type && filters.type !== 'all') {
      result = result.filter(j => j.type === filters.type);
    }
    if (filters.search) {
      const q = filters.search.toLowerCase();
      result = result.filter(j => 
        j.order_number.toLowerCase().includes(q) ||
        (j.cashier && j.cashier.toLowerCase().includes(q)) ||
        (j.table && j.table.toLowerCase().includes(q)) ||
        (j.items_summary && j.items_summary.toLowerCase().includes(q))
      );
    }

    const limit = Number(filters.limit) || MAX_HISTORY;
    return result.slice(0, limit);
  }

  getJobById(id) {
    return this.jobs.find(j => j.id === id);
  }

  clearHistory() {
    this.jobs = [];
    this.saveHistory();
    return true;
  }

  getStats() {
    const total = this.jobs.length;
    const successful = this.jobs.filter(j => j.status === 'printed').length;
    const failed = this.jobs.filter(j => j.status === 'failed').length;
    return { total, successful, failed };
  }
}

module.exports = new PrintHistory();
