# Desktop POS Print Agent for Cafe Management System
### Production-Grade, Offline-First Thermal Print Daemon & Laravel Reverb Gateway

---

## 1. Executive Summary & Architecture Overview

The **Desktop POS Print Agent** is a lightweight, zero-C++ compilation Node.js daemon engineered for high-volume hospitality and retail POS terminals. It bridges cloud or on-premise **Laravel Reverb WebSockets** to physical **ESC/POS thermal printers** over raw TCP sockets (`port 9100`), guaranteeing sub-second ticket rendering, automated cash drawer triggers, and strict hardware verification before order authorization.

### Architectural Blueprint

```
                     ┌──────────────────────────────────────────────┐
                     │            Laravel Cloud / On-Prem           │
                     │  - Reverb WebSocket Server (Port 8080)       │
                     │  - HTTP API Heartbeat & Sync (:8000)         │
                     └──────────────────────┬───────────────────────┘
                                            │
               WSS / WS Event Stream        │  HTTP Heartbeat (30s)
          .print.job (Channel: print-agent) │  POST /api/pos/device-heartbeat
                                            ▼
                     ┌──────────────────────────────────────────────┐
                     │     POS Print Agent Daemon (localhost:3210)  │
                     │                                              │
                     │  ┌────────────────┐    ┌──────────────────┐  │
                     │  │ Express Server │    │ Config Manager   │  │
                     │  │ & Dashboard UI │    │ (config.json)    │  │
                     │  └────────┬───────┘    └────────┬─────────┘  │
                     │           │                     │            │
                     │  ┌────────▼─────────────────────▼─────────┐  │
                     │  │    Pure Binary ESC/POS Dispatcher      │  │
                     │  │   (Native Node Buffer - Zero C++ Deps) │  │
                     │  └────────┬─────────────────────┬─────────┘  │
                     └───────────┼─────────────────────┼────────────┘
                                 │ Raw TCP             │ Raw TCP
                                 │ Port 9100           │ Port 9100
                                 ▼                     ▼
          ┌─────────────────────────────┐   ┌──────────────────────────────┐
          │   Cashier Thermal Printer   │   │  Barista / Kitchen Printer   │
          │   (Customer 42-col Receipt) │   │  (High-Visibility Ticket)    │
          │              │              │   │   * Large Font               │
          │              ▼              │   │   * Item Modifiers           │
          │  RJ-11 Cash Drawer Solenoid │   │   * Partial Paper Cut        │
          │   (1B 70 00 19 FA Kick)     │   └──────────────────────────────┘
          │   (1D 56 41 03 Paper Cut)   │
          └─────────────────────────────┘
```

### Core Technical Pillars

1. **Zero-C++ Runtime Architecture**: Completely eliminates `node-gyp`, Python, Windows SDKs, and native C++ compilation. Built on Node's native `net` socket layer and `Buffer` byte primitives, running reliably on any Windows, macOS, or Linux platform.
2. **Offline-First Synchronization & Reconnect Backoff**: Implements exponential reconnection backoff with randomized jitter. Recovers missed print jobs (`GET /api/print-agent/jobs`) on network reconnection.
3. **Strict TCP Socket Lifecycle**: Every connection enforces strict timeout guards (default 4000ms), removes listeners, and calls `socket.destroy()` to completely prevent file descriptor or memory leaks.
4. **Hardware Verification Heartbeat**: Continuously probes printer TCP sockets and broadcasts device state to `POST /api/pos/device-heartbeat`. Laravel rejects orders when printers are offline, eliminating lost cashier receipts.

---

## 2. Directory Structure

```
├── configManager.js        # Atomic config loader, validator, and disk persistence
├── package.json            # Project manifest, dependencies, and packaging targets
├── printerDispatcher.js    # Pure binary ESC/POS formatting and Raw TCP socket engine
├── public/                 # Embedded Express Dashboard UI
│   ├── app.js              # Real-time dashboard client controller and API fetcher
│   └── index.html          # Tailwind CSS single-page management dashboard
├── README.md               # Enterprise manual and packaging documentation
├── reverbClient.js         # WebSocket daemon, watchdog, and Laravel sync reporter
├── server.js               # Application bootstrap, Express server, and heartbeat scheduler
└── virtualPrinter.js       # Dual-port TCP ESC/POS simulator (ports 9101, 9102) for local dev
```

---

## 3. Local Development on macOS / Linux (Virtual TCP Simulator)

You do not need physical thermal hardware to develop and test the agent. The included `virtualPrinter.js` daemon simulates four network thermal printers on localhost:
- **Port 9101**: Cashier Printer & Cash Drawer Solenoid (`CASHIER`)
- **Port 9102**: Barista / Beverage Ticket Printer (`BARISTA`)
- **Port 9103**: Kitchen / Hot Food Station Printer (`KITCHEN`)
- **Port 9104**: Hall Orders / Delivery Backup Printer (`ALL`)

### Step 1: Start the Virtual Printer Simulator

In your first terminal window, start the simulator:

```bash
npm run virtual-printer
```

Output:
```text
===========================================================
  تشغيل محاكي الطابعات الحرارية (4 طابعات TCP افتراضية)  
===========================================================
[VirtualPrinter] طابعة الكاشير الرئيسية (CASHIER) جاهزة وتستمع على البورت TCP :9101
[VirtualPrinter] طابعة الباريستا والمشروبات (BARISTA) جاهزة وتستمع على البورت TCP :9102
[VirtualPrinter] طابعة المطبخ والمأكولات (KITCHEN) جاهزة وتستمع على البورت TCP :9103
[VirtualPrinter] طابعة الصالة والتوصيل (عامة) (ALL) جاهزة وتستمع على البورت TCP :9104
```

### Step 2: Start the POS Print Agent

In a second terminal window, start the agent:

```bash
npm start
```

Output:
```text
====================================================
  CAFE MANAGEMENT - DESKTOP POS PRINT AGENT v1.0.0  
====================================================
[Server] Web Dashboard listening at: http://localhost:3210
[Server] Device UUID: "pos-cashier-01"
[Server] Configuration stored at: /path/to/config.json
[Server] Heartbeat synced with Laravel: Status="ready" (Printers: Cashier=UP, Barista=UP)
[ReverbClient] WebSocket connected successfully to Laravel Reverb
[ReverbClient] Subscribed successfully to "print-agent.pos-cashier-01". Ready to receive print jobs.
```

### Step 3: Access Local Web Configuration Dashboard

Open your browser to:
```
http://localhost:3210
```

From this UI, you can:
1. View live status indicators for Reverb WebSocket and Laravel Sync.
2. Click **"Probe Cashier Socket"** or **"Probe Barista Socket"** to run immediate TCP handshakes.
3. Click **"Test Sample Print (Cashier)"** to dispatch a test receipt.

### Step 4: Verify ESC/POS Output in Simulator

When a print job or test print is fired, check the simulator terminal:

```text
=== [VIRTUAL THERMAL PRINTER: CASHIER (Port 9101)] 5:12:44 PM ===
Payload Size: 342 bytes
┌──────────────────────────────────────────┐
│             CAFE MANAGEMENT              │
│       Specialty Coffee & Roastery        │
│          Terminal: pos-cashier-01        │
│ ---------------------------------------- │
│ Order #: #1042                           │
│ Date   : 2026-09-18 17:12                │
│ Type   : Table: 5                        │
│ ---------------------------------------- │
│ Qty Item Description               Price │
│ - - - - - - - - - - - - - - - - - - - -  │
│ 2x Flat White (Oat Milk)           36.00 │
│ 1x San Sebastian Cheesecake        28.00 │
│ ---------------------------------------- │
│ TOTAL:                         64.00 SAR │
│ Payment Method: CASH                     │
│ ---------------------------------------- │
│       Thank you for visiting us!         │
└──────────────────────────────────────────┘
⚡ [HARDWARE EVENT] Cash Drawer Kick Pulse Detected (Pin 2 Solenoid Fired)
✂️  [HARDWARE EVENT] Partial Paper Cut Command Executed
=================================================================
```

---

## 4. Architectural Analysis: Standalone `.exe` vs. Background Windows Service

A common question in POS deployment:
*Should we distribute the agent as a standalone executable (`.exe`) that cashier staff double-clicks, or install it as an unattended Windows Service?*

### Comparison Matrix

| Dimension | Standalone `.exe` (User Space) | Unattended Windows Service (NSSM / LocalSystem) |
| :--- | :--- | :--- |
| **Startup Behavior** | Requires user login to Windows desktop; fails to start if user forgets to launch it or closes the window. | **Starts on OS boot before any user logs in**. Completely independent of user login state. |
| **Cashier Tampering Risk** | High. Cashiers can accidentally close the console window, exit the tray, or kill the process. | **Zero**. Runs in Session 0 background. Cashier cannot accidentally terminate the process. |
| **Crash Recovery** | None. If the app crashes (e.g. out of memory, hardware glitch), printing stays down until someone notices. | **Automatic Instant Restart**. Windows Service Manager automatically restarts the service on crash within seconds. |
| **Access to Configuration UI** | Available at `http://localhost:3210`. | **Identical**. Express continues serving `http://localhost:3210` over HTTP for any local browser session. |
| **System Resource Impact** | Slightly higher due to interactive window overhead. | Minimal. Zero GUI overhead in Session 0. |
| **Enterprise Fleet Upgrades** | Requires manual intervention per station. | Can be updated remotely via PowerShell or Group Policy (GPO) without interrupting user desktop. |

### The Verdict for Production POS Terminals

> [!IMPORTANT]
> **Production Best Practice: Unattended Windows Service via NSSM.**
> In high-turnover cafe environments, cashiers frequently close open terminal windows, reboot POS terminals during peak rush hours, or fail to start startup shortcuts.
> An unattended Windows Service guarantees that **as long as the POS machine has power, the print agent is running and listening for orders**, even on the Windows login screen.

---

## 5. Step-by-Step Standalone `.exe` Compilation (`@yao-pkg/pkg`)

We use `@yao-pkg/pkg` (the maintained community fork supporting Node 18, 20, and 22) to package the entire Node runtime, Express UI, static public assets, and dependencies into a single binary.

### Prerequisites

Ensure you have installed dev dependencies:
```bash
npm install
```

### Build Command for 64-bit Windows

Run the build script defined in `package.json`:

```bash
npm run build:win
```

Or execute directly:
```bash
npx @yao-pkg/pkg . --targets node18-win-x64 --output dist/pos-agent.exe
```

### Build Command for macOS / Linux (Cross-Platform)

```bash
# macOS Build
npm run build:mac

# Linux Build
npm run build:linux
```

The resulting binary will be located in `./dist/pos-agent.exe`. It contains:
- Embedded Node.js runtime
- All npm dependencies (`pusher-js`, `express`, `ws`, `cors`)
- Embedded static UI files from `public/`
- Runtime configuration reader targeting `config.json` alongside `pos-agent.exe`

---

## 6. Installing as an Unattended Windows Service (1-Click Automation)

We provide an automated, 1-click installation script (`install-service.bat`) that registers `CafePrintAgent` as a resilient Windows Service using NSSM, adds the Windows Firewall rule, sets auto-start on boot, and configures an auto-restart guard on crashes.

### Method A: 1-Click Automated Setup (Recommended for POS Clients)

1. Copy the contents of the `dist/` directory (`pos-agent.exe`, `install-service.bat`, `uninstall-service.bat`) to:
   `C:\CafePOS\PrintAgent\`
2. Place `nssm.exe` (from `https://nssm.cc/download`) into `C:\CafePOS\PrintAgent\`
3. **Right-click `install-service.bat` and select "Run as administrator"**.
4. The service `CafePrintAgent` will be installed, configured, firewall-opened, and started immediately!

### Method B: Manual Command-Line Setup (Advanced)

Right-click **Command Prompt** and select **"Run as administrator"**:
```cmd
cd C:\CafePOS\PrintAgent
```

### Step 3: Install the Service via NSSM

Run the following commands:

```cmd
:: 1. Register the service binary
nssm.exe install CafePrintAgent "C:\CafePOS\PrintAgent\pos-agent.exe"

:: 2. Set the startup working directory
nssm.exe set CafePrintAgent AppDirectory "C:\CafePOS\PrintAgent"

:: 3. Configure service description and display name
nssm.exe set CafePrintAgent DisplayName "Cafe POS Thermal Print Agent"
nssm.exe set CafePrintAgent Description "Production ESC/POS thermal printer daemon and Laravel Reverb gateway"

:: 4. Set startup mode to Automatic (starts on Windows boot)
nssm.exe set CafePrintAgent Start SERVICE_AUTO_START

:: 5. Configure stdout and stderr log rotation
nssm.exe set CafePrintAgent AppStdout "C:\CafePOS\PrintAgent\logs\agent-out.log"
nssm.exe set CafePrintAgent AppStderr "C:\CafePOS\PrintAgent\logs\agent-err.log"
nssm.exe set CafePrintAgent AppStdoutCreationDisposition 4
nssm.exe set CafePrintAgent AppStderrCreationDisposition 4
nssm.exe set CafePrintAgent AppRotateFiles 1
nssm.exe set CafePrintAgent AppRotateOnline 1
nssm.exe set CafePrintAgent AppRotateSeconds 86400
nssm.exe set CafePrintAgent AppRotateBytes 5242880

:: 6. Configure instant automatic restart on crash
nssm.exe set CafePrintAgent AppExit Default Restart
nssm.exe set CafePrintAgent AppRestartDelay 3000
```

### Step 4: Start the Service

```cmd
nssm.exe start CafePrintAgent
```

Verify service status:
```cmd
nssm.exe status CafePrintAgent
```
Output: `SERVICE_RUNNING`

### Step 5: Configure Firewall (One-Time)

Allow the local dashboard port through Windows Firewall:

```cmd
netsh advfirewall firewall add rule name="Cafe POS Agent Dashboard" dir=in action=allow protocol=TCP localport=3210
```

### How Cashiers Access the Settings Dashboard

Whenever management needs to adjust printer IP addresses:
1. Open Google Chrome or Microsoft Edge on the POS terminal.
2. Navigate to: `http://localhost:3210`
3. Enter new IP addresses and click **"Save Settings & Apply"**.
4. The service immediately adopts the new configuration without requiring a Windows restart.

---

## 7. Configuration Schema Reference (`config.json`)

The `config.json` file is located adjacent to `pos-agent.exe`:

```json
{
  "laravel_backend_url": "http://127.0.0.1:8000",
  "reverb": {
    "host": "127.0.0.1",
    "port": 8080,
    "scheme": "http",
    "app_key": "4m1fxfohhbtyb2cxn45b"
  },
  "device_uuid": "pos-cashier-01",
  "printers": {
    "cashier": {
      "name": "Cashier Thermal Printer",
      "host": "192.168.1.101",
      "port": 9100,
      "enabled": true,
      "timeout_ms": 4000
    },
    "barista": {
      "name": "Barista / Kitchen Printer",
      "host": "192.168.1.102",
      "port": 9100,
      "enabled": true,
      "timeout_ms": 4000
    }
  },
  "server": {
    "port": 3210
  },
  "heartbeat_interval_ms": 30000,
  "is_configured": true
}
```

---

## 8. ESC/POS Binary Command Specification

All byte sequences are generated in pure memory buffers without third-party drivers:

| Function | Hex Command | Description |
| :--- | :--- | :--- |
| **Initialize** | `1B 40` | `ESC @`: Resets hardware buffer and print modes |
| **Align Left** | `1B 61 00` | `ESC a 0`: Left-justifies subsequent text |
| **Align Center** | `1B 61 01` | `ESC a 1`: Center-aligns receipt headers and totals |
| **Align Right** | `1B 61 02` | `ESC a 2`: Right-aligns price columns |
| **Double Width & Height** | `1D 21 11` | `GS ! 17`: 2x width and 2x height for kitchen tickets |
| **Double Height Only** | `1D 21 01` | `GS ! 1`: 1x width and 2x height for grand total |
| **Bold On / Off** | `1B 45 01` / `1B 45 00` | `ESC E 1` / `ESC E 0`: Emphasized text toggle |
| **Drawer Kick** | `1B 70 00 19 FA` | `ESC p 0 25 250`: Fires 24V pulse to Pin 2 for 50ms |
| **Partial Paper Cut** | `1D 56 41 03` | `GS V 65 3`: Feeds 3 lines and cuts leaving 1 tab |

---

## 9. Troubleshooting & Diagnostic Playbook

### 1. Reverb WebSocket Status is "Offline" or "Reconnecting"
- Verify that Laravel Reverb is running on the server:
  ```bash
  php artisan reverb:start --host=0.0.0.0 --port=8080
  ```
- Verify network route from POS PC to Reverb host using PowerShell:
  ```powershell
  Test-NetConnection -ComputerName 192.168.1.50 -Port 8080
  ```
- Ensure the `app_key` in `config.json` exactly matches `REVERB_APP_KEY` in Laravel's `.env`.

### 2. Printer Shows "OFFLINE" in Dashboard
- Verify physical power and ethernet cable connection on the thermal printer.
- Verify printer IP by performing a hardware self-test (turn off printer, hold **FEED** button, turn on printer).
- Verify printer TCP port 9100 using PowerShell:
  ```powershell
  Test-NetConnection -ComputerName 192.168.1.101 -Port 9100
  ```

### 3. Drawer Kick Does Not Open Cash Drawer
- Verify the cash drawer RJ-11 cable is plugged directly into the **DK port** of the **Cashier Printer** (not the router or telephone jack).
- Ensure the drawer key lock is turned to the unlocked position.
- In `config.json`, verify that the target printer is assigned `cashier` (barista/kitchen tickets do not send the drawer kick pulse).
