using System;
using System.IO;
using System.IO.Compression;
using System.Reflection;
using System.Diagnostics;
using System.Windows.Forms;
using System.Drawing;
using System.Security.Principal;
using Microsoft.Win32;

namespace CafePrintAgentInstaller
{
    public class InstallerForm : Form
    {
        private ProgressBar progressBar;
        private Label lblTitle;
        private Label lblSubtitle;
        private Label lblStatus;
        private Label lblPath;
        private TextBox txtPath;
        private Button btnBrowse;
        private Button btnInstall;
        private Button btnCancel;
        private CheckBox chkAutoStart;
        private CheckBox chkDesktopShortcut;
        private CheckBox chkLaunchNow;
        private PictureBox picLogo;

        private string defaultInstallDir;

        public InstallerForm()
        {
            InitializeComponent();
        }

        private bool IsAdministrator()
        {
            var identity = WindowsIdentity.GetCurrent();
            var principal = new WindowsPrincipal(identity);
            return principal.IsInRole(WindowsBuiltInRole.Administrator);
        }

        private void InitializeComponent()
        {
            this.Text = "تثبيت برنامج طباعة الكافيه | Cafe Print Agent Setup";
            this.Size = new Size(580, 430);
            this.StartPosition = FormStartPosition.CenterScreen;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.RightToLeft = RightToLeft.Yes;
            this.RightToLeftLayout = true;
            this.BackColor = Color.FromArgb(15, 23, 42); // Dark slate (#0f172a)
            this.ForeColor = Color.White;
            this.Font = new Font("Segoe UI", 9F, FontStyle.Regular);

            // Icon
            try
            {
                string iconPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "assets", "icon.ico");
                if (File.Exists(iconPath)) this.Icon = new Icon(iconPath);
            }
            catch { }

            // Default directory
            if (IsAdministrator())
            {
                defaultInstallDir = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "Cafe Print Agent");
            }
            else
            {
                defaultInstallDir = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "Programs", "Cafe Print Agent");
            }

            // Header Panel
            Panel pnlHeader = new Panel
            {
                Dock = DockStyle.Top,
                Height = 85,
                BackColor = Color.FromArgb(30, 41, 59) // Surface-800
            };
            this.Controls.Add(pnlHeader);

            lblTitle = new Label
            {
                Text = "نظام طباعة الكافيه المكتبي (Cafe Print Agent)",
                Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                ForeColor = Color.FromArgb(52, 211, 153), // Emerald-400
                Location = new Point(20, 15),
                AutoSize = true
            };
            pnlHeader.Controls.Add(lblTitle);

            lblSubtitle = new Label
            {
                Text = "برنامج الطباعة الحرارية المباشرة والربط السحابي مع سيرفر الكافيه",
                Font = new Font("Segoe UI", 9F, FontStyle.Regular),
                ForeColor = Color.FromArgb(148, 163, 184), // Slate-400
                Location = new Point(20, 45),
                AutoSize = true
            };
            pnlHeader.Controls.Add(lblSubtitle);

            // Body Controls
            lblPath = new Label
            {
                Text = "مجلد التثبيت على الجهاز:",
                Location = new Point(25, 105),
                AutoSize = true,
                ForeColor = Color.FromArgb(226, 232, 240)
            };
            this.Controls.Add(lblPath);

            txtPath = new TextBox
            {
                Text = defaultInstallDir,
                Location = new Point(120, 130),
                Size = new Size(330, 25),
                BackColor = Color.FromArgb(30, 41, 59),
                ForeColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                RightToLeft = RightToLeft.No
            };
            this.Controls.Add(txtPath);

            btnBrowse = new Button
            {
                Text = "تحديد...",
                Location = new Point(25, 128),
                Size = new Size(85, 27),
                BackColor = Color.FromArgb(51, 65, 85),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat
            };
            btnBrowse.Click += (s, e) =>
            {
                using (var fbd = new FolderBrowserDialog())
                {
                    fbd.SelectedPath = txtPath.Text;
                    if (fbd.ShowDialog() == DialogResult.OK)
                    {
                        txtPath.Text = Path.Combine(fbd.SelectedPath, "Cafe Print Agent");
                    }
                }
            };
            this.Controls.Add(btnBrowse);

            // Checkboxes
            chkAutoStart = new CheckBox
            {
                Text = "التشغيل التلقائي للبرنامج في الخلفية مع بدء تشغيل الويندوز (موصى به للكاشير)",
                Checked = true,
                Location = new Point(25, 175),
                AutoSize = true,
                ForeColor = Color.FromArgb(203, 213, 225)
            };
            this.Controls.Add(chkAutoStart);

            chkDesktopShortcut = new CheckBox
            {
                Text = "إنشاء اختصار على سطح المكتب وقائمة ابدأ",
                Checked = true,
                Location = new Point(25, 205),
                AutoSize = true,
                ForeColor = Color.FromArgb(203, 213, 225)
            };
            this.Controls.Add(chkDesktopShortcut);

            chkLaunchNow = new CheckBox
            {
                Text = "تشغيل برنامج الطباعة فور اكتمال التثبيت",
                Checked = true,
                Location = new Point(25, 235),
                AutoSize = true,
                ForeColor = Color.FromArgb(203, 213, 225)
            };
            this.Controls.Add(chkLaunchNow);

            // Progress bar
            progressBar = new ProgressBar
            {
                Location = new Point(25, 275),
                Size = new Size(515, 22),
                Visible = false,
                Style = ProgressBarStyle.Continuous
            };
            this.Controls.Add(progressBar);

            lblStatus = new Label
            {
                Text = "",
                Location = new Point(25, 305),
                Size = new Size(515, 20),
                ForeColor = Color.FromArgb(52, 211, 153),
                Visible = false
            };
            this.Controls.Add(lblStatus);

            // Bottom Buttons
            btnInstall = new Button
            {
                Text = "بدء التثبيت الآن",
                Location = new Point(135, 340),
                Size = new Size(130, 35),
                BackColor = Color.FromArgb(16, 185, 129), // Emerald-500
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat,
                Font = new Font("Segoe UI", 10F, FontStyle.Bold)
            };
            btnInstall.Click += BtnInstall_Click;
            this.Controls.Add(btnInstall);

            btnCancel = new Button
            {
                Text = "إلغاء الأمر",
                Location = new Point(25, 340),
                Size = new Size(100, 35),
                BackColor = Color.FromArgb(51, 65, 85),
                ForeColor = Color.White,
                FlatStyle = FlatStyle.Flat
            };
            btnCancel.Click += (s, e) => this.Close();
            this.Controls.Add(btnCancel);
        }

        private async void BtnInstall_Click(object sender, EventArgs e)
        {
            string installPath = txtPath.Text.Trim();
            if (string.IsNullOrEmpty(installPath))
            {
                MessageBox.Show("يرجى تحديد مسار مجلد التثبيت.", "تنبيه", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            btnInstall.Enabled = false;
            btnBrowse.Enabled = false;
            txtPath.Enabled = false;
            chkAutoStart.Enabled = false;
            chkDesktopShortcut.Enabled = false;
            chkLaunchNow.Enabled = false;

            progressBar.Visible = true;
            lblStatus.Visible = true;
            progressBar.Value = 10;
            lblStatus.Text = "جاري تحضير مسار التثبيت...";

            try
            {
                await System.Threading.Tasks.Task.Run(() =>
                {
                    PerformInstallation(installPath);
                });

                progressBar.Value = 100;
                lblStatus.Text = "اكتمل التثبيت بنجاح تام! ✓";

                if (chkLaunchNow.Checked)
                {
                    string exePath = Path.Combine(installPath, "Cafe Print Agent.exe");
                    if (File.Exists(exePath))
                    {
                        Process.Start(new ProcessStartInfo
                        {
                            FileName = exePath,
                            WorkingDirectory = installPath
                        });
                    }
                }

                MessageBox.Show(
                    "تم تثبيت برنامج طباعة الكافيه (Cafe Print Agent) بنجاح على الجهاز!\n\n" +
                    "البرنامج يعمل الآن في الخلفية وجاهز لاستقبال طلبات الطباعة عبر Reverb و Port 3210.",
                    "اكتمل التثبيت",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Information);

                this.Close();
            }
            catch (Exception ex)
            {
                progressBar.Visible = false;
                lblStatus.Text = "حدث خطأ أثناء التثبيت!";
                lblStatus.ForeColor = Color.Red;
                MessageBox.Show("فشل تثبيت البرنامج: " + ex.Message, "خطأ في التثبيت", MessageBoxButtons.OK, MessageBoxIcon.Error);
                btnInstall.Enabled = true;
                btnCancel.Enabled = true;
            }
        }

        private void PerformInstallation(string installDir)
        {
            // 1. Create target directory
            UpdateStatus("جاري إنشاء مجلدات البرنامج...", 20);
            if (!Directory.Exists(installDir))
            {
                Directory.CreateDirectory(installDir);
            }

            // Create logs directory
            string logsDir = Path.Combine(installDir, "logs");
            if (!Directory.Exists(logsDir)) Directory.CreateDirectory(logsDir);

            // 2. Extract embedded payload
            UpdateStatus("جاري استخراج ملفات البرنامج ومحرك التشغيل المدمج...", 35);
            var assembly = Assembly.GetExecutingAssembly();
            using (var resourceStream = assembly.GetManifestResourceStream("payload.zip"))
            {
                if (resourceStream == null)
                {
                    // If running next to portable zip
                    string localZip = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "Cafe Print Agent Portable.zip");
                    if (File.Exists(localZip))
                    {
                        using (var fs = File.OpenRead(localZip))
                        using (var archive = new ZipArchive(fs, ZipArchiveMode.Read))
                        {
                            ExtractZipWithProgress(archive, installDir);
                        }
                    }
                    else
                    {
                        throw new FileNotFoundException("لم يتم العثور على حزمة البرنامج المدمجة (payload.zip).");
                    }
                }
                else
                {
                    using (var archive = new ZipArchive(resourceStream, ZipArchiveMode.Read))
                    {
                        ExtractZipWithProgress(archive, installDir);
                    }
                }
            }

            // 3. Setup persistent config in AppData
            UpdateStatus("جاري ضبط إعدادات المحطة الافتراضية...", 70);
            string appDataDir = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ApplicationData), "pos-print-agent");
            if (!Directory.Exists(appDataDir)) Directory.CreateDirectory(appDataDir);
            string appDataConfig = Path.Combine(appDataDir, "config.json");
            string bundledConfig = Path.Combine(installDir, "config.json");

            if (!File.Exists(appDataConfig) && File.Exists(bundledConfig))
            {
                File.Copy(bundledConfig, appDataConfig, true);
            }

            // 4. Windows Auto-Start
            if (chkAutoStart.Checked)
            {
                UpdateStatus("جاري تسجيل التشغيل التلقائي مع الويندوز...", 80);
                string exePath = Path.Combine(installDir, "Cafe Print Agent.exe");
                try
                {
                    using (var key = Registry.CurrentUser.OpenSubKey(@"Software\Microsoft\Windows\CurrentVersion\Run", true))
                    {
                        if (key != null)
                        {
                            key.SetValue("Cafe Print Agent", "\"" + exePath + "\" --hidden");
                        }
                    }
                }
                catch { }
            }

            // 5. Shortcuts
            if (chkDesktopShortcut.Checked)
            {
                UpdateStatus("جاري إنشاء الاختصارات على سطح المكتب وقائمة ابدأ...", 85);
                string exePath = Path.Combine(installDir, "Cafe Print Agent.exe");
                string desktopPath = Environment.GetFolderPath(Environment.SpecialFolder.DesktopDirectory);
                string startMenuPath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.Programs), "Cafe Print Agent");
                if (!Directory.Exists(startMenuPath)) Directory.CreateDirectory(startMenuPath);

                CreateShortcut(Path.Combine(desktopPath, "Cafe Print Agent.lnk"), exePath, installDir, "نظام طباعة الكافيه المكتبي");
                CreateShortcut(Path.Combine(startMenuPath, "Cafe Print Agent.lnk"), exePath, installDir, "نظام طباعة الكافيه المكتبي");
            }

            // 6. Create Uninstaller
            UpdateStatus("جاري إنشاء برنامج إلغاء التثبيت وتوثيق البرنامج...", 90);
            CreateUninstaller(installDir);

            // 7. Register in Add/Remove Programs
            RegisterUninstall(installDir);
        }

        private void ExtractZipWithProgress(ZipArchive archive, string destinationDir)
        {
            int total = archive.Entries.Count;
            int count = 0;

            foreach (var entry in archive.Entries)
            {
                count++;
                string destPath = Path.Combine(destinationDir, entry.FullName);

                // Ignore trailing directory entries
                if (string.IsNullOrEmpty(entry.Name))
                {
                    Directory.CreateDirectory(destPath);
                    continue;
                }

                string dir = Path.GetDirectoryName(destPath);
                if (!Directory.Exists(dir)) Directory.CreateDirectory(dir);

                // Extract with overwrite
                entry.ExtractToFile(destPath, true);

                if (count % 20 == 0 || count == total)
                {
                    int pct = 35 + (int)((float)count / total * 35);
                    UpdateStatus("جاري استخراج: " + entry.Name, pct);
                }
            }
        }

        private void UpdateStatus(string message, int progress)
        {
            if (this.InvokeRequired)
            {
                this.Invoke(new Action(() => UpdateStatus(message, progress)));
                return;
            }

            lblStatus.Text = message;
            if (progress >= 0 && progress <= 100) progressBar.Value = progress;
        }

        private void CreateShortcut(string shortcutPath, string targetPath, string workingDir, string description)
        {
            try
            {
                Type shellType = Type.GetTypeFromProgID("WScript.Shell");
                if (shellType == null) return;
                dynamic shell = Activator.CreateInstance(shellType);
                dynamic shortcut = shell.CreateShortcut(shortcutPath);
                shortcut.TargetPath = targetPath;
                shortcut.WorkingDirectory = workingDir;
                shortcut.Description = description;
                shortcut.Save();
            }
            catch { }
        }

        private void CreateUninstaller(string installDir)
        {
            string uninstallBat = Path.Combine(installDir, "uninstall.bat");
            string content = "@echo off\r\n" +
                "chcp 65001 >nul\r\n" +
                "echo جاري إغلاق برنامج طباعة الكافيه وإلغاء التثبيت...\r\n" +
                "taskkill /f /im \"Cafe Print Agent.exe\" >nul 2>&1\r\n" +
                "reg delete \"HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Run\" /v \"Cafe Print Agent\" /f >nul 2>&1\r\n" +
                "reg delete \"HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Uninstall\\CafePrintAgent\" /f >nul 2>&1\r\n" +
                "del /f /q \"%USERPROFILE%\\Desktop\\Cafe Print Agent.lnk\" >nul 2>&1\r\n" +
                "rd /s /q \"%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs\\Cafe Print Agent\" >nul 2>&1\r\n" +
                "echo تم حذف الاختصارات والتسجيلات بنجاح.\r\n" +
                "cd ..\r\n" +
                "timeout /t 2 >nul\r\n" +
                "start /b \"\" cmd /c \"rd /s /q \\\"" + installDir + "\\\"\"\r\n" +
                "echo تم إلغاء تثبيت برنامج طباعة الكافيه بنجاح.\r\n";
            File.WriteAllText(uninstallBat, content, System.Text.Encoding.UTF8);
        }

        private void RegisterUninstall(string installDir)
        {
            try
            {
                string keyPath = @"Software\Microsoft\Windows\CurrentVersion\Uninstall\CafePrintAgent";
                using (var key = Registry.CurrentUser.CreateSubKey(keyPath))
                {
                    if (key != null)
                    {
                        key.SetValue("DisplayName", "Cafe Print Agent (نظام طباعة الكافيه)");
                        key.SetValue("DisplayVersion", "1.0.0");
                        key.SetValue("Publisher", "Cafe System Engineering");
                        key.SetValue("InstallLocation", installDir);
                        key.SetValue("UninstallString", "\"" + Path.Combine(installDir, "uninstall.bat") + "\"");
                        key.SetValue("DisplayIcon", Path.Combine(installDir, "Cafe Print Agent.exe") + ",0");
                        key.SetValue("URLInfoAbout", "https://sana-beach-uno.com");
                    }
                }
            }
            catch { }
        }

        [STAThread]
        public static void Main(string[] args)
        {
            // Silent install support
            bool isSilent = false;
            foreach (var arg in args)
            {
                if (arg.Equals("/S", StringComparison.OrdinalIgnoreCase) || arg.Equals("/silent", StringComparison.OrdinalIgnoreCase))
                {
                    isSilent = true;
                }
            }

            if (isSilent)
            {
                try
                {
                    var form = new InstallerForm();
                    form.PerformInstallation(form.defaultInstallDir);
                    if (File.Exists(Path.Combine(form.defaultInstallDir, "Cafe Print Agent.exe")))
                    {
                        Process.Start(new ProcessStartInfo
                        {
                            FileName = Path.Combine(form.defaultInstallDir, "Cafe Print Agent.exe"),
                            Arguments = "--hidden",
                            WorkingDirectory = form.defaultInstallDir
                        });
                    }
                }
                catch { }
                return;
            }

            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new InstallerForm());
        }
    }
}
