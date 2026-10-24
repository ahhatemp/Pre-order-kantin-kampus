@echo off
setlocal
cd /d "%~dp0"

for /f %%a in ('powershell -NoProfile -Command "(Get-NetIPConfiguration ^| Where-Object { $_.IPv4DefaultGateway -ne $null -and $_.NetAdapter.Status -eq 'Up' } ^| Select-Object -First 1).IPv4Address.IPAddress"') do set "IP=%%a"

echo.
if defined IP (
  echo  Buka di HP ^(WiFi yang sama^): http://%IP%:8000/frontend/
) else (
  echo  IP lokal tidak terdeteksi. Cek koneksi WiFi.
)
echo  Di PC: http://localhost:8000/frontend/
echo  Tekan Ctrl+C untuk berhenti.
echo.

php -S 0.0.0.0:8000 router.php
endlocal
