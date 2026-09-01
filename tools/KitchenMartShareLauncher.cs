using System;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Net;
using System.Net.NetworkInformation;
using System.Text.RegularExpressions;
using System.Threading;
using System.Windows.Forms;

namespace KitchenMartShareLauncher
{
    internal sealed class MainForm : Form
    {
        private const int WebPort = 8000;
        private const int MySqlPort = 3306;
        private readonly string projectDirectory = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\');
        private readonly Label statusLabel = new Label();
        private readonly LinkLabel publicUrlLink = new LinkLabel();
        private readonly Button startButton = new Button();
        private readonly Button copyButton = new Button();
        private readonly Button openButton = new Button();
        private readonly Button stopButton = new Button();
        private readonly TextBox activityLog = new TextBox();
        private Process phpProcess;
        private Process cloudflaredProcess;
        private string publicUrl = string.Empty;
        private string pendingPublicUrl = string.Empty;
        private int tunnelConnected;
        private int readinessCheckRunning;

        public MainForm()
        {
            Text = "KitchenMart Share";
            ClientSize = new Size(630, 410);
            MinimumSize = new Size(630, 410);
            StartPosition = FormStartPosition.CenterScreen;
            Font = new Font("Segoe UI", 9F, FontStyle.Regular, GraphicsUnit.Point);
            BackColor = Color.FromArgb(247, 246, 240);
            FormClosing += OnFormClosing;
            Shown += delegate { StartSharing(); };

            var title = new Label { Text = "KitchenMart Server & Share", Font = new Font("Georgia", 20F, FontStyle.Bold), ForeColor = Color.FromArgb(23, 63, 50), AutoSize = true, Location = new Point(30, 25) };
            var subtitle = new Label { Text = "เปิดเว็บในเครื่อง และสร้างลิงก์ Cloudflare สำหรับแชร์ให้เพื่อน", ForeColor = Color.FromArgb(91, 108, 99), AutoSize = true, Location = new Point(32, 64) };
            Controls.Add(title);
            Controls.Add(subtitle);

            startButton.Text = "เริ่มเซิร์ฟเวอร์และแชร์";
            startButton.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            startButton.BackColor = Color.FromArgb(23, 63, 50);
            startButton.ForeColor = Color.White;
            startButton.FlatStyle = FlatStyle.Flat;
            startButton.FlatAppearance.BorderSize = 0;
            startButton.Size = new Size(225, 44);
            startButton.Location = new Point(30, 102);
            startButton.Click += delegate { StartSharing(); };
            Controls.Add(startButton);

            statusLabel.Text = "พร้อมเริ่มต้น";
            statusLabel.ForeColor = Color.FromArgb(69, 105, 84);
            statusLabel.AutoSize = true;
            statusLabel.Location = new Point(276, 117);
            Controls.Add(statusLabel);

            var linkPanel = new Panel { Location = new Point(30, 168), Size = new Size(570, 88), BackColor = Color.White, BorderStyle = BorderStyle.FixedSingle };
            var linkCaption = new Label { Text = "ลิงก์สำหรับแชร์", ForeColor = Color.FromArgb(96, 109, 102), AutoSize = true, Location = new Point(15, 12) };
            publicUrlLink.Text = "ยังไม่ได้สร้างลิงก์";
            publicUrlLink.Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            publicUrlLink.AutoSize = true;
            publicUrlLink.Location = new Point(15, 34);
            publicUrlLink.LinkColor = Color.FromArgb(29, 99, 69);
            publicUrlLink.Click += delegate { OpenPublicUrl(); };
            linkPanel.Controls.Add(linkCaption);
            linkPanel.Controls.Add(publicUrlLink);
            Controls.Add(linkPanel);

            copyButton.Text = "คัดลอกลิงก์";
            copyButton.Size = new Size(112, 33);
            copyButton.Location = new Point(30, 273);
            copyButton.Enabled = false;
            copyButton.Click += delegate { CopyPublicUrl(); };
            Controls.Add(copyButton);

            openButton.Text = "เปิดในเบราว์เซอร์";
            openButton.Size = new Size(135, 33);
            openButton.Location = new Point(151, 273);
            openButton.Enabled = false;
            openButton.Click += delegate { OpenPublicUrl(); };
            Controls.Add(openButton);

            stopButton.Text = "หยุดการแชร์";
            stopButton.Size = new Size(112, 33);
            stopButton.Location = new Point(488, 273);
            stopButton.Enabled = false;
            stopButton.Click += delegate { StopSharing(); };
            Controls.Add(stopButton);

            activityLog.ReadOnly = true;
            activityLog.Multiline = true;
            activityLog.ScrollBars = ScrollBars.Vertical;
            activityLog.BackColor = Color.FromArgb(32, 48, 42);
            activityLog.ForeColor = Color.FromArgb(220, 234, 225);
            activityLog.BorderStyle = BorderStyle.None;
            activityLog.Font = new Font("Consolas", 8.5F);
            activityLog.Location = new Point(30, 321);
            activityLog.Size = new Size(570, 66);
            Controls.Add(activityLog);

            Log("กดปุ่มเริ่มเพื่อเปิด KitchenMart และ Cloudflare Quick Tunnel");
        }

        private void StartSharing()
        {
            startButton.Enabled = false;
            ThreadPool.QueueUserWorkItem(delegate
            {
                try
                {
                    SetStatus("กำลังเริ่มบริการ...");
                    EnsureMySql();
                    EnsurePhpServer();
                    StartCloudflareTunnel();
                }
                catch (Exception ex)
                {
                    Log("เกิดข้อผิดพลาด: " + ex.Message);
                    SetStatus("ไม่สามารถเริ่มได้");
                    Invoke(new MethodInvoker(delegate { startButton.Enabled = true; }));
                }
            });
        }

        private void EnsureMySql()
        {
            if (PortIsListening(MySqlPort)) { Log("MySQL พร้อมใช้งานที่พอร์ต 3306"); return; }
            const string mysqlExe = @"C:\xampp\mysql\bin\mysqld.exe";
            const string mysqlIni = @"C:\xampp\mysql\bin\my.ini";
            if (!File.Exists(mysqlExe) || !File.Exists(mysqlIni)) throw new FileNotFoundException("ไม่พบ XAMPP MySQL ที่ C:\\xampp\\mysql");
            Process.Start(new ProcessStartInfo(mysqlExe, "--defaults-file=" + mysqlIni) { UseShellExecute = false, CreateNoWindow = true, WindowStyle = ProcessWindowStyle.Hidden, WorkingDirectory = projectDirectory });
            Log("กำลังเปิด MySQL...");
            WaitForPort(MySqlPort, "MySQL");
        }

        private void EnsurePhpServer()
        {
            if (PortIsListening(WebPort)) { Log("เว็บเซิร์ฟเวอร์พร้อมใช้งานที่ http://127.0.0.1:8000"); return; }
            const string phpExe = @"C:\xampp\php\php.exe";
            if (!File.Exists(phpExe)) throw new FileNotFoundException("ไม่พบ PHP ของ XAMPP ที่ C:\\xampp\\php");
            phpProcess = Process.Start(new ProcessStartInfo(phpExe, "-S 127.0.0.1:8000") { UseShellExecute = false, CreateNoWindow = true, WindowStyle = ProcessWindowStyle.Hidden, WorkingDirectory = projectDirectory });
            Log("กำลังเปิด KitchenMart ที่ http://127.0.0.1:8000");
            WaitForPort(WebPort, "เว็บเซิร์ฟเวอร์");
        }

        private void StartCloudflareTunnel()
        {
            if (cloudflaredProcess != null && !cloudflaredProcess.HasExited) { Log("Cloudflare Tunnel กำลังทำงานอยู่แล้ว กำลังตรวจสอบลิงก์อีกครั้ง..."); TryStartTunnelReadinessCheck(); return; }
            string cloudflaredExe = File.Exists(@"C:\Program Files (x86)\cloudflared\cloudflared.exe") ? @"C:\Program Files (x86)\cloudflared\cloudflared.exe" : @"C:\Program Files\cloudflared\cloudflared.exe";
            if (!File.Exists(cloudflaredExe)) throw new FileNotFoundException("ไม่พบ cloudflared กรุณาติดตั้ง Cloudflare Tunnel ก่อน");
            publicUrl = string.Empty;
            pendingPublicUrl = string.Empty;
            Interlocked.Exchange(ref tunnelConnected, 0);
            Interlocked.Exchange(ref readinessCheckRunning, 0);
            // HTTP/2 uses TCP and is more reliable than QUIC on school/public Wi-Fi
            // where outbound UDP 7844 is commonly blocked.
            var info = new ProcessStartInfo(cloudflaredExe, "tunnel --url http://127.0.0.1:8000 --protocol http2 --no-autoupdate") { UseShellExecute = false, CreateNoWindow = true, WindowStyle = ProcessWindowStyle.Hidden, RedirectStandardOutput = true, RedirectStandardError = true, WorkingDirectory = projectDirectory };
            cloudflaredProcess = new Process { StartInfo = info, EnableRaisingEvents = true };
            cloudflaredProcess.OutputDataReceived += OnCloudflaredOutput;
            cloudflaredProcess.ErrorDataReceived += OnCloudflaredOutput;
            cloudflaredProcess.Exited += delegate { if (!IsDisposed) { Log("Cloudflare Tunnel หยุดทำงาน"); SetStatus("การแชร์หยุดแล้ว"); } };
            cloudflaredProcess.Start();
            cloudflaredProcess.BeginOutputReadLine();
            cloudflaredProcess.BeginErrorReadLine();
            Log("กำลังขอลิงก์ Cloudflare สำหรับแชร์...");
            SetStatus("กำลังสร้างลิงก์สาธารณะ...");
        }

        private void OnCloudflaredOutput(object sender, DataReceivedEventArgs e)
        {
            if (String.IsNullOrEmpty(e.Data)) return;
            Match match = Regex.Match(e.Data, "https://[a-z0-9-]+\\.trycloudflare\\.com", RegexOptions.IgnoreCase);
            if (match.Success)
            {
                pendingPublicUrl = match.Value;
                BeginInvoke(new MethodInvoker(delegate
                {
                    publicUrlLink.Text = "กำลังตรวจสอบ DNS และการเชื่อมต่อ...";
                    copyButton.Enabled = false;
                    openButton.Enabled = false;
                    stopButton.Enabled = true;
                    SetStatus("กำลังเตรียมลิงก์ Cloudflare...");
                    Log("Cloudflare สร้าง URL แล้ว กำลังรอให้ลิงก์พร้อมใช้งาน...");
                }));
                TryStartTunnelReadinessCheck();
            }
            else if (e.Data.IndexOf("Registered tunnel connection", StringComparison.OrdinalIgnoreCase) >= 0)
            {
                Interlocked.Exchange(ref tunnelConnected, 1);
                Log("เชื่อมต่อ Cloudflare สำเร็จ กำลังตรวจสอบ DNS และหน้าเว็บ...");
                TryStartTunnelReadinessCheck();
            }
            else if (e.Data.IndexOf(" ERR ", StringComparison.OrdinalIgnoreCase) >= 0 || e.Data.IndexOf(" WRN ", StringComparison.OrdinalIgnoreCase) >= 0)
            {
                Log("Cloudflare: " + e.Data);
            }
        }

        private void TryStartTunnelReadinessCheck()
        {
            if (String.IsNullOrEmpty(pendingPublicUrl) || Interlocked.CompareExchange(ref tunnelConnected, 0, 0) != 1) return;
            if (Interlocked.CompareExchange(ref readinessCheckRunning, 1, 0) != 0) return;
            ThreadPool.QueueUserWorkItem(delegate
            {
                string candidate = pendingPublicUrl;
                try
                {
                    for (int attempt = 1; attempt <= 90; attempt++)
                    {
                        if (cloudflaredProcess == null || cloudflaredProcess.HasExited || candidate != pendingPublicUrl) return;
                        if (PublicUrlIsReady(candidate))
                        {
                            publicUrl = candidate;
                            BeginInvoke(new MethodInvoker(delegate
                            {
                                publicUrlLink.Text = publicUrl;
                                copyButton.Enabled = true;
                                openButton.Enabled = true;
                                stopButton.Enabled = true;
                                startButton.Enabled = false;
                                SetStatus("พร้อมแชร์ให้เพื่อนแล้ว");
                                Log("ตรวจสอบสำเร็จ ลิงก์พร้อมใช้งานจริง: " + publicUrl);
                            }));
                            return;
                        }
                        if (attempt == 1 || attempt % 10 == 0) Log("กำลังรอ DNS ของ Cloudflare... (ครั้งที่ " + attempt + ")");
                        Thread.Sleep(2000);
                    }
                    BeginInvoke(new MethodInvoker(delegate
                    {
                        publicUrlLink.Text = "ลิงก์ยังไม่พร้อม กรุณากดเริ่มเพื่อตรวจสอบใหม่";
                        startButton.Enabled = true;
                        SetStatus("Cloudflare เชื่อมต่อแล้ว แต่ DNS ยังไม่พร้อม");
                        Log("รอ DNS เกิน 3 นาที กรุณาตรวจสอบอินเทอร์เน็ตแล้วกดเริ่มอีกครั้ง");
                    }));
                }
                finally { Interlocked.Exchange(ref readinessCheckRunning, 0); }
            });
        }

        private static bool PublicUrlIsReady(string url)
        {
            try
            {
                Uri uri = new Uri(url);
                if (Dns.GetHostAddresses(uri.Host).Length == 0) return false;
                ServicePointManager.SecurityProtocol = (SecurityProtocolType)3072;
                HttpWebRequest request = (HttpWebRequest)WebRequest.Create(uri);
                request.Method = "GET";
                request.Timeout = 5000;
                request.ReadWriteTimeout = 5000;
                request.AllowAutoRedirect = true;
                request.Proxy = null;
                request.UserAgent = "KitchenMart-Share-HealthCheck/1.0";
                using (HttpWebResponse response = (HttpWebResponse)request.GetResponse())
                {
                    int status = (int)response.StatusCode;
                    return status >= 200 && status < 400;
                }
            }
            catch { return false; }
        }

        private void StopSharing()
        {
            StopProcess(cloudflaredProcess);
            StopProcess(phpProcess);
            cloudflaredProcess = null;
            phpProcess = null;
            publicUrl = string.Empty;
            pendingPublicUrl = string.Empty;
            Interlocked.Exchange(ref tunnelConnected, 0);
            publicUrlLink.Text = "หยุดการแชร์แล้ว";
            copyButton.Enabled = false;
            openButton.Enabled = false;
            stopButton.Enabled = false;
            startButton.Enabled = true;
            SetStatus("หยุดการแชร์แล้ว");
            Log("หยุด Cloudflare Tunnel และ PHP Server ที่โปรแกรมเปิดเองแล้ว");
        }

        private void OnFormClosing(object sender, FormClosingEventArgs e)
        {
            if (cloudflaredProcess != null && !cloudflaredProcess.HasExited)
            {
                if (MessageBox.Show("เมื่อปิดโปรแกรม ลิงก์ Cloudflare จะหยุดทำงาน ต้องการปิดหรือไม่?", "หยุดการแชร์", MessageBoxButtons.YesNo, MessageBoxIcon.Question) != DialogResult.Yes) { e.Cancel = true; return; }
                StopSharing();
            }
        }

        private void CopyPublicUrl()
        {
            if (String.IsNullOrEmpty(publicUrl)) return;
            Clipboard.SetText(publicUrl);
            SetStatus("คัดลอกลิงก์แล้ว");
        }

        private void OpenPublicUrl()
        {
            if (!String.IsNullOrEmpty(publicUrl)) Process.Start(publicUrl);
        }

        private static void StopProcess(Process process)
        {
            if (process == null) return;
            try { if (!process.HasExited) process.Kill(); } catch { }
        }

        private static bool PortIsListening(int port)
        {
            foreach (System.Net.IPEndPoint endpoint in IPGlobalProperties.GetIPGlobalProperties().GetActiveTcpListeners()) if (endpoint.Port == port) return true;
            return false;
        }

        private void WaitForPort(int port, string serviceName)
        {
            for (int attempt = 0; attempt < 20; attempt++)
            {
                if (PortIsListening(port)) { Log(serviceName + " พร้อมใช้งาน"); return; }
                Thread.Sleep(250);
            }
            throw new InvalidOperationException(serviceName + " ไม่ตอบสนองที่พอร์ต " + port);
        }

        private void Log(string message)
        {
            if (IsDisposed) return;
            if (InvokeRequired) { BeginInvoke(new MethodInvoker(delegate { Log(message); })); return; }
            activityLog.AppendText("[" + DateTime.Now.ToString("HH:mm:ss") + "] " + message + Environment.NewLine);
        }

        private void SetStatus(string message)
        {
            if (IsDisposed) return;
            if (InvokeRequired) { BeginInvoke(new MethodInvoker(delegate { SetStatus(message); })); return; }
            statusLabel.Text = message;
        }
    }

    internal static class Program
    {
        [STAThread]
        private static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new MainForm());
        }
    }
}
