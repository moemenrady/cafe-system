@echo off
:: التأكد من صلاحيات الآدمن
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [خطأ] لازم تضغط كليك يمين وتختار Run as Administrator!
    pause
    exit /b
)

set SERVICE_NAME=CafePrintAgent

echo ====================================================
echo   إلغاء تثبيت خدمة طابعات الكافيه (POS Print Service)
echo ====================================================

nssm stop %SERVICE_NAME% >nul 2>&1
nssm remove %SERVICE_NAME% confirm >nul 2>&1

echo [تم بنجاح] تم إيقاف وإزالة خدمة %SERVICE_NAME% من النظام.
pause
