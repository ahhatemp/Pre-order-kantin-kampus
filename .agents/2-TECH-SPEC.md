# Tech Spec: Sistem Pre-Order Kantin Kampus (v1)

> **Status dokumen:** Diselaraskan dengan implementasi aktual di repo (PHP 8 + MySQL + HTML/CSS/JS).
> Dokumen ini menggantikan rencana lama berbasis Next.js + Express + TypeScript.

## 1. Overview

Sistem pre-order kantin kampus: mahasiswa melihat menu & stok, membuat pesanan dengan jam
pengambilan, membayar lewat Midtrans Snap, lalu memantau status. Admin mengelola menu/stok dan
memproses pesanan per slot pickup.

**Scope v1:** autentikasi mahasiswa/admin, menu & stok, pembuatan order dengan anti-oversell,
pembayaran Midtrans + webhook, auto-cancel order kedaluwarsa, papan pesanan admin, dan tracking
status lewat polling.

**Non-goals v1:** push notification, verifikasi/reset password via email, invoice PDF, multi-kantin,
jadwal menu per tanggal, rating/review, dan split payment.

## 2. Tech Stack (final)

| Layer | Teknologi | Catatan |
|---|---|---|
| Backend | PHP 8.0+ native, tanpa framework | Front controller + tabel route manual |
| Database | MySQL 8.0 / MariaDB 10.3+, InnoDB, `utf8mb4` | Driver PDO, prepared statement |
| Auth | JWT HS256 implementasi sendiri + `password_hash()` bcrypt | Tanpa Composer/dependensi eksternal |
| Rate limit | File JSON + `flock()` di `backend/storage/ratelimit/` | Cocok untuk satu server |
| Payment | Midtrans Snap + webhook | Opsional saat demo (mode dummy) |
| Frontend | HTML + CSS + JavaScript polos | Satu HTML per layar, tanpa build step |
| Web server | PHP built-in server atau Apache/Laragon | Rewrite lewat `backend/.htaccess` |
| Testing | `backend/tests/jwt_test.php` | Self-check JWT & rate limiter |

**Alasan pemilihan:**

- Tidak ada build step maupun package manager; repo bisa langsung dijalankan dengan PHP + MySQL.
- Query ditulis eksplisit dengan PDO agar mudah dijelaskan saat demo.
- JWT dan rate limiter dibuat manual supaya tidak perlu Composer.
- Frontend memakai satu "engine" JS bersama (`app.js`) di atas banyak file HTML.

## 3. Struktur Repository

```
backend/
├── api/index.php            front controller + tabel route
├── config.php               loader .env + konfigurasi
├── db.php                   koneksi PDO (singleton per request)
├── helpers.php              JSON response, parsing body, CORS
├── auth.php                 JWT HS256 + authenticate/require_role
├── rate_limit.php           sliding window berbasis file
├── controllers/
│   ├── auth.php  menus.php  orders.php  payments.php
│   ├── admin_menus.php  admin_orders.php
│   └── uploads.php          upload foto menu + serve file
├── services/
│   ├── order.php            transaksi pembuatan order
│   ├── order_expiry.php     sweep order kedaluwarsa
│   └── midtrans.php         generate Snap Token
├── migrations/              001–004 (users, menus, orders, seed)
├── schema.sql               skema + seed untuk import phpMyAdmin
├── migrate.php              CLI: buat DB + jalankan migrations
├── storage/                 rate limit + lock sweep (runtime)
│   └── uploads/             foto menu hasil upload (runtime, di-.gitignore)
├── tests/jwt_test.php
├── .env.example
└── .htaccess

frontend/
├── index.html               redirect sesuai sesi
├── masuk.html               login + daftar
├── menu.html  keranjang.html  pembayaran.html  sukses.html
├── pesanan.html  lacak.html
├── akun-mahasiswa.html  akun-admin.html
├── admin-pesanan.html  admin-stok.html
├── notifikasi.html  bantuan.html  tentang.html
├── profil-kantin.html  jam-operasional.html
├── css/styles.css           design tokens + komponen
├── js/api.js                lapisan HTTP, auth, mapper
├── js/app.js                state, render semua layar, aksi
└── assets/                  gambar menu bawaan

.agents/                     PRD, tech spec, tasks, design
Desain-Berdasarkan-PRD-dan-DESIGN/   desain mentah (referensi)
```

## 4. Database

Database default: `kantin`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`, engine InnoDB.
Semua harga disimpan sebagai `INT` Rupiah penuh. Waktu memakai `DATETIME`; PHP diset ke
`Asia/Jakarta` agar konsisten dengan `NOW()` MySQL.

### `users`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `nim` | VARCHAR(20) | UNIQUE, NULL (admin boleh tanpa NIM) |
| `nama` | VARCHAR(100) | NOT NULL |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL |
| `password` | VARCHAR(255) | NOT NULL (bcrypt) |
| `role` | ENUM('mahasiswa','admin') | NOT NULL DEFAULT 'mahasiswa' |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

### `menus`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `nama` | VARCHAR(100) | NOT NULL |
| `deskripsi` | TEXT | NULL |
| `kategori` | VARCHAR(50) | NULL |
| `harga` | INT | NOT NULL |
| `stok` | INT | NOT NULL DEFAULT 0 |
| `image_url` | VARCHAR(255) | NULL |
| `is_active` | TINYINT(1) | NOT NULL DEFAULT 1 |
| `deleted_at` | DATETIME | NULL (soft delete; riwayat order tetap valid) |
| `updated_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### `orders`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `order_code` | VARCHAR(20) | UNIQUE, NOT NULL (`ORD-` + 8 hex) |
| `user_id` | INT | FK → `users.id` ON DELETE CASCADE |
| `total_price` | INT | NOT NULL |
| `pickup_at` | DATETIME | NOT NULL |
| `catatan` | VARCHAR(255) | NULL |
| `status` | ENUM('pending','paid','ready','completed','cancelled') | DEFAULT 'pending' |
| `payment_token` | VARCHAR(255) | NULL |
| `expires_at` | DATETIME | NOT NULL |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

Index: `idx_user_id`, `idx_status`, `idx_pickup_at`.

### `order_items`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `order_id` | INT | FK → `orders.id` ON DELETE CASCADE |
| `menu_id` | INT | FK → `menus.id` |
| `quantity` | INT | NOT NULL |
| `price_at_order` | INT | NOT NULL (snapshot harga) |

Index: `idx_order_id`.

> Rekomendasi server: aktifkan `STRICT_TRANS_TABLES` agar data yang melebihi panjang kolom
> menghasilkan error, bukan terpotong diam-diam.

## 5. API

- Base path: `/api`
- Format: JSON (`Content-Type: application/json; charset=utf-8`)
- Auth: header `Authorization: Bearer <token>`
- Error: `{"error": "pesan"}` dengan status HTTP yang sesuai
- Sukses: objek/array langsung (tanpa envelope `data`)

### Daftar endpoint

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/health` | — | Health check |
| POST | `/api/auth/register` | — | Registrasi mahasiswa |
| POST | `/api/auth/login` | — | Login, mengembalikan JWT |
| GET | `/api/auth/me` | token | Profil user aktif |
| GET | `/api/menus` | — | Menu aktif + stok |
| GET | `/api/orders/me` | token | Riwayat pesanan user + item |
| POST | `/api/orders` | token | Buat pesanan (stok dipotong atomik) |
| GET | `/api/orders/{id}` | token | Detail pesanan milik user |
| POST | `/api/payments/webhook` | — (signature) | Notifikasi Midtrans |
| GET | `/api/payments/config` | — | Client key + status konfigurasi Midtrans |
| POST | `/api/admin/menus` | admin | Tambah menu |
| GET | `/api/admin/menus` | admin | Semua menu (termasuk nonaktif) |
| GET | `/api/admin/menus/{id}` | admin | Detail menu |
| PATCH | `/api/admin/menus/{id}` | admin | Ubah field menu |
| PATCH | `/api/admin/menus/{id}/stock` | admin | Ubah stok |
| DELETE | `/api/admin/menus/{id}` | admin | Hapus menu (soft delete, `deleted_at`) |
| POST | `/api/admin/menus/upload` | admin | Upload foto menu (multipart `image`, JPG/PNG/WebP ≤ 5 MB) |
| GET | `/api/uploads/{file}` | — | Serve foto menu hasil upload |
| GET | `/api/admin/orders` | admin | Daftar pesanan (`?pickup_date=YYYY-MM-DD`) |
| PATCH | `/api/admin/orders/{id}/status` | admin | Ubah status pesanan |

### Contoh

**Register**

```json
POST /api/auth/register
{ "nim": "22222222", "nama": "Budi", "email": "budi@kampus.ac.id", "password": "rahasia123" }
```

**Login**

```json
POST /api/auth/login
{ "email": "budi@kampus.ac.id", "password": "rahasia123" }
```

Respons: `{ "token": "...", "user": { "id": 2, "nim": "22222222", "nama": "Budi", "email": "...", "role": "mahasiswa" } }`

**Buat order**

```json
POST /api/orders
{
  "items": [{ "menu_id": 1, "quantity": 2 }, { "menu_id": 3, "quantity": 1 }],
  "pickup_at": "2026-10-24T12:00:00",
  "catatan": "Tanpa sambal"
}
```

Respons `201`:

```json
{
  "id": 12,
  "order_code": "ORD-1A2B3C4D",
  "total_price": 55000,
  "payment_token": "snap-token-...",
  "expires_at": "2026-10-24 12:10:00"
}
```

## 6. Autentikasi, Otorisasi & Rate Limit

- JWT payload: `id`, `email`, `role`, `iat`, `exp` (12 jam). Algoritma HS256, secret dari `JWT_SECRET`.
- `authenticate()` membaca header `Authorization`, termasuk fallback `REDIRECT_HTTP_AUTHORIZATION`
  dan `apache_request_headers()` untuk Apache/CGI.
- `require_role($user, 'admin')` melindungi seluruh endpoint `/api/admin/*`.
- Password: `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`; verifikasi `password_verify()`.
- CORS terbuka (`Access-Control-Allow-Origin: *`) untuk memudahkan development lintas port.
  Untuk produksi, batasi ke origin frontend.
- `.htaccess` menolak akses langsung ke `.env`, `.sql`, `.md`, `.log`, `.ini`, dan semua file PHP
  selain front controller.

| Limiter | Batas | Window | Cakupan |
|---|---|---|---|
| Order | 20 | 1 menit | Per IP |
| Pending order | 3 | — | Per user, order `pending` yang belum kedaluwarsa |

Counter disimpan sebagai file JSON dengan `flock()`. Jika direktori storage tidak bisa ditulis,
limiter membiarkan request lewat (fail-open). Login tidak dibatasi percobaannya.

## 7. Order Lifecycle & Anti-Oversell

### Validasi `POST /api/orders`

1. `items` array, 1–10 baris; setiap `menu_id` dan `quantity` numerik; `quantity` 1–50.
2. `pickup_at` valid, minimal 5 menit ke depan, maksimal 7 hari ke depan.
3. Jam pickup 07:00–17:00 (jam operasional backend).
4. `catatan` opsional, maksimal 255 karakter.
5. User tidak boleh punya ≥ `ORDER_PENDING_MAX` (default 3) order `pending` yang belum expired.

### Transaksi pembuatan order (`services/order.php`)

```
BEGIN;
  SELECT id, nama, harga, stok, is_active FROM menus
    WHERE id IN (?, ...) AND deleted_at IS NULL FOR UPDATE;
  -- gabungkan quantity untuk menu_id yang sama
  -- tolak jika menu tidak ada / is_active = 0 / stok < quantity
  UPDATE menus SET stok = stok - ? WHERE id = ?;
  INSERT INTO orders (..., status='pending', expires_at=NOW()+10 MINUTE);
  INSERT INTO order_items (..., price_at_order);
  -- panggil Midtrans Snap, simpan payment_token
COMMIT;
```

Jika terjadi error (termasuk gagal memanggil Midtrans), transaksi di-rollback dan stok batal
terpotong. Respons gagal stok: HTTP 400 dengan pesan spesifik dari service.

### Transisi status

| Dari | Ke | Pemicu |
|---|---|---|
| `pending` | `paid` | Otomatis: webhook Midtrans (`capture`/`settlement` valid) atau checkout demo saat Midtrans belum dikonfigurasi |
| `pending` | `cancelled` | Admin, sweep kedaluwarsa, webhook `cancel`/`expire`/`deny` |
| `paid` | `ready` | Admin |
| `paid` | `cancelled` | Admin (stok dikembalikan) |
| `ready` | `completed` | Admin |

Transisi lain ditolak. Stok dikembalikan hanya saat order berubah menjadi `cancelled`.

## 8. Pembayaran (Midtrans Snap)

1. Setelah transaksi order, backend memanggil Snap API (`sandbox` atau `production`) dengan
   `order_id = order_code` dan `gross_amount = total_price`.
2. `GET /api/payments/config` mengembalikan `client_key`, `is_production`, dan `enabled`.
   Server key tidak pernah dikirim ke client.
3. Frontend memuat `snap.js` dan memanggil `window.snap.pay(payment_token)`.
4. Jika Midtrans belum dikonfigurasi (server key kosong/placeholder), backend membuat token
   dummy `dummy-snap-token-*` dan langsung menandai order `paid` (mode demo); frontend melewati
   Snap dan menampilkan "Pembayaran berhasil". Admin tidak lagi mengonfirmasi pembayaran manual.

### Webhook `POST /api/payments/webhook`

- Signature wajib: `sha512(order_id + status_code + gross_amount + server_key)`, dibandingkan
  dengan `hash_equals()`. Tanpa server key asli, webhook menolak dengan 503.
- `gross_amount` harus sama dengan `orders.total_price`.
- `capture` hanya diproses bila `fraud_status = accept`.
- `settlement`/`capture`: `pending` → `paid`; bila order sudah `cancelled`, stok dicek ulang dan
  dipotong lagi. Jika stok tidak cukup, respons 409 agar Midtrans retry dan kasus tercatat untuk
  refund manual.
- `cancel`/`expire`/`deny`: order `pending` → `cancelled` + stok dikembalikan.
- Transisi status memakai `SELECT ... FOR UPDATE` sehingga webhook berulang bersifat idempoten.

## 9. Auto-Cancel Order Kedaluwarsa

PHP tidak memiliki proses latar seperti `setInterval`, jadi sweep dijalankan di sela request API:

- `maybe_sweep_expired_orders()` dipanggil setiap request yang cocok dengan route.
- Lock file `storage/.sweep.lock` membatasi eksekusi maksimal sekali per 60 detik.
- `sweep_expired_orders()` membungkus proses dalam transaksi: ambil order `pending` dengan
  `expires_at < NOW()` menggunakan `FOR UPDATE`, kembalikan stok dari `order_items`, lalu set
  status `cancelled`.
- Konsekuensi: bila tidak ada traffic sama sekali, order kedaluwarsa baru dibatalkan saat request
  berikutnya datang.

## 10. Frontend

### Peta halaman

| File | `data-page` | Peran | Fungsi |
|---|---|---|---|
| `index.html` | — | — | Redirect ke `masuk.html` atau `menu.html` |
| `masuk.html` | `auth` | publik | Login + daftar |
| `menu.html` | `menu` | mahasiswa | Daftar menu, pencarian, keranjang |
| `keranjang.html` | `cart` | mahasiswa | Ubah qty, pilih slot pickup, catatan |
| `pembayaran.html` | `payment` | mahasiswa | Ringkasan sebelum bayar |
| `sukses.html` | `success` | mahasiswa | Konfirmasi order + tenggat bayar |
| `pesanan.html` | `orders` | mahasiswa | Riwayat pesanan |
| `lacak.html` | `tracking` | mahasiswa | Timeline status pesanan |
| `akun-mahasiswa.html` | `accountStudent` | mahasiswa | Profil & menu akun |
| `admin-pesanan.html` | `board` | admin | Papan pesanan per slot/tanggal |
| `admin-stok.html` | `stock` | admin | Kelola stok, status, tambah & edit menu |
| `akun-admin.html` | `accountAdmin` | admin | Profil admin |
| `notifikasi.html`, `bantuan.html`, `tentang.html` | sub-halaman | keduanya | Halaman informasi |
| `profil-kantin.html`, `jam-operasional.html` | sub-halaman | admin | Info kantin |

Setiap HTML memuat `js/api.js` lalu `js/app.js` (urutan wajib), dan menandai halamannya lewat
`<body data-page="...">`.

### `js/api.js`

- `API_BASE` ditentukan otomatis:
  - Jika halaman disajikan di bawah `/frontend/`, API = `<origin>/backend/api` (Laragon/Apache).
  - Selain itu default `http://localhost:5000/api` (mode `php -S`).
  - Bisa dioverride: `localStorage.setItem('kantin:api', '<url>')`.
- Token disimpan di `localStorage` (`kantin:token`); profil di `sessionStorage` (`kantin:user`).
- Respons 401 otomatis menghapus sesi dan redirect ke `masuk.html`.
- `mapMenu()` dan `mapOrder()` menormalkan tipe data PDO (string → number) untuk UI.

### `js/app.js`

- State disimpan di `sessionStorage` (`kantin-kampus:v2`) agar alur antar halaman tetap hidup.
- Semua layar dirender dari satu engine (`renderView`), dengan ikon SVG inline.
- Slot pickup UI: 12:00, 13:00, 14:00, 15:00, 16:00; jika semua sudah lewat, slot ditawarkan
  untuk besok.
- Polling data: halaman `board`, `orders`, `tracking` setiap 30 detik; `menu`, `stock` setiap
  60 detik; refresh juga terjadi saat tab kembali aktif.
- Admin menambah/mengedit menu lewat bottom sheet di halaman `stock` (`POST`/`PATCH`
  `/api/admin/menus`); render ulang modal dilewati saat polling agar isian tidak hilang.
- Foto menu hanya lewat unggah file atau kamera, bukan isian nama file. Tombol Kamera membuka
  pratinjau `getUserMedia` di bottom sheet, lalu hasil frame diambil lewat canvas sebagai JPEG.
  Jika kamera tidak tersedia/izin ditolak, perangkat mobile memakai input `capture="environment"`;
  desktop menampilkan pesan untuk memakai tombol Upload. `getUserMedia` butuh HTTPS atau localhost.
- File dikirim ke `POST /api/admin/menus/upload` sebagai multipart, lalu `image_url` hasilnya
  (`uploads/<file>`) disimpan lewat create/update menu. `menuImage()` mengarahkan URL tersebut
  ke `<API_BASE>/uploads/<file>`; gambar bawaan tetap dilayani dari `assets/`.
- Pembayaran: `POST /api/orders` → cek `/api/payments/config` → muat Snap → `snap.pay()`.
  Tanpa konfigurasi Midtrans, order dianggap menunggu konfirmasi manual.

## 11. Setup & Menjalankan

### Prasyarat

- PHP 8.0+ dengan ekstensi `pdo_mysql` dan `curl`
- MySQL 8.0 / MariaDB 10.3+ (Laragon/XAMPP)
- Git Bash/PowerShell (Windows) atau shell Unix

### Langkah

```bash
# 1. Konfigurasi
cd backend
cp .env.example .env          # Windows: copy .env.example .env
# isi DB_USER, DB_PASSWORD, JWT_SECRET, dan kredensial Midtrans (opsional)

# 2. Database + seed
php migrate.php
# alternatif: import backend/schema.sql lewat phpMyAdmin

# 3. API
php -S 127.0.0.1:5000 -t . api/index.php

# 4. Frontend (terminal lain)
cd ../frontend
php -S 127.0.0.1:8080
```

Buka `http://127.0.0.1:8080/`.

Lewat Laragon/Apache: letakkan repo di `C:\laragon\www\`, Start All, lalu akses
`http://kantin.test/frontend/` dan API di `http://kantin.test/backend/api/health`.

### Akun awal (password `password123`)

- `admin@kantin.com` — admin
- `user@kantin.com` — mahasiswa

### Test

```bash
cd backend
php tests/jwt_test.php
```

## 12. Batasan & Risiko yang Diketahui

- Panggilan HTTP ke Midtrans dilakukan **di dalam transaksi database**, sehingga row lock menu
  tertahan sampai request selesai (timeout cURL 10 detik). Untuk trafik tinggi, pertimbangkan
  memindahkan pembuatan token setelah commit.
- Sweep kedaluwarsa bergantung pada traffic; tidak ada cron/daemon.
- Rate limit berbasis file lokal, belum cocok untuk beberapa instance server.
- CORS `*` dan fallback `JWT_SECRET` default harus diganti sebelum produksi.
- Belum ada test end-to-end; hanya ada self-check JWT + rate limiter.
- Dark mode pada `DESIGN.md` belum diimplementasikan di `frontend/css/styles.css`.
- Polling halaman tracking tetap berjalan setelah status final; tidak berhenti otomatis.
- Menu belum memiliki jadwal per tanggal; semua menu aktif tampil setiap hari.

## 13. Deferred ke v2

- Push notification (FCM/email) untuk status `paid` dan `ready`.
- Reset password via email dan verifikasi email saat registrasi.
- Invoice PDF.
- Rating/review, loyalty points, multi-kantin, split payment.
- Dark mode dan audit aksesibilitas menyeluruh.
