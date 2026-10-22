# Upload repo ke GitHub. Jalankan sekali di PowerShell dari folder ini:
#   .\push-github.ps1
# Butuh git + (gh CLI sudah login, ATAU git credential/PAT tersedia).
$ErrorActionPreference = 'Stop'
$user = 'ahhatemp'
$repo = 'Pre-order-kantin-kampus'
$url  = "https://github.com/$user/$repo.git"
Set-Location $PSScriptRoot

if (-not (Test-Path .git)) { git init -b main | Out-Null }
git config user.name  $user
git config user.email "$user@users.noreply.github.com"

git add -A
if ((git ls-files -- 'backend/.env') -ne $null) { throw 'backend/.env ikut ter-track. Cek .gitignore dulu.' }

git commit -m "Initial commit: pre-order kantin kampus (PHP API + frontend)" 2>$null
if ($LASTEXITCODE -ne 0) { "tidak ada perubahan baru untuk di-commit" }

git remote remove origin 2>$null
git remote add origin $url

if (Get-Command gh -ErrorAction SilentlyContinue) { gh auth setup-git 2>$null }
git push -u origin main

"OK: $url"
"File ter-track: " + (git ls-files).Count
