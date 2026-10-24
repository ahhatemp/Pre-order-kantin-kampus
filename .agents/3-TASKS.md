# Task List: Sistem Pre-Order Kantin Kampus

> **Status per 2026-10-24.** Dokumen ini mencatat status implementasi aktual (PHP 8 + MySQL +
> HTML/CSS/JS), menggantikan rencana lama Next.js + Express. ID task dipertahankan agar riwayat
> perencanaan tetap bisa dilacak.

## Ringkasan

| Status | Jumlah |
|---|---|
| Selesai | 16 |
| Sebagian | 1 (T-18) |
| Ditunda ke v2 | 1 (T-05) |
| **Total** | **18** |

Stack aktual: PHP native + PDO/MySQL, frontend HTML/CSS/JS tanpa build step, Midtrans Snap
(opsional saat demo), tanpa push notification.

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
  `require_role('admin')`, rate limit login 5x/10 menit per IP.

### T-05: Reset password via email — Ditunda ke v2
- **Alasan:** belum ada layanan email yang ditentukan; tidak termasuk scope v1.

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
- **Bukti:** `backend/controllers/admin_menus.php`.
- **Fitur:** `POST /api/admin/menus`, `GET /api/admin/menus`, `GET /api/admin/menus/{id}`,
  `PATCH /api/admin/menus/{id}` (termasuk `is_active`), `PATCH /api/admin/menus/{id}/stock`.
- **Catatan:** "hapus" dilakukan dengan menonaktifkan menu (`is_active = 0`), bukan hard delete.

---

## Modul 3: Pre-Order & Pembayaran

### T-09: Migrasi `orders` & `order_items` — Selesai
- **Bukti:** `backend/migrations/003_create_orders.sql`.
- **Isi:** `order_code`, `pickup_at`, `expires_at`, status enum, snapshot `price_at_order`,
  foreign key + index.

### T-10: API create order + anti-oversell — Selesai
- **Bukti:** `backend/controllers/orders.php`, `backend/services/order.php`.
- **Fitur:** validasi item (maks 10 baris, qty 1–50), pickup 5 menit–7 hari ke depan dan jam
  07:00–17:00, batas 3 order pending per user, transaksi `SELECT ... FOR UPDATE`, potong stok,
  insert order + item, generate `order_code`.
- **Catatan:** slot pickup di UI adalah 12:00–16:00.

### T-11: Integrasi Midtrans Snap + webhook — Selesai
- **Bukti:** `backend/services/midtrans.php`, `backend/controllers/payments.php`,
  `backend/api/index.php`, `frontend/js/app.js`.
- **Fitur:** Snap Token saat order dibuat, `GET /api/payments/config`, webhook dengan validasi
  signature SHA-512 dan `gross_amount`, penanganan pembayaran terlambat untuk order `cancelled`.
- **Mode demo:** tanpa server key, backend memakai token dummy dan frontend melewati Snap.

### T-12: API riwayat & detail order — Selesai
- **Bukti:** `backend/controllers/orders.php`.
- **Fitur:** `GET /api/orders/me` (riwayat + item), `GET /api/orders/{id}` (hanya milik user).

---

## Modul 4: Admin Dashboard & Order Lifecycle

### T-13: API daftar order admin — Selesai
- **Bukti:** `backend/controllers/admin_orders.php`.
- **Fitur:** `GET /api/admin/orders` dengan filter `?pickup_date=YYYY-MM-DD`, data pemesan,
  item, urut `pickup_at`; frontend mengelompokkan per slot dan tanggal.

### T-14: API update status order — Selesai
- **Bukti:** `backend/controllers/admin_orders.php`.
- **Transisi sah:** `pending → paid|cancelled`, `paid → ready|cancelled`, `ready → completed`.
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
- **Fitur:** papan pesanan per slot/tanggal, tombol Siap/Selesai/Batal, kelola stok dan status
  aktif menu, refresh 30 detik.

### T-18: Security & performance — Sebagian
- **Sudah:** rate limit auth/order, JWT + role guard, prepared statement, transaksi anti-oversell,
  proteksi file sensitif via `.htaccess`, uji mandiri JWT/rate limiter.
- **Belum:** load test konkurensi, audit responsive 320/768/1024 px, pengukuran page load,
  test end-to-end, pembatasan CORS produksi, penggantian `JWT_SECRET` default.
- **Bukti:** `backend/tests/jwt_test.php`, `backend/.htaccess`.

---

## Deferred ke v2

- Reset password via email dan verifikasi email registrasi.
- Push notification (FCM/email) untuk status `paid` dan `ready`.
- Invoice PDF.
- Rating/review menu, loyalty points, multi-kantin, split payment.
- Dark mode dan audit aksesibilitas menyeluruh.
- Jadwal menu per tanggal dan pengaturan slot pickup oleh admin.

## Verifikasi Cepat

```bash
cd backend
php tests/jwt_test.php     # self-check JWT & rate limiter
php migrate.php            # idempotent: seed dilewati bila users sudah terisi
php -S 127.0.0.1:5000 -t . api/index.php
```

```bash
cd frontend
php -S 127.0.0.1:8080
```
