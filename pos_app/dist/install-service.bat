@echo off
:: التأكد من صلاحيات الآدمن
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [خطأ] لازم تضغط كليك يمين وتختار Run as Administrator!
    pause
    exit /b
)

set SERVICE_NAME=CafePrintAgent
set BASE_DIR=%~dp0
set EXE_PATH=%BASE_DIR%pos-agent.exe

echo ====================================================
echo   تثبيت خدمة طابعات الكافيه (POS Print Service)
echo ====================================================

:: إيقاف وحذف أي خدمة قديمة بنفس الاسم إن وجدت
nssm stop %SERVICE_NAME% >nul 2>&1
nssm remove %SERVICE_NAME% confirm >nul 2>&1

:: تثبيت الخدمة وتحديد مسار التشغيل
nssm install %SERVICE_NAME% "%EXE_PATH%"
nssm set %SERVICE_NAME% AppDirectory "%BASE_DIR%"
nssm set %SERVICE_NAME% Start SERVICE_AUTO_START
nssm set %SERVICE_NAME% Description "خدمة إدارة طباعة فواتير وتذاكر الكافيه تلقائياً"

:: إعداد الإنعاش التلقائي عند أي انهيار (Auto-Restart after 5s)
nssm set %SERVICE_NAME% AppRestartDelay 5000

:: فتح البورت في جدار حماية ويندوز تلقائياً
netsh advfirewall firewall add rule name="Cafe POS Agent Port 3210" dir=in action=allow protocol=TCP localport=3210 >nul 2>&1

:: تشغيل الخدمة فوراً
nssm start %SERVICE_NAME%

echo.
echo [تم بنجاح] الخدمة اتثبتت وشغالة في الخلفية تلقائياً مع فتح الويندوز!
echo تقدر تفتح لوحة التحكم على: http://localhost:3210
pause
