# Task List: Sistem Pre-Order Kantin Kampus

> **Status per 2026-10-27.** Dokumen ini mencatat status implementasi aktual (PHP 8 + MySQL +
> HTML/CSS/JS), menggantikan rencana lama Next.js + Express. ID task dipertahankan agar riwayat
> perencanaan tetap bisa dilacak.

## Ringkasan

| Status | Jumlah |
|---|---|
| Selesai | 18 |
| Sebagian | 1 (T-18) |
| Ditunda ke v2 | 1 (T-05) |
| **Total** | **20** |

Stack aktual: PHP native + PDO/MySQL, frontend HTML/CSS/JS tanpa build step, Midtrans Snap
(opsional saat demo), Web Push (VAPID + service worker).

---

## Modul 1: Setup & Autentikasi

### T-01: Setup frontend — Selesai
- **Implementasi aktual:** HTML + CSS + JavaScript polos, satu file per layar, tanpa framework/build.
- **Bukti:** `frontend/*.html`, `frontend/css/styles.css`, `frontend/js/api.js`, `frontend/js/app.js`.
- **Catatan:** Rencana Next.js + Tailwind + shadcn/ui tidak dipakai.

### T-02: Setup backend — Selesai
- **Implementasi aktual:** PHP 8 native + PDO, front controller dan tabel route manual.
- **Bukti:** `backend/api/index.php`, `backend/config.php`, `backend/db.php`, `backend/helpers.php`.
- **Catatan:** Rencana Express + TypeScript + mysql2 tidak dipakai.

### T-03: Migrasi tabel `users` — Selesai
- **Bukti:** `backend/migrations/001_create_users.sql`.
- **Isi:** PK `INT`, NIM unik & nullable, email unik, password bcrypt, role enum, `created_at`.

### T-04: API auth + role guard — Selesai
- **Bukti:** `backend/controllers/auth.php`, `backend/auth.php`, `backend/rate_limit.php`.
- **Fitur:** register (validasi email/NIM/password), login (JWT 12 jam), `GET /api/auth/me`,
  `require_role('admin')`.

### T-05: Reset password via email — Ditunda ke v2
- **Alasan:** belum ada layanan email yang ditentukan; tidak termasuk scope v1.
- **Catatan:** tersedia utilitas CLI development `backend/reset_password.php <email> <password>`
  (bukan fitur pengguna; halaman reset mandiri tetap v2).

---

## Modul 2: Menu & Stok

### T-06: Migrasi tabel `menus` — Selesai
- **Bukti:** `backend/migrations/002_create_menus.sql`.
- **Isi:** harga `INT`, stok, kategori, gambar, `is_active`, `updated_at`.

### T-07: API menu publik — Selesai
- **Bukti:** `backend/controllers/menus.php`.
- **Fitur:** `GET /api/menus` hanya mengembalikan menu `is_active = 1`, dengan stok terkini.
- **Catatan:** belum ada jadwal menu per tanggal.

### T-08: API admin menu & stok — Selesai
- **Bukti:** `backend/controllers/admin_menus.php`, `backend/controllers/uploads.php`.
- **Fitur:** `POST /api/admin/menus`, `GET /api/admin/menus`, `GET /api/admin/menus/{id}`,
  `PATCH /api/admin/menus/{id}` (termasuk `is_active`), `PATCH /api/admin/menus/{id}/stock`,
  `DELETE /api/admin/menus/{id}`, `POST /api/admin/menus/upload`
  (multipart JPG/PNG/WebP ≤ 5 MB), `GET /api/uploads/{file}`.
- **Catatan:** hapus menu bersifat lunak (`deleted_at`), sehingga riwayat order lama tetap
  menampilkan nama menu. Menu nonaktif (`is_active = 0`) tetap muncul di halaman admin.
  Foto upload disimpan acak di `backend/storage/uploads/` dan disajikan lewat API.

---

## Modul 3: Pre-Order & Pembayaran

### T-09: Migrasi `orders` & `order_items` — Selesai
- **Bukti:** `backend/migrations/003_create_orders.sql`.
- **Isi:** `order_code`, `pickup_at`, `expires_at`, status enum, snapshot `price_at_order`,
  foreign key + index.

### T-10: API create order + anti-oversell — Selesai
- **Bukti:** `backend/controllers/orders.php`, `backend/services/order.php`.
- **Fitur:** validasi item (maks 10 baris, qty 1–50), pickup 5 menit–7 hari ke depan serta harus
  pada hari buka dan dalam jam operasional `kantin_hours`, batas 3 order pending per user, transaksi
  `SELECT ... FOR UPDATE`, potong stok, insert order + item, generate `order_code`.
- **Catatan:** slot pickup di UI mengikuti jam operasional: per jam mulai `max(12:00, jam buka)`
  sampai jam tutup, hanya untuk hari buka pertama yang masih punya slot.

### T-11: Integrasi Midtrans Snap + webhook — Selesai
- **Bukti:** `backend/services/midtrans.php`, `backend/controllers/payments.php`,
  `backend/api/index.php`, `frontend/js/app.js`.
- **Fitur:** Snap Token saat order dibuat, `GET /api/payments/config`, webhook dengan validasi
  signature SHA-512 dan `gross_amount`, penanganan pembayaran terlambat untuk order `cancelled`.
- **Mode demo:** tanpa server key, backend memakai token dummy, order langsung `paid`, dan frontend melewati Snap.

### T-12: API riwayat & detail order — Selesai
- **Bukti:** `backend/controllers/orders.php`.
- **Fitur:** `GET /api/orders/me` (riwayat + item), `GET /api/orders/{id}` (hanya milik user).

---

## Modul 4: Admin Dashboard & Order Lifecycle

### T-13: API daftar order admin — Selesai
- **Bukti:** `backend/controllers/admin_orders.php`.
- **Fitur:** `GET /api/admin/orders` dengan filter `?pickup_date=YYYY-MM-DD`, data pemesan,
  item, urut `pickup_at`; frontend menampilkan daftar datar urut pesanan terbaru dengan filter
  status (belum memakai `pickup_date` dan belum mengelompokkan per slot/tanggal).

### T-14: API update status order — Selesai
- **Bukti:** `backend/controllers/admin_orders.php`.
- **Transisi sah:** `pending → cancelled`, `paid → ready|cancelled`, `ready → completed`.
  `pending → paid` hanya otomatis: webhook Midtrans atau checkout demo tanpa Midtrans.
- **Stok:** dikembalikan saat order dibatalkan.

### T-15: Auto-cancel order kedaluwarsa — Selesai
- **Bukti:** `backend/services/order_expiry.php`, dipanggil dari `backend/api/index.php`.
- **Mekanisme:** sweep di sela request, lock file maksimal sekali per 60 detik, transaksi
  `FOR UPDATE`, stok dikembalikan, status menjadi `cancelled`.
- **Catatan:** tidak ada endpoint manual `sweep-expired` dan tidak memakai cron.

---

## Modul 5: Frontend UI & Polish

### T-16: UI mahasiswa — Selesai
- **Bukti:** `frontend/masuk.html`, `menu.html`, `keranjang.html`, `pembayaran.html`,
  `sukses.html`, `pesanan.html`, `lacak.html`, `akun-mahasiswa.html`, `frontend/js/*.js`.
- **Fitur:** login/daftar, cari menu, keranjang, pilih slot pickup, Midtrans Snap, riwayat,
  timeline tracking dengan refresh 30 detik.

### T-17: UI admin — Selesai
- **Bukti:** `frontend/admin-pesanan.html`, `frontend/admin-stok.html`, `frontend/js/app.js`.
- **Fitur:** papan pesanan dengan filter status, tombol Siap/Selesai (pembatalan masih lewat API,
  belum ada tombol Batal di UI), kelola stok dan status aktif menu, tambah/edit menu lewat bottom
  sheet (nama, harga, kategori, deskripsi, foto), unggah foto dari galeri atau kamera langsung
  (`getUserMedia` + canvas, fallback `capture` di HP) dengan pratinjau dan validasi tipe/ukuran,
  refresh 30 detik.

### T-18: Security & performance — Sebagian
- **Sudah:** rate limit order, JWT + role guard, prepared statement, transaksi anti-oversell,
  proteksi file sensitif via `.htaccess`, uji mandiri JWT/rate limiter.
- **Belum:** load test konkurensi, audit responsive 320/768/1024 px, pengukuran page load,
  test end-to-end, pembatasan CORS produksi, penggantian `JWT_SECRET` default.
- **Bukti:** `backend/tests/jwt_test.php`, `backend/.htaccess`.

---

## Modul 6: Notifikasi & Pengaturan Kantin

### T-19: Web Push + PWA — Selesai
- **Bukti:** `backend/controllers/push.php`, `backend/services/push.php`,
  `backend/migrations/005_create_push_subscriptions.sql`, `frontend/sw.js`,
  `frontend/notifikasi.html`, `frontend/js/app.js`.
- **Fitur:** `GET /api/push/public-key`, `POST /api/push/subscribe` (upsert per endpoint),
  `POST /api/push/unsubscribe`; validasi allowlist host gateway serta panjang `p256dh`/`auth`;
  payload terenkripsi `aes128gcm` dengan tanda tangan VAPID ES256; push dikirim setelah admin
  menandai order `ready` (`register_shutdown_function`), subscription 404/410 dihapus otomatis.
- **Catatan:** kunci VAPID dibuat otomatis di `backend/storage/vapid.json` (gitignored, `0600`,
  seluruh `storage/` diblokir `.htaccess`); lokasi bisa dipindah lewat `VAPID_KEY_FILE`. Butuh
  HTTPS atau localhost. Push konfirmasi order (`paid`) dan email/FCM tetap v2.

### T-20: Profil & jam operasional kantin — Selesai
- **Bukti:** `backend/controllers/kantin.php`, `backend/services/kantin_hours.php`,
  `backend/migrations/006_create_kantin_profile.sql`, `007_create_kantin_hours.sql`,
  `frontend/profil-kantin.html`, `frontend/jam-operasional.html`, `frontend/js/app.js`.
- **Fitur:** `GET /api/kantin/profile`, `GET /api/kantin/hours`,
  `PATCH /api/admin/kantin/profile`, `PATCH /api/admin/kantin/hours`; validasi panjang field,
  wajib 7 hari unik, format `HH:MM`, dan `open < close`; UI admin mengedit keduanya, halaman
  Bantuan memakai kontak dari profil, slot pickup mengikuti jam operasional.
- **Catatan:** hanya satu kantin (`kantin_profile.id = 1`); jadwal menu per tanggal dan
  pengaturan slot pickup oleh admin tetap v2.

---

## Deferred ke v2

- Reset password via email (halaman mandiri) dan verifikasi email registrasi. Utilitas CLI
  development `backend/reset_password.php` sudah ada.
- Push/email konfirmasi order (`paid`). Web Push untuk status `ready` sudah selesai di v1.
- Invoice PDF.
- Rating/review menu, loyalty points, multi-kantin, split payment.
- Dark mode dan audit aksesibilitas menyeluruh.
- Jadwal menu per tanggal dan pengaturan slot pickup oleh admin.

## Verifikasi Cepat

```bash
cd backend
php tests/jwt_test.php     # self-check JWT & rate limiter
php check_db.php           # cek koneksi + jumlah baris tabel
php migrate.php            # idempotent: seed dilewati bila users sudah terisi
php -S 127.0.0.1:5000 -t . api/index.php
```

```bash
cd frontend
php -S 127.0.0.1:8080
```

Alternatif satu port dari root repo (dipakai untuk akses HP/tunnel):

```bash
php -S 0.0.0.0:8000 router.php
```
