@echo off
setlocal
cd /d "%~dp0"

where cloudflared >nul 2>&1
if errorlevel 1 (
  echo cloudflared tidak ditemukan. Install dulu: winget install Cloudflare.cloudflared
  pause
  exit /b 1
)

echo.
echo  Pastikan server lokal sudah jalan (hosting-lokal.cmd).
echo  Link publik muncul di bawah, formatnya: https://xxxx.trycloudflare.com
echo  Buka di HP: https://xxxx.trycloudflare.com/frontend/
echo  Link hanya aktif selama jendela ini terbuka. Tekan Ctrl+C untuk berhenti.
echo.

cloudflared tunnel --url http://localhost:8000 --no-autoupdate
endlocal
