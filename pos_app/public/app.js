/**
 * public/app.js
 * 
 * Dashboard & Live Receipt Editor Client Controller (Egyptian Arabic UI).
 * Handles multi-printer dynamic management, real-time 80mm paper rendering,
 * template persistence, socket probes, and OTA update checks.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Global State
  let activeConfig = null;
  let activeTemplates = null;
  let currentEditingTemplateType = 'cashier'; // 'cashier' | 'barista'
  let pollInterval = null;

  // Header & Status Elements
  const wsPingRing = document.getElementById('wsPingRing');
  const wsStatusDot = document.getElementById('wsStatusDot');
  const wsStatusText = document.getElementById('wsStatusText');
  const backendStatusDot = document.getElementById('backendStatusDot');
  const backendStatusText = document.getElementById('backendStatusText');
  const appVersionBadge = document.getElementById('appVersionBadge');

  const statDeviceUuid = document.getElementById('statDeviceUuid');
  const statChannelName = document.getElementById('statChannelName');
  const statWsState = document.getElementById('statWsState');
  const statWsHost = document.getElementById('statWsHost');
  const statActiveRolesCount = document.getElementById('statActiveRolesCount');
  const statActiveRolesBadges = document.getElementById('statActiveRolesBadges');
  const statUpdaterText = document.getElementById('statUpdaterText');

  // Navigation Tabs
  const tabBtnDashboard = document.getElementById('tabBtnDashboard');
  const tabBtnHistory = document.getElementById('tabBtnHistory');
  const historyTabBadge = document.getElementById('historyTabBadge');
  const tabBtnTemplateEditor = document.getElementById('tabBtnTemplateEditor');
  const sectionDashboard = document.getElementById('sectionDashboard');
  const sectionHistory = document.getElementById('sectionHistory');
  const sectionTemplateEditor = document.getElementById('sectionTemplateEditor');

  // Print History Elements
  const btnRefreshHistory = document.getElementById('btnRefreshHistory');
  const btnClearHistory = document.getElementById('btnClearHistory');
  const historyRefreshIcon = document.getElementById('historyRefreshIcon');
  const histStatTotal = document.getElementById('histStatTotal');
  const histStatSuccess = document.getElementById('histStatSuccess');
  const histStatFailed = document.getElementById('histStatFailed');
  const histSearchInput = document.getElementById('histSearchInput');
  const histRoleFilter = document.getElementById('histRoleFilter');
  const histStatusFilter = document.getElementById('histStatusFilter');
  const histTableBody = document.getElementById('histTableBody');
  const histEmptyState = document.getElementById('histEmptyState');

  // Job Details Modal
  const jobDetailsModal = document.getElementById('jobDetailsModal');
  const jobModalOrderBadge = document.getElementById('jobModalOrderBadge');
  const btnCloseJobModal = document.getElementById('btnCloseJobModal');
  const btnJobModalCloseBottom = document.getElementById('btnJobModalCloseBottom');
  const btnJobModalReprint = document.getElementById('btnJobModalReprint');
  const jobModalBody = document.getElementById('jobModalBody');
  let currentActiveJobModalId = null;
  let cachedHistoryList = [];

  // Multi-Printer Elements
  const printersListContainer = document.getElementById('printersListContainer');
  const btnAddNewPrinter = document.getElementById('btnAddNewPrinter');
  const btnDiscoverPrinters = document.getElementById('btnDiscoverPrinters');
  const discoverBtnIcon = document.getElementById('discoverBtnIcon');
  const discoveredPrintersBanner = document.getElementById('discoveredPrintersBanner');
  const discoveredChipsContainer = document.getElementById('discoveredChipsContainer');
  const btnDismissDiscoveredBanner = document.getElementById('btnDismissDiscoveredBanner');

  const printerModal = document.getElementById('printerModal');
  const btnClosePrinterModal = document.getElementById('btnClosePrinterModal');
  const btnCancelPrinterModal = document.getElementById('btnCancelPrinterModal');
  const printerForm = document.getElementById('printerForm');
  const modalPrinterTitle = document.getElementById('modalPrinterTitle');
  const modalPrinterId = document.getElementById('modalPrinterId');
  const modalDiscoveredSelect = document.getElementById('modalDiscoveredSelect');
  const btnRefreshDiscoveredInModal = document.getElementById('btnRefreshDiscoveredInModal');
  const modalRefreshSpinner = document.getElementById('modalRefreshSpinner');
  const modalDiscoveredHint = document.getElementById('modalDiscoveredHint');
  const modalPrinterName = document.getElementById('modalPrinterName');
  const modalPrinterRole = document.getElementById('modalPrinterRole');
  const modalPrinterHost = document.getElementById('modalPrinterHost');
  const modalPrinterPort = document.getElementById('modalPrinterPort');
  const manualNetworkAccordion = document.getElementById('manualNetworkAccordion');
  const modalPrinterEnabled = document.getElementById('modalPrinterEnabled');

  let discoveredPrinters = [];

  // General Settings Form
  const generalConfigForm = document.getElementById('generalConfigForm');
  const inputDeviceUuid = document.getElementById('inputDeviceUuid');
  const inputBackendUrl = document.getElementById('inputBackendUrl');
  const inputReverbHost = document.getElementById('inputReverbHost');
  const inputReverbPort = document.getElementById('inputReverbPort');
  const inputReverbScheme = document.getElementById('inputReverbScheme');
  const inputReverbKey = document.getElementById('inputReverbKey');

  // Template Editor Elements
  const btnSelectCashierTpl = document.getElementById('btnSelectCashierTpl');
  const btnSelectBaristaTpl = document.getElementById('btnSelectBaristaTpl');
  const formCashierTemplate = document.getElementById('formCashierTemplate');
  const formBaristaTemplate = document.getElementById('formBaristaTemplate');
  const previewCashierContent = document.getElementById('previewCashierContent');
  const previewBaristaContent = document.getElementById('previewBaristaContent');

  // Template Form Inputs: Cashier
  const tplCashierStoreName = document.getElementById('tplCashierStoreName');
  const tplCashierBranch = document.getElementById('tplCashierBranch');
  const tplCashierTaxNo = document.getElementById('tplCashierTaxNo');
  const tplCashierPhone = document.getElementById('tplCashierPhone');
  const tplCashierAlign = document.getElementById('tplCashierAlign');
  const tplCashierStoreSize = document.getElementById('tplCashierStoreSize');
  const tplCashierStoreWeight = document.getElementById('tplCashierStoreWeight');
  const tplCashierItemSize = document.getElementById('tplCashierItemSize');
  const tplCashierItemWeight = document.getElementById('tplCashierItemWeight');
  const tplCashierGeneralWeight = document.getElementById('tplCashierGeneralWeight');
  const tplCashierShowTable = document.getElementById('tplCashierShowTable');
  const tplCashierShowServer = document.getElementById('tplCashierShowServer');
  const tplCashierShowTax = document.getElementById('tplCashierShowTax');
  const tplCashierDrawerKick = document.getElementById('tplCashierDrawerKick');
  const tplCashierThankYou = document.getElementById('tplCashierThankYou');
  const tplCashierWifi = document.getElementById('tplCashierWifi');
  const tplCashierShowCut = document.getElementById('tplCashierShowCut');

  // Template Form Inputs: Barista
  const tplBaristaTitle = document.getElementById('tplBaristaTitle');
  const tplBaristaTitleSize = document.getElementById('tplBaristaTitleSize');
  const tplBaristaTitleWeight = document.getElementById('tplBaristaTitleWeight');
  const tplBaristaShowTable = document.getElementById('tplBaristaShowTable');
  const tplBaristaShowOrderType = document.getElementById('tplBaristaShowOrderType');
  const tplBaristaShowNotes = document.getElementById('tplBaristaShowNotes');
  const tplBaristaItemSize = document.getElementById('tplBaristaItemSize');
  const tplBaristaItemWeight = document.getElementById('tplBaristaItemWeight');
  const tplBaristaNotesWeight = document.getElementById('tplBaristaNotesWeight');
  const tplBaristaShowCut = document.getElementById('tplBaristaShowCut');

  // Template Buttons
  const btnResetTemplate = document.getElementById('btnResetTemplate');
  const btnTestTemplatePrint = document.getElementById('btnTestTemplatePrint');
  const btnSaveTemplate = document.getElementById('btnSaveTemplate');
  const btnCheckUpdateManual = document.getElementById('btnCheckUpdateManual');

  // ==========================================
  // Toast Notification System (Egyptian Arabic)
  // ==========================================
  function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-3 rounded-2xl shadow-2xl border text-xs font-bold transition-all transform duration-300 -translate-x-3 opacity-0 ${
      type === 'success' ? 'bg-emerald-950/95 text-emerald-200 border-emerald-700/80 shadow-emerald-950/50' :
      type === 'error' ? 'bg-rose-950/95 text-rose-200 border-rose-700/80 shadow-rose-950/50' :
      'bg-slate-900/95 text-slate-200 border-slate-700 shadow-slate-950/50'
    }`;

    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ';
    toast.innerHTML = `<span class="w-5 h-5 rounded-full flex items-center justify-center bg-white/10">${icon}</span><span>${message}</span>`;
    container.appendChild(toast);

    requestAnimationFrame(() => {
      toast.classList.remove('-translate-x-3', 'opacity-0');
    });

    setTimeout(() => {
      toast.classList.add('opacity-0', '-translate-x-3');
      setTimeout(() => toast.remove(), 300);
    }, 4500);
  }

  // ==========================================
  // Tab Switching Logic (Dashboard, History, Templates)
  // ==========================================
  function switchTab(activeTab) {
    const activeClass = 'px-5 py-2.5 rounded-xl font-bold text-sm bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 transition flex items-center gap-2';
    const inactiveClass = 'px-5 py-2.5 rounded-xl font-bold text-sm bg-surface-850 hover:bg-surface-800 text-slate-300 hover:text-white border border-slate-800 transition flex items-center gap-2';

    if (tabBtnDashboard) tabBtnDashboard.className = (activeTab === 'dashboard') ? activeClass : inactiveClass;
    if (tabBtnHistory) tabBtnHistory.className = (activeTab === 'history') ? activeClass : inactiveClass;
    if (tabBtnTemplateEditor) tabBtnTemplateEditor.className = (activeTab === 'templates') ? activeClass : inactiveClass;

    if (sectionDashboard) sectionDashboard.classList.toggle('hidden', activeTab !== 'dashboard');
    if (sectionHistory) sectionHistory.classList.toggle('hidden', activeTab !== 'history');
    if (sectionTemplateEditor) sectionTemplateEditor.classList.toggle('hidden', activeTab !== 'templates');

    if (activeTab === 'history') {
      loadPrintHistory();
    } else if (activeTab === 'templates') {
      renderTemplateForm();
    }
  }

  if (tabBtnDashboard) tabBtnDashboard.addEventListener('click', () => switchTab('dashboard'));
  if (tabBtnHistory) tabBtnHistory.addEventListener('click', () => switchTab('history'));
  if (tabBtnTemplateEditor) tabBtnTemplateEditor.addEventListener('click', () => switchTab('templates'));

  // Template Type Switcher
  btnSelectCashierTpl.addEventListener('click', () => {
    currentEditingTemplateType = 'cashier';
    btnSelectCashierTpl.className = 'px-4 py-1.5 rounded-lg text-xs font-extrabold bg-emerald-600 text-white shadow transition';
    btnSelectBaristaTpl.className = 'px-4 py-1.5 rounded-lg text-xs font-extrabold text-slate-400 hover:text-white transition';
    formCashierTemplate.classList.remove('hidden');
    formBaristaTemplate.classList.add('hidden');
    previewCashierContent.classList.remove('hidden');
    previewBaristaContent.classList.add('hidden');
    renderTemplateForm();
  });

  btnSelectBaristaTpl.addEventListener('click', () => {
    currentEditingTemplateType = 'barista';
    btnSelectBaristaTpl.className = 'px-4 py-1.5 rounded-lg text-xs font-extrabold bg-cyan-600 text-white shadow transition';
    btnSelectCashierTpl.className = 'px-4 py-1.5 rounded-lg text-xs font-extrabold text-slate-400 hover:text-white transition';
    formCashierTemplate.classList.add('hidden');
    formBaristaTemplate.classList.remove('hidden');
    previewCashierContent.classList.add('hidden');
    previewBaristaContent.classList.remove('hidden');
    renderTemplateForm();
  });

  // ==========================================
  // Configuration & Data Fetching
  // ==========================================
  async function loadInitialData() {
    try {
      const res = await fetch('/api/config');
      const data = await res.json();
      if (data.success && data.config) {
        activeConfig = data.config;
        activeTemplates = data.config.templates;
        populateGeneralForm(activeConfig);
        renderPrintersList(activeConfig.printers);
        renderTemplateForm();
        fetchDiscoveredPrinters(false);
        loadPrintHistory();
      }
    } catch (err) {
      showToast(`فشل في قراءة الإعدادات: ${err.message}`, 'error');
    }
  }

  function populateGeneralForm(cfg) {
    inputDeviceUuid.value = cfg.device_uuid || '';
    inputBackendUrl.value = cfg.laravel_backend_url || '';
    if (cfg.app_version) appVersionBadge.textContent = `v${cfg.app_version}`;

    if (cfg.reverb) {
      inputReverbHost.value = cfg.reverb.host || '';
      inputReverbPort.value = cfg.reverb.port || 8080;
      inputReverbScheme.value = cfg.reverb.scheme || 'http';
      inputReverbKey.value = cfg.reverb.app_key || '';
    }
  }

  // General Settings Submit
  generalConfigForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
      device_uuid: inputDeviceUuid.value.trim(),
      laravel_backend_url: inputBackendUrl.value.trim(),
      reverb: {
        host: inputReverbHost.value.trim(),
        port: Number(inputReverbPort.value),
        scheme: inputReverbScheme.value,
        app_key: inputReverbKey.value.trim()
      }
    };

    try {
      const res = await fetch('/api/config', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showToast('تم حفظ إعدادات السيرفر بنجاح يا باشا!', 'success');
        activeConfig = data.config;
        pollStatus();
      } else {
        showToast(`فشل الحفظ: ${data.message}`, 'error');
      }
    } catch (err) {
      showToast(`خطأ في الشبكة: ${err.message}`, 'error');
    }
  });

  // ==========================================
  // Multi-Printer Management & Rendering
  // ==========================================
  function renderPrintersList(printers) {
    const list = Array.isArray(printers) ? printers : [];
    if (list.length === 0) {
      printersListContainer.innerHTML = `
        <div class="col-span-full py-8 text-center text-slate-500 text-xs">
          لا توجد طابعات مضافة حالياً. اضغط "إضافة طابعة جديدة" للبدء.
        </div>
      `;
      return;
    }

    const roleBadgeMap = {
      cashier: { name: 'كاشير ودرج', class: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' },
      barista: { name: 'باريستا ومشروبات', class: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30' },
      kitchen: { name: 'مطبخ ومأكولات', class: 'bg-amber-500/20 text-amber-300 border-amber-500/30' },
      all: { name: 'الكل (عام)', class: 'bg-purple-500/20 text-purple-300 border-purple-500/30' }
    };

    printersListContainer.innerHTML = list.map(p => {
      const badge = roleBadgeMap[p.role] || { name: p.role, class: 'bg-slate-700 text-slate-300 border-slate-600' };
      return `
        <div class="bg-surface-850 border border-slate-800 rounded-2xl p-4 flex flex-col justify-between gap-3 shadow-sm hover:border-slate-700 transition">
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="font-extrabold text-sm text-white">${p.name}</div>
              <div class="text-xs font-mono text-slate-400 mt-0.5">${p.host}:${p.port}</div>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded border ${badge.class}">
              ${badge.name}
            </span>
          </div>

          <div class="flex items-center justify-between pt-2 border-t border-slate-800/80 text-xs">
            <span id="printerStatusPill-${p.id}" class="text-[11px] font-bold text-slate-400">
              جاري الفحص...
            </span>

            <div class="flex items-center gap-1.5">
              <button onclick="window.probeSinglePrinter('${p.host}', ${p.port}, '${p.id}')" class="p-1.5 rounded-lg bg-surface-800 hover:bg-surface-700 text-slate-300 hover:text-emerald-400 border border-slate-700 transition" title="فحص الاتصال">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </button>
              <button onclick="window.editPrinter('${p.id}')" class="p-1.5 rounded-lg bg-surface-800 hover:bg-surface-700 text-slate-300 hover:text-white border border-slate-700 transition" title="تعديل">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
              </button>
              <button onclick="window.deletePrinter('${p.id}')" class="p-1.5 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-400 border border-rose-800/40 transition" title="حذف">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  // ==========================================
  // Automated Printer Discovery Logic
  // ==========================================
  async function fetchDiscoveredPrinters(forceScan = false) {
    if (discoverBtnIcon) discoverBtnIcon.classList.add('animate-spin');
    if (modalRefreshSpinner) modalRefreshSpinner.classList.add('animate-spin');
    if (modalDiscoveredHint) {
      modalDiscoveredHint.textContent = 'جاري فحص الشبكة والمنافذ لاكتشاف الطابعات المتاحة...';
      modalDiscoveredHint.className = 'text-[11px] text-cyan-400 font-bold';
    }

    try {
      const url = `/api/printers/discover${forceScan ? '?refresh=true' : ''}`;
      const res = await fetch(url);
      const data = await res.json();
      if (data.success && Array.isArray(data.printers)) {
        discoveredPrinters = data.printers;
        populateDiscoveredDropdown();
        renderDiscoveredBanner();
        if (modalDiscoveredHint) {
          if (discoveredPrinters.length > 0) {
            modalDiscoveredHint.textContent = `تم اكتشاف ${discoveredPrinters.length} طابعة جاهزة في الشبكة. اختر الطابعة وسيتم تعبئة البيانات تلقائياً.`;
            modalDiscoveredHint.className = 'text-[11px] text-emerald-400 font-semibold';
          } else {
            modalDiscoveredHint.textContent = 'لم يتم العثور على طابعات جديدة في الشبكة حالياً.';
            modalDiscoveredHint.className = 'text-[11px] text-slate-400';
          }
        }
      }
    } catch (err) {
      console.warn('[Discovery] Error:', err);
      if (modalDiscoveredHint) {
        modalDiscoveredHint.textContent = `تعذر فحص الشبكة: ${err.message}`;
        modalDiscoveredHint.className = 'text-[11px] text-rose-400';
      }
    } finally {
      if (discoverBtnIcon) discoverBtnIcon.classList.remove('animate-spin');
      if (modalRefreshSpinner) modalRefreshSpinner.classList.remove('animate-spin');
    }
  }

  function populateDiscoveredDropdown(selectedId = '') {
    if (!modalDiscoveredSelect) return;
    modalDiscoveredSelect.innerHTML = '<option value="">-- اضغط لاختيار طابعة مكتشفة في الشبكة --</option>';

    if (discoveredPrinters.length === 0) {
      const opt = document.createElement('option');
      opt.value = '';
      opt.textContent = '(لا توجد طابعات مكتشفة - اضغط إعادة فحص الشبكة)';
      modalDiscoveredSelect.appendChild(opt);
      return;
    }

    const currentConfigured = activeConfig?.printers || [];

    discoveredPrinters.forEach(p => {
      const isAlreadyAdded = currentConfigured.some(cp => cp.host === p.host && Number(cp.port) === Number(p.port));
      const opt = document.createElement('option');
      opt.value = p.id;
      const statusLabel = isAlreadyAdded ? ' (مضافة بالفعل)' : ' (جاهزة للإضافة)';
      const typeLabel = p.type === 'system_spooler' ? 'طابعة نظام' : `${p.host}:${p.port}`;
      opt.textContent = `${p.name} - [${typeLabel}]${statusLabel}`;
      if (p.id === selectedId) opt.selected = true;
      modalDiscoveredSelect.appendChild(opt);
    });
  }

  function renderDiscoveredBanner() {
    if (!discoveredPrintersBanner || !discoveredChipsContainer) return;
    const currentConfigured = activeConfig?.printers || [];
    const unadded = discoveredPrinters.filter(dp => 
      !currentConfigured.some(cp => cp.host === dp.host && Number(cp.port) === Number(dp.port))
    );

    if (unadded.length === 0) {
      discoveredPrintersBanner.classList.add('hidden');
      return;
    }

    discoveredPrintersBanner.classList.remove('hidden');
    discoveredChipsContainer.innerHTML = unadded.map(dp => `
      <div class="flex items-center justify-between gap-3 bg-surface-900 border border-cyan-700/40 rounded-xl px-3 py-2 text-xs text-slate-200">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
          <span class="font-bold text-white">${dp.name}</span>
          <span class="text-slate-400 font-mono text-[11px]">${dp.host}:${dp.port}</span>
        </div>
        <button onclick="window.quickAddDiscovered('${dp.id}')" class="px-2.5 py-1 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-[11px] transition">
          + إضافة سريعة
        </button>
      </div>
    `).join('');
  }

  if (btnDismissDiscoveredBanner) {
    btnDismissDiscoveredBanner.addEventListener('click', () => {
      discoveredPrintersBanner.classList.add('hidden');
    });
  }

  if (btnDiscoverPrinters) {
    btnDiscoverPrinters.addEventListener('click', async () => {
      showToast('جاري فحص الشبكة والمنافذ بحثاً عن طابعات جديدة...', 'info');
      await fetchDiscoveredPrinters(true);
      showToast(`اكتمل الفحص: تم العثور على ${discoveredPrinters.length} طابعة متاحة.`, 'success');
    });
  }

  if (btnRefreshDiscoveredInModal) {
    btnRefreshDiscoveredInModal.addEventListener('click', async () => {
      await fetchDiscoveredPrinters(true);
    });
  }

  if (modalDiscoveredSelect) {
    modalDiscoveredSelect.addEventListener('change', () => {
      const selectedId = modalDiscoveredSelect.value;
      if (!selectedId) return;
      const p = discoveredPrinters.find(dp => dp.id === selectedId);
      if (!p) return;

      modalPrinterName.value = p.name;
      modalPrinterRole.value = p.suggested_role || 'cashier';
      modalPrinterHost.value = p.host || '127.0.0.1';
      modalPrinterPort.value = p.port || 9100;
      if (manualNetworkAccordion) manualNetworkAccordion.removeAttribute('open');

      if (modalDiscoveredHint) {
        modalDiscoveredHint.textContent = `تم اختيار "${p.name}" (${p.host}:${p.port}) تلقائياً بنجاح! اضغط حفظ لإتمام الإضافة.`;
        modalDiscoveredHint.className = 'text-[11px] text-emerald-400 font-bold';
      }
    });
  }

  window.quickAddDiscovered = (id) => {
    const p = discoveredPrinters.find(dp => dp.id === id);
    if (!p) return;
    modalPrinterTitle.textContent = 'إضافة طابعة حرارية جديدة';
    modalPrinterId.value = `printer-${Date.now()}`;
    modalPrinterName.value = p.name;
    modalPrinterRole.value = p.suggested_role || 'cashier';
    modalPrinterHost.value = p.host || '127.0.0.1';
    modalPrinterPort.value = p.port || 9100;
    modalPrinterEnabled.checked = true;
    if (manualNetworkAccordion) manualNetworkAccordion.removeAttribute('open');
    populateDiscoveredDropdown(p.id);
    if (modalDiscoveredHint) {
      modalDiscoveredHint.textContent = `تم اختيار "${p.name}" تلقائياً! اضغط "حفظ الطابعة".`;
      modalDiscoveredHint.className = 'text-[11px] text-emerald-400 font-bold';
    }
    printerModal.classList.remove('hidden');
  };

  // Modal Handlers
  btnAddNewPrinter.addEventListener('click', () => {
    modalPrinterTitle.textContent = 'إضافة طابعة حرارية جديدة';
    modalPrinterId.value = `printer-${Date.now()}`;
    modalPrinterName.value = '';
    modalPrinterRole.value = 'cashier';
    modalPrinterHost.value = '192.168.1.101';
    modalPrinterPort.value = 9100;
    modalPrinterEnabled.checked = true;
    if (manualNetworkAccordion) manualNetworkAccordion.removeAttribute('open');

    // Check if there is an unadded discovered printer to auto-select
    const currentConfigured = activeConfig?.printers || [];
    const firstUnadded = discoveredPrinters.find(dp => 
      !currentConfigured.some(cp => cp.host === dp.host && Number(cp.port) === Number(dp.port))
    );

    if (firstUnadded) {
      populateDiscoveredDropdown(firstUnadded.id);
      modalPrinterName.value = firstUnadded.name;
      modalPrinterRole.value = firstUnadded.suggested_role || 'cashier';
      modalPrinterHost.value = firstUnadded.host || '127.0.0.1';
      modalPrinterPort.value = firstUnadded.port || 9100;
      if (modalDiscoveredHint) {
        modalDiscoveredHint.textContent = `تم اكتشاف واختيار "${firstUnadded.name}" تلقائياً! اضغط حفظ لإضافتها مباشرة.`;
        modalDiscoveredHint.className = 'text-[11px] text-emerald-400 font-bold';
      }
    } else {
      populateDiscoveredDropdown('');
      if (modalDiscoveredHint) {
        modalDiscoveredHint.textContent = 'اختر أي طابعة من القائمة المكتشفة أو أدخل البيانات يدوياً.';
        modalDiscoveredHint.className = 'text-[11px] text-slate-400';
      }
    }

    printerModal.classList.remove('hidden');

    if (discoveredPrinters.length === 0) {
      fetchDiscoveredPrinters(false);
    }
  });

  btnClosePrinterModal.addEventListener('click', () => printerModal.classList.add('hidden'));
  btnCancelPrinterModal.addEventListener('click', () => printerModal.classList.add('hidden'));

  printerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pId = modalPrinterId.value;
    const printers = activeConfig.printers ? [...activeConfig.printers] : [];

    const printerObj = {
      id: pId,
      name: modalPrinterName.value.trim(),
      role: modalPrinterRole.value,
      host: modalPrinterHost.value.trim(),
      port: Number(modalPrinterPort.value),
      enabled: modalPrinterEnabled.checked,
      timeout_ms: 4000
    };

    const existingIndex = printers.findIndex(p => p.id === pId);
    if (existingIndex >= 0) {
      printers[existingIndex] = printerObj;
    } else {
      printers.push(printerObj);
    }

    try {
      const res = await fetch('/api/printers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ printers })
      });
      const data = await res.json();
      if (data.success) {
        showToast('تم حفظ بيانات الطابعة بنجاح.', 'success');
        activeConfig.printers = data.printers;
        renderPrintersList(data.printers);
        renderDiscoveredBanner();
        printerModal.classList.add('hidden');
        pollStatus();
      }
    } catch (err) {
      showToast(`فشل حفظ الطابعة: ${err.message}`, 'error');
    }
  });

  // Global window functions for inline row buttons
  window.editPrinter = (id) => {
    const p = activeConfig.printers.find(item => item.id === id);
    if (!p) return;
    modalPrinterTitle.textContent = 'تعديل بيانات الطابعة';
    modalPrinterId.value = p.id;
    modalPrinterName.value = p.name;
    modalPrinterRole.value = p.role;
    modalPrinterHost.value = p.host;
    modalPrinterPort.value = p.port;
    modalPrinterEnabled.checked = Boolean(p.enabled);
    if (manualNetworkAccordion) manualNetworkAccordion.setAttribute('open', 'true');

    const matchingDiscovered = discoveredPrinters.find(dp => dp.host === p.host && Number(dp.port) === Number(p.port));
    populateDiscoveredDropdown(matchingDiscovered ? matchingDiscovered.id : '');
    if (modalDiscoveredHint) {
      modalDiscoveredHint.textContent = matchingDiscovered 
        ? `طابعة متصلة ومكتشفة في الشبكة: ${matchingDiscovered.name}` 
        : 'طابعة مخصصة حالياً.';
      modalDiscoveredHint.className = 'text-[11px] text-slate-400';
    }

    printerModal.classList.remove('hidden');
  };

  window.deletePrinter = async (id) => {
    if (!confirm('هل أنت متأكد من حذف هذه الطابعة من النظام؟')) return;
    const printers = activeConfig.printers.filter(p => p.id !== id);
    try {
      const res = await fetch('/api/printers', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ printers })
      });
      const data = await res.json();
      if (data.success) {
        showToast('تم حذف الطابعة.', 'info');
        activeConfig.printers = data.printers;
        renderPrintersList(data.printers);
        renderDiscoveredBanner();
        pollStatus();
      }
    } catch (err) {
      showToast(`فشل الحذف: ${err.message}`, 'error');
    }
  };

  window.probeSinglePrinter = async (host, port, id) => {
    const pill = document.getElementById(`printerStatusPill-${id}`);
    if (pill) pill.innerHTML = '<span class="text-amber-400">جاري الفحص...</span>';
    try {
      const res = await fetch('/api/printers/test-connection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ host, port })
      });
      const data = await res.json();
      if (data.result?.online) {
        showToast(`الطابعة متصلة (${data.result.latencyMs}ms)`, 'success');
        if (pill) pill.innerHTML = `<span class="text-emerald-400">● أونلاين (${data.result.latencyMs}ms)</span>`;
      } else {
        showToast(`الطابعة غير متصلة: ${data.result?.error || 'Unreachable'}`, 'error');
        if (pill) pill.innerHTML = '<span class="text-rose-400">● أوفلاين</span>';
      }
    } catch (err) {
      showToast(`فشل الفحص: ${err.message}`, 'error');
    }
  };

  // ==========================================
  // Visual Receipt Template Editor & Live Preview
  // ==========================================

  // Sizing and Weight class mapping for the 80mm live paper preview
  const PREVIEW_SIZE_MAP = {
    store_name: {
      normal: 'text-sm',
      medium: 'text-base',
      large: 'text-lg',
      double: 'text-lg',
      xlarge: 'text-xl'
    },
    title: {
      normal: 'text-sm',
      medium: 'text-base',
      large: 'text-lg',
      double: 'text-lg',
      xlarge: 'text-xl'
    },
    items: {
      normal: 'text-[11px]',
      medium: 'text-xs',
      large: 'text-sm',
      double: 'text-sm',
      xlarge: 'text-base'
    }
  };

  const PREVIEW_WEIGHT_MAP = {
    normal: 'font-normal',
    medium: 'font-semibold',
    bold: 'font-bold',
    double: 'font-bold',
    extrabold: 'font-black'
  };

  function renderTemplateForm() {
    if (!activeTemplates) return;

    if (currentEditingTemplateType === 'cashier') {
      const t = activeTemplates.cashier || {};
      const h = t.header || {};
      const b = t.body || {};
      const f = t.footer || {};

      tplCashierStoreName.value = h.store_name || '';
      tplCashierBranch.value = h.branch || '';
      tplCashierTaxNo.value = h.tax_number || '';
      tplCashierPhone.value = h.phone || '';
      tplCashierAlign.value = h.align || 'center';

      tplCashierStoreSize.value = h.store_name_size || 'large';
      tplCashierStoreWeight.value = h.store_name_weight || 'extrabold';
      tplCashierItemSize.value = b.item_font_size || 'large';
      tplCashierItemWeight.value = b.item_font_weight || 'bold';
      tplCashierGeneralWeight.value = b.general_font_weight || 'medium';

      tplCashierShowTable.checked = b.show_table !== false;
      tplCashierShowServer.checked = b.show_server !== false;
      tplCashierShowTax.checked = b.show_tax_breakdown !== false;
      tplCashierDrawerKick.checked = b.show_drawer_kick !== false;

      tplCashierThankYou.value = f.thank_you_message || '';
      tplCashierWifi.value = f.wifi_pass || '';
      tplCashierShowCut.checked = f.show_cut !== false;
    } else {
      const t = activeTemplates.barista || {};
      const h = t.header || {};
      const b = t.body || {};
      const f = t.footer || {};

      tplBaristaTitle.value = h.title || '';
      tplBaristaTitleSize.value = h.title_size || 'large';
      tplBaristaTitleWeight.value = h.title_weight || 'extrabold';
      tplBaristaShowTable.checked = b.show_table !== false;
      tplBaristaShowOrderType.checked = b.show_order_type !== false;
      tplBaristaShowNotes.checked = b.show_notes !== false;
      tplBaristaItemSize.value = b.item_font_size || 'large';
      tplBaristaItemWeight.value = b.item_font_weight || 'extrabold';
      tplBaristaNotesWeight.value = b.notes_font_weight || 'bold';
      tplBaristaShowCut.checked = f.show_cut !== false;
    }

    updateLivePaperPreview();
  }

  // Reactive Event Listeners for Live Thermal Paper Updates
  const cashierInputs = [
    tplCashierStoreName, tplCashierBranch, tplCashierTaxNo, tplCashierPhone,
    tplCashierAlign, tplCashierStoreSize, tplCashierStoreWeight, tplCashierItemSize,
    tplCashierItemWeight, tplCashierGeneralWeight, tplCashierShowTable, tplCashierShowServer,
    tplCashierShowTax, tplCashierDrawerKick, tplCashierThankYou, tplCashierWifi, tplCashierShowCut
  ];
  cashierInputs.forEach(input => {
    input?.addEventListener('input', updateLivePaperPreview);
    input?.addEventListener('change', updateLivePaperPreview);
  });

  const baristaInputs = [
    tplBaristaTitle, tplBaristaTitleSize, tplBaristaTitleWeight, tplBaristaShowTable,
    tplBaristaShowOrderType, tplBaristaShowNotes, tplBaristaItemSize, tplBaristaItemWeight,
    tplBaristaNotesWeight, tplBaristaShowCut
  ];
  baristaInputs.forEach(input => {
    input?.addEventListener('input', updateLivePaperPreview);
    input?.addEventListener('change', updateLivePaperPreview);
  });

  function updateLivePaperPreview() {
    if (currentEditingTemplateType === 'cashier') {
      // Elements in paper preview
      const prevHeader = document.getElementById('prevCashierHeader');
      const prevStoreName = document.getElementById('prevCashierStoreName');
      const prevBranch = document.getElementById('prevCashierBranch');
      const prevTax = document.getElementById('prevCashierTax');
      const prevPhone = document.getElementById('prevCashierPhone');
      const prevTable = document.getElementById('prevCashierTable');
      const prevServer = document.getElementById('prevCashierServer');
      const prevTaxBreakdown = document.getElementById('prevCashierTaxBreakdown');
      const prevThankYou = document.getElementById('prevCashierThankYou');
      const prevWifi = document.getElementById('prevCashierWifi');
      const prevDrawerBadge = document.getElementById('prevCashierDrawerBadge');
      const prevCutBadge = document.getElementById('prevCashierCutBadge');

      const prevItemHeader = document.getElementById('prevCashierItemHeader');
      const prevItem1 = document.getElementById('prevCashierItem1');
      const prevItem2 = document.getElementById('prevCashierItem2');
      const prevItemNote = document.getElementById('prevCashierItemNote');

      prevHeader.style.textAlign = tplCashierAlign.value;
      prevStoreName.textContent = tplCashierStoreName.value || 'كافيه رويال';

      // 4-Level Store Name Size & Weight
      const sSizeClass = (PREVIEW_SIZE_MAP.store_name[tplCashierStoreSize.value] || 'text-lg');
      const sWeightClass = (PREVIEW_WEIGHT_MAP[tplCashierStoreWeight.value] || 'font-black');
      prevStoreName.className = `${sSizeClass} ${sWeightClass} tracking-tight`;

      prevBranch.textContent = tplCashierBranch.value;
      prevBranch.style.display = tplCashierBranch.value ? 'block' : 'none';

      prevTax.textContent = tplCashierTaxNo.value ? `الرقم الضريبي: ${tplCashierTaxNo.value}` : '';
      prevTax.style.display = tplCashierTaxNo.value ? 'block' : 'none';

      prevPhone.textContent = tplCashierPhone.value;
      prevPhone.style.display = tplCashierPhone.value ? 'block' : 'none';

      // 4-Level Items Size & Weight
      const iSizeClass = (PREVIEW_SIZE_MAP.items[tplCashierItemSize.value] || 'text-sm');
      const iWeightClass = (PREVIEW_WEIGHT_MAP[tplCashierItemWeight.value] || 'font-bold');

      if (prevItem1) prevItem1.className = `flex justify-between ${iSizeClass} ${iWeightClass}`;
      if (prevItem2) prevItem2.className = `flex justify-between ${iSizeClass} ${iWeightClass}`;
      if (prevItemHeader) prevItemHeader.className = `flex justify-between font-bold border-b border-stone-300 pb-1 ${iSizeClass}`;

      // General Weight for metadata
      const genWeightClass = (PREVIEW_WEIGHT_MAP[tplCashierGeneralWeight.value] || 'font-semibold');
      if (prevTable) {
        prevTable.style.display = tplCashierShowTable.checked ? 'block' : 'none';
        prevTable.className = `text-stone-700 ${genWeightClass}`;
      }
      if (prevServer) {
        prevServer.style.display = tplCashierShowServer.checked ? 'block' : 'none';
        prevServer.className = `text-stone-700 ${genWeightClass}`;
      }

      prevTaxBreakdown.style.display = tplCashierShowTax.checked ? 'block' : 'none';

      prevThankYou.textContent = tplCashierThankYou.value;
      prevThankYou.style.display = tplCashierThankYou.value ? 'block' : 'none';

      prevWifi.innerHTML = tplCashierWifi.value ? `واي فاي: <span class="font-mono font-bold">${tplCashierWifi.value}</span>` : '';
      prevWifi.style.display = tplCashierWifi.value ? 'block' : 'none';

      prevDrawerBadge.style.opacity = tplCashierDrawerKick.checked ? '1' : '0.2';
      prevCutBadge.style.opacity = tplCashierShowCut.checked ? '1' : '0.2';

    } else {
      const prevBaristaTitle = document.getElementById('prevBaristaTitle');
      const prevBaristaTable = document.getElementById('prevBaristaTable');
      const prevBaristaOrderType = document.getElementById('prevBaristaOrderType');
      const prevBaristaNotes = document.getElementById('prevBaristaNotes');
      const prevBaristaItem1 = document.getElementById('prevBaristaItem1');
      const prevBaristaItem2 = document.getElementById('prevBaristaItem2');
      const prevBaristaCutBadge = document.getElementById('prevBaristaCutBadge');

      prevBaristaTitle.textContent = tplBaristaTitle.value || '*** تكت باريستا ***';

      // 4-Level Title Size & Weight
      const bTitleSizeClass = (PREVIEW_SIZE_MAP.title[tplBaristaTitleSize.value] || 'text-lg');
      const bTitleWeightClass = (PREVIEW_WEIGHT_MAP[tplBaristaTitleWeight.value] || 'font-black');
      prevBaristaTitle.className = `${bTitleSizeClass} ${bTitleWeightClass}`;

      prevBaristaTable.style.display = tplBaristaShowTable.checked ? 'block' : 'none';
      prevBaristaOrderType.style.display = tplBaristaShowOrderType.checked ? 'block' : 'none';

      // 4-Level Barista Items Size & Weight
      const bItemSizeClass = (PREVIEW_SIZE_MAP.items[tplBaristaItemSize.value] || 'text-base');
      const bItemWeightClass = (PREVIEW_WEIGHT_MAP[tplBaristaItemWeight.value] || 'font-black');
      prevBaristaItem1.className = `${bItemSizeClass} ${bItemWeightClass} flex justify-between`;
      prevBaristaItem2.className = `${bItemSizeClass} ${bItemWeightClass} flex justify-between`;

      // 4-Level Barista Notes Weight
      const bNotesWeightClass = (PREVIEW_WEIGHT_MAP[tplBaristaNotesWeight.value] || 'font-bold');
      prevBaristaNotes.style.display = tplBaristaShowNotes.checked ? 'block' : 'none';
      prevBaristaNotes.className = `text-[11px] ${bNotesWeightClass} text-stone-700 pr-2`;

      prevBaristaCutBadge.style.opacity = tplBaristaShowCut.checked ? '1' : '0.2';
    }
  }

  function gatherCurrentTemplateData() {
    if (currentEditingTemplateType === 'cashier') {
      return {
        header: {
          store_name: tplCashierStoreName.value.trim(),
          branch: tplCashierBranch.value.trim(),
          tax_number: tplCashierTaxNo.value.trim(),
          phone: tplCashierPhone.value.trim(),
          align: tplCashierAlign.value,
          store_name_size: tplCashierStoreSize.value,
          store_name_weight: tplCashierStoreWeight.value
        },
        body: {
          show_table: tplCashierShowTable.checked,
          show_server: tplCashierShowServer.checked,
          show_tax_breakdown: tplCashierShowTax.checked,
          show_drawer_kick: tplCashierDrawerKick.checked,
          item_font_size: tplCashierItemSize.value,
          item_font_weight: tplCashierItemWeight.value,
          general_font_weight: tplCashierGeneralWeight.value
        },
        footer: {
          thank_you_message: tplCashierThankYou.value.trim(),
          wifi_pass: tplCashierWifi.value.trim(),
          align: tplCashierAlign.value,
          show_cut: tplCashierShowCut.checked
        }
      };
    } else {
      return {
        header: {
          title: tplBaristaTitle.value.trim(),
          align: 'center',
          title_size: tplBaristaTitleSize.value,
          title_weight: tplBaristaTitleWeight.value
        },
        body: {
          show_table: tplBaristaShowTable.checked,
          show_order_type: tplBaristaShowOrderType.checked,
          show_notes: tplBaristaShowNotes.checked,
          item_font_size: tplBaristaItemSize.value,
          item_font_weight: tplBaristaItemWeight.value,
          notes_font_weight: tplBaristaNotesWeight.value
        },
        footer: {
          show_cut: tplBaristaShowCut.checked
        }
      };
    }
  }

  // Save Template Button
  btnSaveTemplate.addEventListener('click', async () => {
    const templateData = gatherCurrentTemplateData();
    try {
      const res = await fetch('/api/templates', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: currentEditingTemplateType,
          template: templateData
        })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
        if (!activeTemplates) activeTemplates = {};
        activeTemplates[currentEditingTemplateType] = data.template;
      } else {
        showToast(`فشل حفظ التصميم: ${data.message}`, 'error');
      }
    } catch (err) {
      showToast(`خطأ أثناء الحفظ: ${err.message}`, 'error');
    }
  });

  // Reset Template Button
  btnResetTemplate.addEventListener('click', async () => {
    if (!confirm('هل تريد استعادة تصميم المصنع الافتراضي لهذا الإيصال؟')) return;
    try {
      const res = await fetch('/api/templates/reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: currentEditingTemplateType })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'info');
        activeTemplates[currentEditingTemplateType] = data.template;
        renderTemplateForm();
      }
    } catch (err) {
      showToast(`فشل استعادة القالب: ${err.message}`, 'error');
    }
  });

  // Test Print Button
  btnTestTemplatePrint.addEventListener('click', async () => {
    btnTestTemplatePrint.disabled = true;
    showToast('جاري إرسال الإيصال التجريبي للطابعة...', 'info');
    const currentTemplate = gatherCurrentTemplateData();

    try {
      const res = await fetch('/api/printers/test-template', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: currentEditingTemplateType,
          template: currentTemplate
        })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message, 'success');
      } else {
        showToast(`فشلت التجربة: ${data.message}`, 'error');
      }
    } catch (err) {
      showToast(`خطأ في الطباعة: ${err.message}`, 'error');
    } finally {
      btnTestTemplatePrint.disabled = false;
    }
  });

  // ==========================================
  // OTA Updater Manual Check
  // ==========================================
  btnCheckUpdateManual.addEventListener('click', async () => {
    btnCheckUpdateManual.disabled = true;
    showToast('جاري فحص وجود تحديثات من السيرفر...', 'info');
    try {
      const res = await fetch('/api/updater/check');
      const data = await res.json();
      if (data.result?.update_available) {
        showToast(`يوجد تحديث جديد v${data.result.latest_version}!`, 'success');
        statUpdaterText.textContent = `نسخة جديدة متاحة v${data.result.latest_version}`;
        statUpdaterText.className = 'text-base font-bold text-cyan-400';
      } else {
        showToast('مفيش تحديثات جديدة، نسختك أحدث حاجة يا باشا.', 'info');
        statUpdaterText.textContent = 'أحدث إصدار مثبت';
        statUpdaterText.className = 'text-base font-bold text-emerald-400';
      }
    } catch (err) {
      showToast(`فشل فحص التحديثات: ${err.message}`, 'error');
    } finally {
      btnCheckUpdateManual.disabled = false;
    }
  });

  // ==========================================
  // Print Jobs History Logic
  // ==========================================
  async function loadPrintHistory() {
    if (historyRefreshIcon) historyRefreshIcon.classList.add('animate-spin');

    try {
      const search = histSearchInput?.value?.trim() || '';
      const type = histRoleFilter?.value || 'all';
      const status = histStatusFilter?.value || 'all';

      const query = new URLSearchParams();
      if (search) query.append('search', search);
      if (type !== 'all') query.append('type', type);
      if (status !== 'all') query.append('status', status);

      const res = await fetch(`/api/jobs/history?${query.toString()}`);
      const data = await res.json();

      if (data.success) {
        cachedHistoryList = data.jobs || [];

        // Update KPIs
        if (histStatTotal) histStatTotal.textContent = data.stats?.total ?? cachedHistoryList.length;
        if (histStatSuccess) histStatSuccess.textContent = data.stats?.successful ?? 0;
        if (histStatFailed) histStatFailed.textContent = data.stats?.failed ?? 0;
        if (historyTabBadge) historyTabBadge.textContent = data.stats?.total ?? cachedHistoryList.length;

        renderHistoryTable(cachedHistoryList);
      }
    } catch (err) {
      console.warn('Failed to load history:', err);
      showToast(`تعذر تحميل سجل الطباعة: ${err.message}`, 'error');
    } finally {
      if (historyRefreshIcon) historyRefreshIcon.classList.remove('animate-spin');
    }
  }

  function renderHistoryTable(jobs) {
    if (!histTableBody) return;

    if (!jobs || jobs.length === 0) {
      histTableBody.innerHTML = '';
      if (histEmptyState) histEmptyState.classList.remove('hidden');
      return;
    }

    if (histEmptyState) histEmptyState.classList.add('hidden');

    const roleBadgeMap = {
      cashier: { name: 'كاشير وعميل', class: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' },
      barista: { name: 'باريستا ومطبخ', class: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30' },
      kitchen: { name: 'مطبخ ومأكولات', class: 'bg-amber-500/20 text-amber-300 border-amber-500/30' }
    };

    histTableBody.innerHTML = jobs.map(job => {
      const roleBadge = roleBadgeMap[job.type] || { name: job.type || 'عام', class: 'bg-slate-700 text-slate-300 border-slate-600' };
      const isPrinted = job.status === 'printed';
      const statusBadge = isPrinted
        ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[11px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>طُبع بنجاح</span>'
        : '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[11px] font-bold"><span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>فشل الطباعة</span>';

      // Printers target chips
      const printersBadges = (job.printers && job.printers.length > 0)
        ? job.printers.map(p => {
            const dot = p.success ? 'bg-emerald-400' : 'bg-rose-400';
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-surface-900 border border-slate-700 text-[10px] text-slate-300"><span class="w-1.5 h-1.5 rounded-full ${dot}"></span>${p.name || `${p.host}:${p.port}`}</span>`;
          }).join(' ')
        : '<span class="text-slate-500 text-[11px]">طابعات النظام</span>';

      return `
        <tr class="hover:bg-surface-850/60 transition">
          <td class="py-3.5 px-4 font-mono font-bold text-white">#${job.order_number}</td>
          <td class="py-3.5 px-4">
            <span class="inline-block px-2 py-0.5 rounded border text-[10px] font-bold ${roleBadge.class}">
              ${roleBadge.name}
            </span>
          </td>
          <td class="py-3.5 px-4 text-slate-300">
            <div class="font-medium text-white">${job.formatted_time || ''}</div>
            <div class="text-[10px] text-slate-500">${job.formatted_date || ''}</div>
          </td>
          <td class="py-3.5 px-4 text-slate-300 max-w-xs">
            <div class="truncate font-semibold text-slate-200" title="${job.items_summary || ''}">${job.items_summary || 'طلب كاشير'}</div>
            <div class="text-[10px] text-slate-400 flex items-center gap-2 mt-0.5">
              <span>الطاولة: <b class="text-slate-300 font-mono">${job.table || 'صالة'}</b></span>
              <span>&bull;</span>
              <span>الكاشير: <b class="text-slate-300">${job.cashier || 'أحمد'}</b></span>
            </div>
          </td>
          <td class="py-3.5 px-4 font-mono font-extrabold text-emerald-400 text-sm whitespace-nowrap">
            ${job.total} ج.م
          </td>
          <td class="py-3.5 px-4">
            <div class="flex flex-wrap gap-1 max-w-xs">${printersBadges}</div>
          </td>
          <td class="py-3.5 px-4 whitespace-nowrap">${statusBadge}</td>
          <td class="py-3.5 px-4 text-center whitespace-nowrap">
            <div class="inline-flex items-center gap-1.5">
              <button onclick="window.reprintJob('${job.id}')" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-bold transition" title="إعادة طباعة هذا الطلب">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>إعادة طباعة</span>
              </button>
              <button onclick="window.viewJobDetails('${job.id}')" class="p-1.5 rounded-lg bg-surface-800 hover:bg-surface-700 text-slate-300 hover:text-white border border-slate-700 transition" title="عرض تفاصيل الإيصال">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  // 1-Click Reprint
  window.reprintJob = async (jobId) => {
    try {
      showToast('جاري إرسال أمر إعادة الطباعة إلى الطابعات...', 'info');
      const res = await fetch('/api/jobs/reprint', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ jobId })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'تمت إعادة الطباعة بنجاح!', 'success');
        loadPrintHistory();
      } else {
        showToast(`فشلت إعادة الطباعة: ${data.message}`, 'error');
      }
    } catch (err) {
      showToast(`خطأ في الشبكة: ${err.message}`, 'error');
    }
  };

  // View Job Receipt Details Modal
  window.viewJobDetails = (jobId) => {
    const job = cachedHistoryList.find(j => j.id === jobId);
    if (!job) return;
    currentActiveJobModalId = jobId;

    if (jobModalOrderBadge) jobModalOrderBadge.textContent = `#${job.order_number}`;

    const items = job.raw_job?.payload?.items || [];
    const itemsHtml = (items.length > 0)
      ? items.map(it => `
          <div class="flex items-center justify-between py-1.5 border-b border-slate-800 text-xs">
            <span class="text-white font-semibold">${it.quantity || 1}x ${it.name}</span>
            <span class="font-mono text-emerald-400 font-bold">${Number(it.price || it.total || 0).toFixed(2)} ج.م</span>
          </div>
        `).join('')
      : '<div class="text-xs text-slate-500 py-2">لا توجد تفاصيل أصناف متاحة.</div>';

    const printersHtml = (job.printers && job.printers.length > 0)
      ? job.printers.map(p => `
          <div class="flex items-center justify-between bg-surface-950 p-2.5 rounded-xl border border-slate-800 text-xs">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full ${p.success ? 'bg-emerald-400' : 'bg-rose-400'}"></span>
              <span class="font-bold text-white">${p.name}</span>
              <span class="font-mono text-slate-400 text-[11px]">(${p.host}:${p.port})</span>
            </div>
            <span class="text-[11px] font-bold ${p.success ? 'text-emerald-400' : 'text-rose-400'}">
              ${p.success ? `ناجح (${p.latencyMs}ms)` : `فشل (${p.error || 'أوفلاين'})`}
            </span>
          </div>
        `).join('')
      : '<div class="text-xs text-slate-500">لم يتم تسجيل طابعات مستهدفة.</div>';

    if (jobModalBody) {
      jobModalBody.innerHTML = `
        <div class="bg-surface-950 border border-slate-800 rounded-xl p-3.5 grid grid-cols-2 gap-3 text-xs">
          <div>
            <span class="text-slate-400 block text-[11px]">نوع الإيصال:</span>
            <span class="font-bold text-white">${job.type === 'cashier' ? 'كاشير وعميل' : 'باريستا ومطبخ'}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[11px]">وقت الاستلام:</span>
            <span class="font-mono text-white">${job.formatted_date} ${job.formatted_time}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[11px]">الطاولة / الصالة:</span>
            <span class="font-bold text-white font-mono">${job.table}</span>
          </div>
          <div>
            <span class="text-slate-400 block text-[11px]">الكاشير:</span>
            <span class="font-bold text-white">${job.cashier}</span>
          </div>
        </div>

        <div class="space-y-2">
          <div class="text-xs font-bold text-slate-300">الأصناف المطلوبة:</div>
          <div class="bg-surface-950/60 border border-slate-800 rounded-xl p-3 space-y-1">
            ${itemsHtml}
            <div class="flex items-center justify-between pt-2 text-sm font-extrabold text-white">
              <span>الإجمالي الكلي:</span>
              <span class="font-mono text-emerald-400 text-base">${job.total} ج.م</span>
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <div class="text-xs font-bold text-slate-300">الطابعات التي استلمت الأمر:</div>
          <div class="space-y-1.5">
            ${printersHtml}
          </div>
        </div>
      `;
    }

    if (jobDetailsModal) jobDetailsModal.classList.remove('hidden');
  };

  if (btnCloseJobModal) btnCloseJobModal.addEventListener('click', () => jobDetailsModal?.classList.add('hidden'));
  if (btnJobModalCloseBottom) btnJobModalCloseBottom.addEventListener('click', () => jobDetailsModal?.classList.add('hidden'));

  if (btnJobModalReprint) {
    btnJobModalReprint.addEventListener('click', async () => {
      if (!currentActiveJobModalId) return;
      jobDetailsModal?.classList.add('hidden');
      await window.reprintJob(currentActiveJobModalId);
    });
  }

  // Filter & Search Event Listeners
  if (histSearchInput) {
    let searchDebounce = null;
    histSearchInput.addEventListener('input', () => {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(loadPrintHistory, 250);
    });
  }

  if (histRoleFilter) histRoleFilter.addEventListener('change', loadPrintHistory);
  if (histStatusFilter) histStatusFilter.addEventListener('change', loadPrintHistory);
  if (btnRefreshHistory) btnRefreshHistory.addEventListener('click', loadPrintHistory);

  if (btnClearHistory) {
    btnClearHistory.addEventListener('click', async () => {
      if (!confirm('هل أنت متأكد من رغبتك في تفريغ سجل عمليات الطباعة بالكامل؟')) return;
      try {
        const res = await fetch('/api/jobs/clear-history', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          showToast('تم تفريغ السجل بنجاح.', 'info');
          loadPrintHistory();
        }
      } catch (err) {
        showToast(`فشل تفريغ السجل: ${err.message}`, 'error');
      }
    });
  }

  // ==========================================
  // Live Status Polling
  // ==========================================
  async function pollStatus() {
    try {
      const res = await fetch('/api/status');
      const data = await res.json();
      if (!data.success) return;

      const { agent, websocket, backend_sync, printers, active_roles, updater } = data;

      // Station ID
      statDeviceUuid.textContent = agent.device_uuid || 'N/A';
      statChannelName.textContent = `print-agent.${agent.device_uuid || 'N/A'}`;

      // Reverb & Real-time State Pill
      const wsState = websocket.status || 'disconnected';
      const isPolling = websocket.polling_active || false;

      if (wsState === 'connected') {
        wsStatusText.textContent = 'السيستم شغال والنت تمام';
        wsStatusText.className = 'text-xs font-bold text-emerald-400';
        wsStatusDot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400';
        wsPingRing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
        statWsState.textContent = 'متصل بـ Reverb (أونلاين)';
        statWsState.className = 'text-base font-bold text-emerald-400';
      } else if (isPolling) {
        wsStatusText.textContent = 'الطباعة المباشرة نشطة (Watchdog)';
        wsStatusText.className = 'text-xs font-bold text-emerald-400';
        wsStatusDot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400';
        wsPingRing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
        statWsState.textContent = wsState === 'reconnecting' ? 'مزامنة نشطة (Reverb جاري الربط)' : 'مزامنة فورية نشطة (Watchdog)';
        statWsState.className = 'text-base font-bold text-emerald-400';
      } else if (wsState === 'connecting' || wsState === 'reconnecting') {
        wsStatusText.textContent = 'جاري الاتصال...';
        wsStatusText.className = 'text-xs font-bold text-amber-400';
        wsStatusDot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-400';
        wsPingRing.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75';
        statWsState.textContent = 'جاري إعادة المحاولة...';
        statWsState.className = 'text-base font-bold text-amber-400';
      } else {
        wsStatusText.textContent = 'غير متصل بالرسيفر';
        wsStatusText.className = 'text-xs font-bold text-rose-400';
        wsStatusDot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-400';
        wsPingRing.className = 'hidden';
        statWsState.textContent = 'غير متصل (أوفلاين)';
        statWsState.className = 'text-base font-bold text-rose-400';
      }

      if (inputReverbHost.value) {
        statWsHost.textContent = `ws://${inputReverbHost.value}:${inputReverbPort.value}`;
      }

      // Backend Sync
      if (backend_sync && backend_sync.success) {
        backendStatusText.textContent = 'متزامن مع السيرفر';
        backendStatusText.className = 'text-xs font-bold text-emerald-400';
        backendStatusDot.className = 'inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400';
      } else {
        backendStatusText.textContent = backend_sync?.error || 'السيرفر غير متصل';
        backendStatusText.className = 'text-xs font-bold text-rose-400';
        backendStatusDot.className = 'inline-flex rounded-full h-2.5 w-2.5 bg-rose-400';
      }

      // Active Roles
      const roles = active_roles || [];
      statActiveRolesCount.textContent = `${roles.length} دور جاهز`;
      const roleArabic = { cashier: 'كاشير', barista: 'باريستا', kitchen: 'مطبخ' };
      statActiveRolesBadges.innerHTML = roles.map(r => 
        `<span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-500/30">${roleArabic[r] || r}</span>`
      ).join('');

      // Dynamic Printers Health Update
      if (printers) {
        Object.entries(printers).forEach(([pId, pHealth]) => {
          const pill = document.getElementById(`printerStatusPill-${pId}`);
          if (pill) {
            if (pHealth.online) {
              pill.innerHTML = `<span class="text-emerald-400">● أونلاين (${pHealth.latencyMs}ms)</span>`;
            } else {
              pill.innerHTML = `<span class="text-rose-400">● أوفلاين (${pHealth.error || 'مفصول'})</span>`;
            }
          }
        });
      }

      // Updater Status
      if (updater?.update_available) {
        statUpdaterText.textContent = `نسخة جديدة متاحة v${updater.latest_version}`;
        statUpdaterText.className = 'text-base font-bold text-cyan-400';
      }

    } catch (err) {
      console.warn('Status poll warning:', err.message);
    }
  }

  // Bootstrap
  loadInitialData().then(() => {
    pollStatus();
    pollInterval = setInterval(pollStatus, 3000);
  });
});
