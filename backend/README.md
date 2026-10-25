# Backend - Pre-Order Kantin Kampus (PHP + MySQL)

API REST tanpa framework. Native PHP + PDO + MySQL.

## Kebutuhan

- PHP 8.0+ (ekstensi `pdo_mysql`, `curl`)
- MySQL 5.7+ / MariaDB 10.3+

## Setup

```bash
# 1. konfigurasi
copy .env.example .env        # Windows
cp .env.example .env          # Linux/Mac

# 2. isi .env (DB_USER, DB_PASSWORD, JWT_SECRET)

# 3. buat database + tabel + data awal
php migrate.php
# atau import schema.sql lewat phpMyAdmin

# 4. jalankan server
php -S 127.0.0.1:5000 -t . api/index.php
```

Dengan XAMPP/Laragon: taruh folder ini di `htdocs/kantin-backend/`, lalu
akses `http://localhost/kantin-backend/api/health` (`.htaccess` sudahhandle rewrite).

Akun awal (password: `password123`):
- `admin@kantin.com` (admin)
- `user@kantin.com` (mahasiswa)

## Struktur

```
backend/
├── api/index.php          front controller + tabel route
├── config.php             baca .env, nilai konfigurasi
├── db.php                 koneksi PDO
├── helpers.php            JSON response, body parsing, CORS
├── auth.php               JWT HS256 + authenticate/require_role
├── rate_limit.php         rate limiter berbasis file
├── controllers/           auth, menus, orders, payments, admin_*, uploads
├── services/              order (transaksi), order_expiry (sweep), midtrans
├── migrations/*.sql       skema per tabel (dipakai migrate.php)
├── schema.sql             skema + data awal (import sekali)
├── migrate.php            CLI: apply migrations + seed
├── storage/uploads/       foto menu hasil upload (runtime)
└── tests/jwt_test.php     self-check JWT & rate limiter
```

## Endpoint

| Method | Path | Auth | Keterangan |
|---|---|---|---|
| GET | `/api/health` | - | health check |
| POST | `/api/auth/register` | - | daftar mahasiswa |
| POST | `/api/auth/login` | - | login, balikin JWT (12 jam) |
| GET | `/api/auth/me` | token | profil user aktif |
| GET | `/api/menus` | - | daftar menu aktif |
| POST | `/api/orders` | token | buat pesanan (stok dipotong atomik) |
| GET | `/api/orders/me` | token | riwayat pesanan |
| GET | `/api/orders/{id}` | token | detail pesanan milik sendiri |
| POST | `/api/payments/webhook` | - | notifikasi Midtrans |
| POST | `/api/admin/menus` | admin | tambah menu |
| GET | `/api/admin/menus/{id}` | admin | detail menu |
| PATCH | `/api/admin/menus/{id}` | admin | ubah menu |
| PATCH | `/api/admin/menus/{id}/stock` | admin | ubah stok |
| POST | `/api/admin/menus/upload` | admin | upload foto menu (multipart `image`, maks 5 MB) |
| GET | `/api/uploads/{file}` | - | serve foto menu hasil upload |
| GET | `/api/admin/orders` | admin | daftar pesanan (`?pickup_date=`) |
| PATCH | `/api/admin/orders/{id}/status` | admin | ubah status |

Header auth: `Authorization: Bearer <token>`

## Frontend

`frontend/` (HTML/CSS/JS polos, satu file per layar) terhubung ke API ini lewat
`API_BASE` di `frontend/js/api.js` (default `http://localhost:5000/api`).

Cara jalan:

```bash
# terminal 1: API
cd backend
php migrate.php
php -S 127.0.0.1:5000 -t . api/index.php

# terminal 2: frontend (atau buka lewat XAMPP htdocs)
cd frontend
php -S 127.0.0.1:8080
```

Lalu buka `http://127.0.0.1:8080/` atau langsung `menu.html`. CORS sudah terbuka (`*`),
jadi boleh beda port. Ganti endpoint lain dengan
`localStorage.setItem('kantin:api', '<url>')` bila perlu.

Lewat Laragon/Apache: letakkan repo di `C:\laragon\www\` (atau junction), Start All,
lalu `http://kantin.test/frontend/` dan API di `http://kantin.test/backend/api/health`.
`frontend/js/api.js` otomatis memakai `<origin>/backend/api` bila halaman disajikan
dari path `/frontend/`.

Alur: login/daftar simpan JWT di `localStorage`, tiap halaman memuat data sendiri
(`/menus`, `/orders/me`, `/admin/orders`), status pesanan hanya diubah admin lewat
`PATCH /api/admin/orders/{id}/status`, stok lewat `PATCH /api/admin/menus/{id}/stock`.
Urutan script di tiap HTML wajib: `js/api.js` lalu `js/app.js`.

## Catatan

- Password di-hash dengan `password_hash()` (bcrypt), dicek dengan `password_verify()`.
- Semua query memakai prepared statement PDO.
- Rate limit: 20 order / menit. Disimpan di `storage/ratelimit/`.
- Pesanan `pending` yang lewat `expires_at` dibatalkan otomatis dan stok dikembalikan
  (sweep dijalankan di sela request, maksimal sekali per menit).
- `JWT_SECRET` wajib diganti sebelum dipakai di server publik.

## Test

```bash
php tests/jwt_test.php
```

## Reset password (development)

```bash
php reset_password.php user@kantin.com password123
```
