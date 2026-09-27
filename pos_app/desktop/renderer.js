/**
 * renderer.js
 * 
 * Desktop UI Renderer Script for Cafe Print Agent.
 * Interacts with main process via window.electronAPI.
 */

document.addEventListener('DOMContentLoaded', async () => {
  const api = window.electronAPI;
  if (!api) {
    console.error('Electron API bridge is missing from window object');
    return;
  }

  // State
  let currentConfig = null;
  let detectedPrinters = [];

  // ==========================================
  // Tab Switching
  // ==========================================
  const tabButtons = document.querySelectorAll('.tab-btn');
  const tabPanes = document.querySelectorAll('.tab-pane');

  tabButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-tab');
      tabButtons.forEach(b => b.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const targetPane = document.getElementById(targetId);
      if (targetPane) targetPane.classList.add('active');
    });
  });

  // Minimize to Tray Button
  document.getElementById('btn-minimize-tray')?.addEventListener('click', () => {
    api.minimizeToTray();
  });

  // ==========================================
  // Status & Telemetry Polling
  // ==========================================
  async function refreshStatus() {
    try {
      const status = await api.getStatus();
      if (!status || !status.success) return;

      const agent = status.agent || {};
      const ws = status.websocket || {};
      const backend = status.backend_sync || {};

      // Device & Port
      document.getElementById('device-label').textContent = agent.device_uuid || '--';
      document.getElementById('port-label').textContent = agent.port || 3210;
      document.getElementById('footer-config-path').textContent = agent.config_path || '--';

      // Reverb Badge
      const reverbDot = document.getElementById('reverb-dot');
      const reverbLabel = document.getElementById('reverb-label');
      if (ws.status === 'connected') {
        reverbDot.className = 'dot green';
        reverbLabel.textContent = 'متصل';
      } else if (ws.status === 'connecting' || ws.status === 'reconnecting') {
        reverbDot.className = 'dot yellow';
        reverbLabel.textContent = 'جاري الاتصال...';
      } else {
        reverbDot.className = 'dot red';
        reverbLabel.textContent = 'غير متصل';
      }

      // Backend Sync Badge
      const backendDot = document.getElementById('backend-dot');
      const backendLabel = document.getElementById('backend-label');
      if (backend.success) {
        backendDot.className = 'dot green';
        backendLabel.textContent = 'متزامن بنجاح';
      } else if (backend.last_sent_at) {
        backendDot.className = 'dot yellow';
        backendLabel.textContent = 'تنبيه اتصال';
      } else {
        backendDot.className = 'dot gray';
        backendLabel.textContent = 'في الانتظار';
      }

      // Telemetry Bar
      document.getElementById('telemetry-backend-url').textContent = currentConfig?.laravel_backend_url || '--';
      document.getElementById('telemetry-channel').textContent = ws.channel_name || `print-agent.${agent.device_uuid}`;
      document.getElementById('telemetry-heartbeat').textContent = backend.last_sent_at 
        ? new Date(backend.last_sent_at).toLocaleTimeString('ar-EG') 
        : 'لم يُرسل بعد';

      // Update Role Cards with Configured Printers
      updateRoleCards(status.configured_printers || []);

    } catch (err) {
      console.warn('Status poll error:', err);
    }
  }

  function updateRoleCards(printers) {
    const roles = ['cashier', 'barista', 'kitchen'];

    roles.forEach(role => {
      const p = printers.find(x => x.role === role);
      const nameEl = document.getElementById(`val-${role}-name`);
      const portEl = document.getElementById(`val-${role}-port`);
      const typeEl = document.getElementById(`val-${role}-type`);
      const chipEl = document.getElementById(`chip-${role}`);

      if (p) {
        nameEl.textContent = p.name || p.windows_printer_name || '--';
        portEl.textContent = p.port_name || (p.host ? `${p.host}:${p.port}` : 'USB');
        typeEl.textContent = p.type === 'windows' ? 'Windows Spooler (USB/محلي)' : 'TCP Socket (شبكة)';
        chipEl.className = 'status-indicator-chip ready';
        chipEl.textContent = 'جاهزة للطباعة ✓';
      } else {
        nameEl.textContent = 'غير معينة';
        portEl.textContent = '--';
        chipEl.className = 'status-indicator-chip warning';
        chipEl.textContent = 'بحاجة لتعيين ✕';
      }
    });
  }

  // ==========================================
  // Printers Enumeration & Role Assignment
  // ==========================================
  async function loadPrinters(forceScan = false) {
    const tbody = document.getElementById('printers-table-body');
    const refreshBtn = document.getElementById('btn-refresh-printers');
    if (refreshBtn) refreshBtn.disabled = true;

    try {
      const res = await api.getPrinters(forceScan);
      detectedPrinters = res.printers || [];

      // Update Select Dropdowns
      populateSelect('select-role-cashier', 'cashier');
      populateSelect('select-role-barista', 'barista');
      populateSelect('select-role-kitchen', 'kitchen');

      // Update Detected Table
      tbody.innerHTML = '';
      if (detectedPrinters.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #94a3b8;">لم يتم العثور على أي طابعات معرفة على الويندوز حالياً.</td></tr>`;
      } else {
        detectedPrinters.forEach(p => {
          const tr = document.createElement('tr');
          const isAssigned = (currentConfig?.printers || []).find(cp => 
            cp.windows_printer_name === p.name || (cp.name === p.name && cp.port_name === p.port_name)
          );

          const roleBadge = isAssigned 
            ? `<span class="badge-role ${isAssigned.role}">${translateRole(isAssigned.role)}</span>` 
            : `<span style="color:#64748b;">غير مستخدم</span>`;

          tr.innerHTML = `
            <td style="font-weight: 600;">${p.name}</td>
            <td><span class="badge-port">${p.port_name || 'USB'}</span></td>
            <td style="color: #94a3b8;">${p.driver_name || 'Generic'}</td>
            <td><span class="dot green"></span> جاهزة</td>
            <td>${roleBadge}</td>
          `;
          tbody.appendChild(tr);
        });
      }
    } catch (err) {
      console.error('Failed to load printers:', err);
    } finally {
      if (refreshBtn) refreshBtn.disabled = false;
    }
  }

  function populateSelect(selectId, role) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const currentAssigned = (currentConfig?.printers || []).find(p => p.role === role);

    select.innerHTML = `<option value="">-- اضغط للاختيار --</option>`;
    detectedPrinters.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.name;
      // Show Name and Port to uniquely identify duplicate models
      opt.textContent = `${p.name} [منفذ: ${p.port_name || 'USB'}]`;

      if (currentAssigned && (currentAssigned.windows_printer_name === p.name || currentAssigned.name === p.name)) {
        opt.selected = true;
      }
      select.appendChild(opt);
    });
  }

  function translateRole(role) {
    if (role === 'cashier') return 'كاشير';
    if (role === 'barista') return 'باريستا';
    if (role === 'kitchen') return 'مطبخ';
    return role;
  }

  // Refresh Printers Click
  document.getElementById('btn-refresh-printers')?.addEventListener('click', () => {
    loadPrinters(true);
  });

  // Save Printer Assignments
  document.getElementById('btn-save-assignments')?.addEventListener('click', async () => {
    const cashierName = document.getElementById('select-role-cashier').value;
    const baristaName = document.getElementById('select-role-barista').value;
    const kitchenName = document.getElementById('select-role-kitchen').value;
    const statusEl = document.getElementById('assign-save-status');

    statusEl.textContent = 'جاري حفظ التعيينات...';
    statusEl.style.color = '#38bdf8';

    try {
      if (cashierName) {
        const p = detectedPrinters.find(x => x.name === cashierName) || { name: cashierName };
        await api.assignPrinterRole(p, 'cashier');
      }
      if (baristaName) {
        const p = detectedPrinters.find(x => x.name === baristaName) || { name: baristaName };
        await api.assignPrinterRole(p, 'barista');
      }
      if (kitchenName) {
        const p = detectedPrinters.find(x => x.name === kitchenName) || { name: kitchenName };
        await api.assignPrinterRole(p, 'kitchen');
      }

      await loadConfig();
      await loadPrinters(false);
      await refreshStatus();

      statusEl.textContent = 'تم حفظ وتفعيل تعيينات الطابعات بنجاح!';
      statusEl.style.color = '#4ade80';
      setTimeout(() => { statusEl.textContent = ''; }, 4000);
    } catch (err) {
      statusEl.textContent = `خطأ: ${err.message}`;
      statusEl.style.color = '#ef4444';
    }
  });

  // ==========================================
  // Test Print Triggers
  // ==========================================
  async function triggerTestPrint(role, btnId) {
    const btn = document.getElementById(btnId);
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'جاري إرسال أمر الطباعة...';

    try {
      const res = await api.testPrint(role);
      if (res.success) {
        btn.innerHTML = 'تمت الطباعة بنجاح ✓';
        setTimeout(() => {
          btn.innerHTML = originalText;
          btn.disabled = false;
        }, 2500);
      } else {
        alert(`فشلت الطباعة التجريبية:\n${res.error || 'تأكد من توصيل الطابعة بالكهرباء والكمبيوتر'}`);
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    } catch (err) {
      alert(`خطأ غير متوقع: ${err.message}`);
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  }

  document.getElementById('btn-test-cashier')?.addEventListener('click', () => {
    triggerTestPrint('cashier', 'btn-test-cashier');
  });

  document.getElementById('btn-test-barista')?.addEventListener('click', () => {
    triggerTestPrint('barista', 'btn-test-barista');
  });

  document.getElementById('btn-test-kitchen')?.addEventListener('click', () => {
    triggerTestPrint('kitchen', 'btn-test-kitchen');
  });

  // ==========================================
  // System Settings Management
  // ==========================================
  async function loadConfig() {
    try {
      const res = await api.getConfig();
      if (!res || !res.success) return;
      currentConfig = res.config;

      document.getElementById('setting-device-uuid').value = currentConfig.device_uuid || '';
      document.getElementById('setting-backend-url').value = currentConfig.laravel_backend_url || '';

      const rv = currentConfig.reverb || {};
      document.getElementById('setting-reverb-host').value = rv.host || '127.0.0.1';
      document.getElementById('setting-reverb-port').value = rv.port || 8080;
      document.getElementById('setting-reverb-scheme').value = rv.scheme || 'http';
      document.getElementById('setting-reverb-key').value = rv.app_key || '';

      // Auto start
      const autoStartRes = await api.getAutoStart();
      document.getElementById('setting-autostart').checked = !!autoStartRes.enabled;
    } catch (err) {
      console.error('Error loading config:', err);
    }
  }

  document.getElementById('form-settings')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('settings-save-status');
    statusEl.textContent = 'جاري حفظ الإعدادات...';
    statusEl.style.color = '#38bdf8';

    const newConfig = {
      ...currentConfig,
      device_uuid: document.getElementById('setting-device-uuid').value.trim(),
      laravel_backend_url: document.getElementById('setting-backend-url').value.trim(),
      reverb: {
        host: document.getElementById('setting-reverb-host').value.trim(),
        port: parseInt(document.getElementById('setting-reverb-port').value, 10) || 8080,
        scheme: document.getElementById('setting-reverb-scheme').value,
        app_key: document.getElementById('setting-reverb-key').value.trim()
      }
    };

    const isAutoStart = document.getElementById('setting-autostart').checked;

    try {
      const saveRes = await api.saveConfig(newConfig);
      await api.setAutoStart(isAutoStart);

      if (saveRes.success) {
        currentConfig = saveRes.config;
        statusEl.textContent = 'تم حفظ الإعدادات وإعادة تشغيل الاتصال بنجاح!';
        statusEl.style.color = '#4ade80';
        refreshStatus();
        setTimeout(() => { statusEl.textContent = ''; }, 4000);
      } else {
        statusEl.textContent = `فشل الحفظ: ${saveRes.error}`;
        statusEl.style.color = '#ef4444';
      }
    } catch (err) {
      statusEl.textContent = `خطأ: ${err.message}`;
      statusEl.style.color = '#ef4444';
    }
  });

  document.getElementById('btn-reconnect-now')?.addEventListener('click', async () => {
    await api.reconnectReverb();
    alert('تم إرسال طلب إعادة الاتصال بالسيرفر فوراً.');
    refreshStatus();
  });

  // ==========================================
  // Live Streaming Logs
  // ==========================================
  const logViewer = document.getElementById('log-viewer');

  function appendLog(entry) {
    if (!entry) return;
    const emptyMsg = logViewer.querySelector('.log-empty-msg');
    if (emptyMsg) emptyMsg.remove();

    const div = document.createElement('div');
    div.className = 'log-entry';
    div.innerHTML = `
      <span class="log-time">[${entry.time || ''}]</span>
      <span class="log-level ${(entry.level || 'info').toLowerCase()}">[${(entry.level || 'INFO').toUpperCase()}]</span>
      <span class="log-msg">${escapeHtml(entry.message || '')}</span>
    `;

    logViewer.appendChild(div);
    logViewer.scrollTop = logViewer.scrollHeight;

    // Limit displayed log lines in DOM to 150
    while (logViewer.children.length > 150) {
      logViewer.removeChild(logViewer.firstChild);
    }
  }

  function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  async function loadInitialLogs() {
    try {
      const res = await api.getLogs(60);
      const logs = (res.logs || []).reverse();
      if (logs.length > 0) {
        logViewer.innerHTML = '';
        logs.forEach(log => appendLog(log));
      }
    } catch (e) {
      console.warn('Failed to load initial logs:', e);
    }
  }

  document.getElementById('btn-clear-logs')?.addEventListener('click', async () => {
    await api.clearLogs();
    logViewer.innerHTML = '<div class="log-empty-msg">تم تفريغ شاشة السجل.</div>';
  });

  document.getElementById('btn-open-logs-dir')?.addEventListener('click', () => {
    api.openLogsFolder();
  });

  // Subscribe to live log events pushed from main process
  api.onLogEvent((event) => {
    appendLog(event);
  });

  // ==========================================
  // Initialization
  // ==========================================
  await loadConfig();
  await loadPrinters(false);
  await refreshStatus();
  await loadInitialLogs();

  // Polling timers
  setInterval(refreshStatus, 3000);
});
