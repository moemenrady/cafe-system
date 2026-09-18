/**
 * printerDiscovery.js
 * 
 * Automated Local Network & System Printer Discovery Engine.
 * 
 * Features:
 * 1. Subnet Auto-Detection: Detects active network interfaces (e.g. 192.168.1.0/24)
 *    and scans for ESC/POS thermal printers listening on standard TCP port 9100.
 * 2. Localhost Virtual Printer Detection: Probes ports 9100 to 9106.
 * 3. Windows & Unix Spooler Discovery: Queries OS installed printers via PowerShell (Get-Printer)
 *    or lpstat on macOS/Linux.
 * 4. Smart Role Suggestion: Suggests appropriate roles (cashier, barista, kitchen) based on port
 *    or printer model name.
 */

const net = require('net');
const os = require('os');
const { exec } = require('child_process');

class PrinterDiscovery {
  constructor() {
    this.cachedDiscovered = [];
    this.lastScanTime = null;
    this.isScanning = false;
  }

  /**
   * Fast non-blocking TCP socket probe.
   */
  probeTcpPort(host, port, timeoutMs = 400) {
    return new Promise((resolve) => {
      const startTime = Date.now();
      const socket = new net.Socket();
      let resolved = false;

      const finish = (online) => {
        if (resolved) return;
        resolved = true;
        const latencyMs = Date.now() - startTime;
        try {
          socket.removeAllListeners();
          socket.destroy();
        } catch (_) {}
        resolve({ online, host, port, latencyMs });
      };

      socket.setTimeout(timeoutMs);
      socket.on('connect', () => finish(true));
      socket.on('timeout', () => finish(false));
      socket.on('error', () => finish(false));

      try {
        socket.connect(port, host);
      } catch (_) {
        finish(false);
      }
    });
  }

  /**
   * Discovers local and subnet network thermal printers.
   */
  async scanNetworkPrinters() {
    const discovered = [];
    const probeTargets = [];

    // 1. Localhost Virtual and Raw TCP Ports (9100 - 9106)
    for (let p = 9100; p <= 9106; p++) {
      probeTargets.push({ host: '127.0.0.1', port: p });
    }

    // 2. Discover Local Network Subnet (e.g. 192.168.1.1 - 254)
    const interfaces = os.networkInterfaces();
    const subnets = new Set();

    Object.values(interfaces).forEach(ifaceList => {
      if (!ifaceList) return;
      ifaceList.forEach(iface => {
        // Only consider non-internal IPv4 addresses (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
        if (!iface.internal && iface.family === 'IPv4' && iface.address) {
          const parts = iface.address.split('.');
          if (parts.length === 4) {
            const prefix = `${parts[0]}.${parts[1]}.${parts[2]}`;
            subnets.add(prefix);
          }
        }
      });
    });

    // Add common POS printer IP ranges on discovered subnets
    subnets.forEach(subnetPrefix => {
      // Prioritize common printer IPs (100-115, 200-210, 1-30) and scan entire /24
      for (let i = 1; i <= 254; i++) {
        probeTargets.push({ host: `${subnetPrefix}.${i}`, port: 9100 });
      }
    });

    // Execute concurrent socket probes with timeout
    const batchSize = 64;
    for (let i = 0; i < probeTargets.length; i += batchSize) {
      const batch = probeTargets.slice(i, i + batchSize);
      const results = await Promise.all(
        batch.map(target => this.probeTcpPort(target.host, target.port, 350))
      );

      results.forEach(res => {
        if (res.online) {
          let suggestedName = `طابعة حرارية (${res.host}:${res.port})`;
          let suggestedRole = 'cashier';

          if (res.host === '127.0.0.1') {
            if (res.port === 9101) {
              suggestedName = 'طابعة الكاشير الرئيسية (محاكي 9101)';
              suggestedRole = 'cashier';
            } else if (res.port === 9102) {
              suggestedName = 'طابعة الباريستا والمشروبات (محاكي 9102)';
              suggestedRole = 'barista';
            } else if (res.port === 9103) {
              suggestedName = 'طابعة المطبخ والمأكولات (محاكي 9103)';
              suggestedRole = 'kitchen';
            } else if (res.port === 9104) {
              suggestedName = 'طابعة الصالة والتوصيل (محاكي 9104)';
              suggestedRole = 'all';
            }
          } else {
            suggestedName = `طابعة شبكة (${res.host})`;
          }

          discovered.push({
            id: `tcp-${res.host.replace(/\./g, '-')}-${res.port}`,
            name: suggestedName,
            host: res.host,
            port: res.port,
            suggested_role: suggestedRole,
            type: 'network_tcp',
            latencyMs: res.latencyMs
          });
        }
      });
    }

    return discovered;
  }

  /**
   * Queries Windows OS / Spooler printers (e.g. USB or Driver-installed printers).
   */
  getSystemSpoolerPrinters() {
    return new Promise((resolve) => {
      const platform = process.platform;
      if (platform === 'win32') {
        const cmd = 'powershell -NoProfile -Command "Get-Printer | Select-Object Name, PortName, DriverName | ConvertTo-Json -Compress"';
        exec(cmd, { timeout: 4000 }, (err, stdout) => {
          if (err || !stdout) return resolve([]);
          try {
            const parsed = JSON.parse(stdout);
            const list = Array.isArray(parsed) ? parsed : [parsed];
            const printers = list.map(p => ({
              id: `sys-${encodeURIComponent(p.Name || 'printer')}`,
              name: p.Name || 'طابعة نظام',
              port_name: p.PortName || 'USB',
              driver_name: p.DriverName || '',
              type: 'system_spooler',
              suggested_role: (p.Name && p.Name.toLowerCase().includes('kitchen')) ? 'kitchen' : 'cashier'
            }));
            resolve(printers);
          } catch (_) {
            resolve([]);
          }
        });
      } else {
        // macOS / Linux CUPS spooler
        exec('lpstat -p', { timeout: 3000 }, (err, stdout) => {
          if (err || !stdout) return resolve([]);
          const lines = stdout.split('\n');
          const printers = [];
          lines.forEach(line => {
            const match = line.match(/^printer\s+([^\s]+)/i);
            if (match && match[1]) {
              printers.push({
                id: `cups-${match[1]}`,
                name: match[1],
                type: 'cups_spooler',
                suggested_role: 'cashier'
              });
            }
          });
          resolve(printers);
        });
      }
    });
  }

  /**
   * Complete discovery method combining TCP network scan and OS spooler.
   */
  async discoverAllPrinters() {
    if (this.isScanning) {
      return this.cachedDiscovered;
    }

    this.isScanning = true;
    try {
      console.log('[PrinterDiscovery] بدء البحث التلقائي عن الطابعات في الشبكة والمنافذ المحلية...');
      const [tcpPrinters, sysPrinters] = await Promise.all([
        this.scanNetworkPrinters(),
        this.getSystemSpoolerPrinters()
      ]);

      const merged = [...tcpPrinters, ...sysPrinters];
      this.cachedDiscovered = merged;
      this.lastScanTime = new Date().toISOString();
      console.log(`[PrinterDiscovery] اكتمل البحث: تم العثور على ${merged.length} طابعة متاحة.`);
      return merged;
    } catch (err) {
      console.error(`[PrinterDiscovery] خطأ أثناء البحث عن الطابعات: ${err.message}`);
      return this.cachedDiscovered;
    } finally {
      this.isScanning = false;
    }
  }

  getCached() {
    return this.cachedDiscovered;
  }
}

module.exports = new PrinterDiscovery();
