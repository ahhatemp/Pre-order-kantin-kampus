# Rencana Deployment — InfinityFree (subdomain gratis) + Midtrans Sandbox

## Ringkasan keputusan

| Item | Pilihan |
|---|---|
| Hosting | InfinityFree (gratis), subdomain bawaan (mis. `kantinku.free.nf`) |
| PHP | 8.2 / 8.3 (proyek butuh ≥ 8.0) |
| Database | MySQL InfinityFree + phpMyAdmin |
| Pembayaran | Midtrans **sandbox** |
| Struktur URL | `https://<subdomain>/frontend/` dan `https://<subdomain>/backend/api/` |

## Konsekuensi penting: webhook Midtrans tidak bisa dipakai

InfinityFree memblokir request server-to-server ("Receiving external webhooks is
sadly not possible"), jadi `POST /api/payments/webhook` tidak akan pernah dipanggil
Midtrans. Saat ini status `paid` **hanya** diubah webhook, sehingga pesanan QRIS akan
selalu `pending` lalu batal setelah 10 menit walau sudah dibayar.

Solusi: rekonsiliasi status lewat **Midtrans status API** (outbound cURL didukung
InfinityFree) yang dipicu polling halaman Lacak setiap 5 detik
(`scheduleQrisPolling` di `frontend/js/app.js` sudah memanggil
`GET /api/orders/{id}` tiap 5 detik). Tidak perlu ubah frontend.

Webhook tetap dipertahankan di kode supaya bisa langsung dipakai bila nanti pindah
ke hosting berbayar/VPS.

## Batasan InfinityFree yang memengaruhi deployment

| Batasan | Dampak | Penanganan |
|---|---|---|
| Webhook eksternal diblokir | Midtrans tidak bisa callback | Rekonsiliasi polling (Fase 0) |
| Akses hanya lewat browser (ada halaman verifikasi) | `curl`/Postman bisa kena challenge | Uji manual pakai browser |
| Tanpa SSH/CLI | `php migrate.php` tidak bisa jalan | Import `schema.sql` via phpMyAdmin |
| MySQL tanpa remote access | Migrasi dari laptop tidak bisa | Import/export via phpMyAdmin |
| Upload maks 10 MB (PHP/HTML maks 1 MB) | Menu upload 5 MB, `app.js` 106 KB | Aman |
| 50.000 hits/hari | Polling 5 detik ≈ 720 hit/jam per tab | Cukup untuk demo, jangan buka banyak tab |
| Tanpa cron | Sweep pesanan kedaluwarsa | Sudah jalan di sela request (`maybe_sweep_expired_orders`) |
| Gratis, "not intended for API hosting" | Risiko suspend / tidak cocok produksi | Untuk demo OK; produksi → hosting berbayar |

---

## Fase 0 — Perubahan kode (wajib, sebelum upload)

Tujuan: status pembayaran bisa ditarik dari Midtrans saat halaman Lacak melakukan
polling, tanpa webhook.

### File yang diubah

1. `backend/services/midtrans.php` — tambah `midtrans_fetch_status(string $orderCode): ?array`
   - Lewati kalau `midtrans_mode()` = `demo`/`mismatch`.
   - `GET https://api.sandbox.midtrans.com/v2/{order_code}/status` (atau `api.midtrans.com`
     bila produksi), Basic auth `server_key:`, timeout 5 detik.
   - Kembalikan array body bila HTTP 200/201 dan `status_code` 200/201; selain itu
     `error_log` + `null`.

2. `backend/services/payment.php` (baru) — pindahkan logika penerapan notifikasi keluar
   dari controller:
   - `payment_channel_from_notification()`, `payment_paid_at()`, `payment_detail_columns()`
     (dipindah dari `controllers/payments.php`).
   - `payment_apply_notification(array $body): ?array` — isi blok `capture/settlement`,
     `pending`, `cancel/expire/deny` dari `payments_webhook()` (baris 86–225). Kembalikan
     order yang berubah jadi lunas (untuk push) atau `null`. Logika transaksi
     `FOR UPDATE` + idempotensi tetap seperti sekarang.
   - `payment_reconcile_order(array $order): array` — jika order bukan QRIS atau status
     bukan `pending`, kembalikan apa adanya. Throttle 5 detik per order dengan
     `rate_limit_run('midtrans:' . $order['id'], 1, 5, true)`, panggil
     `midtrans_fetch_status()`, terapkan lewat `payment_apply_notification()`, lalu
     kembalikan row terbaru.

3. `backend/controllers/payments.php` — `payments_webhook()` tinggal: validasi
   `signature_key` (tetap seperti sekarang), panggil `payment_apply_notification()`,
   daftarkan `push_notify_new_order()` lewat `register_shutdown_function`.

4. `backend/controllers/orders.php` — di `orders_detail()`, setelah row order diambil:
   `$order = payment_reconcile_order($order);` (sebelum query items dan `cast_orders`).
   Pastikan `services/payment.php` di-`require_once`.

5. Opsional tapi disarankan — `backend/controllers/admin_orders.php`:
   rekonsiliasi maksimal 5 pesanan QRIS `pending` per request, throttle 20 detik per
   order, supaya papan admin ikut berubah walau halaman mahasiswa sudah ditutup.

6. Opsional — `backend/tests/payment_test.php`: uji tanpa jaringan untuk mode demo,
   signature webhook, dan idempotensi `payment_apply_notification`.

### Verifikasi lokal sebelum upload

1. Set key sandbox di `.env`, jalankan `php -S 127.0.0.1:5000 -t . api/index.php` +
   frontend 8080.
2. Buat pesanan QRIS, bayar lewat `https://simulator.sandbox.midtrans.com/`
   (set status `settlement`).
3. Halaman Lacak harus menampilkan "Lunas" dalam ≤ 5 detik tanpa webhook.
4. Regresi: pesanan tunai, konfirmasi admin, pembatalan kedaluwarsa, upload menu.

---

## Fase 1 — Akun & domain

1. Daftar di InfinityFree, buat hosting baru.
2. Pilih subdomain gratis yang tersedia (mis. `kantinku.free.nf`).
3. Pilih PHP 8.2/8.3 di vPanel.
4. Catat: host FTP, user FTP, password FTP, serta kredensial MySQL.

## Fase 2 — Database

1. vPanel → **MySQL Databases** → buat database. Catat:
   - host: `sqlXXX.infinityfree.com` (bukan `localhost`)
   - nama DB: `if0_XXXXXXXX_kantin`
   - user: `if0_XXXXXXXX`
2. **Cek zona waktu MySQL** di phpMyAdmin:
   ```sql
   SELECT NOW(), @@session.time_zone;
   ```
   Bandingkan dengan jam WIB. Bila server UTC, `expires_at`/`paid_at` akan meleset 7 jam.
   Mitigasi: tambahkan `SET time_zone = '+07:00';` di koneksi PDO (`backend/db.php`).
3. Import skema: buka `backend/schema.sql`, **hapus** baris `CREATE DATABASE ...` dan
   `USE kantin;`, lalu copy sisanya ke tab **SQL** phpMyAdmin (database sudah dipilih).
   Jangan pakai Import file mentah — user MySQL gratis tidak boleh `CREATE DATABASE`.
4. Verifikasi tabel ada: `users`, `menus`, `orders`, `order_items`, `push_subscriptions`,
   `kantin_profile`, `kantin_hours`.
5. Ganti password akun seed (JANGAN biarkan `password123`):
   ```bash
   # di laptop
   php -r "echo password_hash('GantiKeSandiKuat', PASSWORD_BCRYPT), PHP_EOL;"
   ```
   ```sql
   UPDATE users SET password = '<hash>' WHERE email = 'admin@kantin.com';
   UPDATE users SET password = '<hash>' WHERE email = 'user@kantin.com';
   ```
   Akun mahasiswa demo boleh dipertahankan untuk presentasi, admin wajib diganti.
6. Hapus `backend/schema.sql` dari server setelah import (tidak diupload, lihat Fase 3).

## Fase 3 — Upload file

Struktur di server (FTP root berisi `htdocs/`):

```
htdocs/
├── .htaccess              (baru, lihat di bawah)
├── index.html             (redirect ke /frontend/)
├── frontend/              (semua file + .htaccess + sw.js + assets/)
└── backend/
    ├── .htaccess          (wajib, jangan terlewat)
    ├── .env               (dibuat di Fase 4)
    ├── openssl.cnf
    ├── api/ controllers/ services/
    ├── auth.php config.php db.php helpers.php rate_limit.php
    └── storage/ (.htaccess, uploads/, ratelimit/)
```

**Jangan diupload:** `.git/`, `.agents/`, `graphify-out/`, `backend/migrations/`,
`backend/schema.sql`, `backend/migrate.php`, `backend/reset_password.php`,
`backend/check_db.php`, `backend/tests/`, semua `*.md`, `*.cmd`, `*.ps1`,
`.qr_test_url.txt`, `query`, `router.php`, `push-github.ps1`.

**Wajib ikut terupload** (file dotfile sering tersembunyi di FTP client):
`backend/.htaccess`, `backend/storage/.htaccess`, `frontend/.htaccess`.

`htdocs/.htaccess`:

```apache
Options -Indexes
<FilesMatch "^\.">
  Require all denied
</FilesMatch>
RedirectMatch 302 ^/$ /frontend/
```

`htdocs/index.html`: cukup berisi
`<meta http-equiv="refresh" content="0; url=/frontend/">`.

Kenapa tetap memakai `/frontend/`: `API_BASE` di `frontend/js/api.js` otomatis menebak
`<origin>/backend/api` dari pola path `/frontend/`, jadi tidak perlu ubah kode.

## Fase 4 — Konfigurasi `.env`

Buat `htdocs/backend/.env` lewat File Manager (atau FTP):

```dotenv
DB_HOST=sqlXXX.infinityfree.com
DB_NAME=if0_XXXXXXXX_kantin
DB_USER=if0_XXXXXXXX
DB_PASSWORD=<password DB>

JWT_SECRET=<64 hex acak>
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false

RATE_LIMIT_ORDER_MAX=20
ORDER_PENDING_MAX=3

VAPID_SUBJECT=mailto:<email-kamu>
VAPID_KEY_FILE=
```

Generate `JWT_SECRET` di laptop:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

> `MIDTRANS_IS_PRODUCTION` harus `false` dan cocok dengan prefix `SB-`; bila tidak,
> `orders_create` menolak QRIS dan webhook membalas 503 (fail closed).

## Fase 5 — SSL & boot pertama

1. vPanel → **Free SSL Certificates** → terbitkan untuk subdomain, lalu aktifkan
   **Force HTTPS**. HTTPS wajib untuk service worker/web push dan halaman QR.
2. Buka `https://<subdomain>/frontend/` di browser (bukan curl) — selesaikan halaman
   verifikasi InfinityFree bila muncul.
3. Cek `https://<subdomain>/backend/api/health` → `{"status":"ok",...}`.
4. Buka `https://<subdomain>/backend/api/push/public-key` sekali untuk membuat
   `backend/storage/vapid.json`, lalu **unduh salinan** file itu (jangan digenerate
   ulang — semua subscription push lama akan mati).
5. Uji proteksi: `https://<subdomain>/backend/.env` dan
   `https://<subdomain>/backend/storage/vapid.json` harus **403**, bukan menampilkan isi.
6. Cek ekstensi PHP dengan file sementara `htdocs/cek.php`:
   ```php
   <?php var_dump(PHP_VERSION, extension_loaded('pdo_mysql'), extension_loaded('curl'),
     extension_loaded('openssl'), extension_loaded('mbstring'), extension_loaded('fileinfo'),
     ini_get('upload_max_filesize'), ini_get('post_max_size'), date_default_timezone_get());
   ```
   Wajib: PHP ≥ 8.0, semua ekstensi `true`, upload ≥ 6M. **Hapus `cek.php` setelahnya.**

## Fase 6 — Midtrans sandbox

1. Masukkan Server Key & Client Key sandbox ke `.env` (Fase 4).
2. **Notification URL: biarkan kosong** di dashboard Midtrans — webhook tidak akan bisa
   masuk ke InfinityFree. QRIS tetap terkonfirmasi lewat rekonsiliasi polling.
3. Uji alur QRIS dengan simulator sandbox; status `settlement` harus muncul di aplikasi
   dalam ≤ 5 detik pada halaman Lacak.
4. Saat nanti pindah ke hosting berbayar: isi notification URL
   `https://<domain>/backend/api/payments/webhook`, kode sudah siap.

## Fase 7 — Checklist verifikasi end-to-end

- [ ] `/frontend/` tampil, asset CSS/JS/gambar termuat, tidak ada error console
- [ ] `/backend/api/health` OK dan jam WIB
- [ ] Login admin & mahasiswa berhasil (password baru)
- [ ] Daftar menu tampil + foto menu tampil
- [ ] Upload foto menu ≤ 5 MB berhasil dan tampil via `/api/uploads/...`
- [ ] Pesanan tunai dibuat → muncul di papan admin → admin konfirmasi → status mahasiswa berubah
- [ ] Pesanan QRIS dibuat → QR tampil di halaman Lacak
- [ ] Simulator Midtrans `settlement` → halaman Lacak jadi Lunas ≤ 5 detik
- [ ] Notifikasi push masuk ke admin (Chrome/Edge desktop; iOS Safari terbatas)
- [ ] Pesanan QRIS dibiarkan 10 menit → batal otomatis, stok kembali
- [ ] `backend/.env`, `backend/storage/*`, `.git/` tidak dapat diakses dari browser
- [ ] Restart browser / hard reload → sesi masih normal

## Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Webhook Midtrans diblokir | Rekonsiliasi polling 5 detik (Fase 0); webhook disiapkan untuk hosting berbayar |
| Halaman verifikasi anti-bot muncul saat demo | Buka dulu di browser sebelum presentasi; hindari akses via alat otomatis |
| Host gratis di-suspend / kena limit hit | Pantau hits; untuk pemakaian nyata pindah ke shared hosting ± Rp 15–30 rb/bln |
| `vapid.json` hilang saat redeploy | Simpan backup; jangan generate ulang kecuali siap semua user subscribe ulang |
| Zona waktu MySQL UTC | Cek di Fase 2; set `SET time_zone='+07:00'` di `db.php` bila perlu |
| `fileinfo`/`curl`/`openssl` mati | Cek `cek.php` di Fase 5; bila mati, host ini tidak layak, pindah host |
| Upload foto > 10 MB ditolak host | Sudah dibatasi 5 MB oleh aplikasi |

## Rollback

1. Export database sebelum perubahan besar: phpMyAdmin → **Export** → simpan `.sql`.
2. Simpan arsip versi file yang sedang berjalan sebelum redeploy.
3. Bila deploy gagal: upload ulang arsip sebelumnya, jangan menimpa `backend/.env`
   dan `backend/storage/vapid.json`.
4. Perubahan kode Fase 0 aman di-rollback sendiri (webhook tetap berfungsi di host lain).

## Rencana cadangan bila host gratis bermasalah

- **Cloudflare Tunnel** (sudah ada `tunnel-publik.cmd` + `router.php`): jalankan
  `hosting-lokal.cmd` + `tunnel-publik.cmd`, akses `https://xxxx.trycloudflare.com/frontend/`.
  Cocok sebagai cadangan saat presentasi; laptop harus menyala.
- **Shared hosting cPanel** (berbayar murah): `.htaccess` sudah kompatibel, webhook
  Midtrans bisa langsung dipakai, cukup isi notification URL.
