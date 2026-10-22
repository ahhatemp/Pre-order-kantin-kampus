# Tech Spec: Sistem Pre-Order Kantin Kampus

## 1. Overview
Sistem pre-order kantin kampus untuk mengurangi antrian fisik dan memberikan kepastian ketersediaan menu serta jadwal pengambilan (pickup time) bagi mahasiswa.

## 2. Tech Stack (Final — sudah dikunci)

- **Frontend**: Next.js (App Router) + Tailwind CSS + shadcn/ui (Mobile-first responsive).
- **Backend**: Node.js + **Express** + TypeScript. Struktur `routes/ controllers/ middleware/ services/`.
- **Database**: **MySQL 8.0** (relational DB untuk users, menus, orders, order_items).
- **DB Driver**: **mysql2** dengan `createPool`. **Tidak ada ORM** — query ditulis raw SQL.
- **Validation**: Manual di controller (bukan Zod). Sederhana, cukup untuk validasi field.
- **Authentication**: JWT dengan **bcryptjs** (cost 12), role-based access (`mahasiswa` vs `admin`).
- **Middleware**: `cors`, `dotenv`, `express-rate-limit`.
- **Migration**: File `.sql` raw di `backend/migrations/`, dijalankan manual via MySQL CLI.
- **Payment Gateway**: Midtrans (e-wallet, QRIS, transfer bank).
- **Notifikasi**: **TIDAK diimplementasikan di v1** — user melakukan polling status di halaman tracking.

### 2.1 Kenapa keputusan ini

- **Express** dipilih karena dokumentasi & tutorial paling banyak, dan sudah konsisten dipakai di `3-TASKS.md`.
- **MySQL** dipilih karena lebih umum dipakai di kampus dan tool CLI-nya sudah tersedia.
- **Raw SQL tanpa ORM** supaya query transparan saat demo ke dosen — tidak ada abstraksi yang perlu dijelaskan.
- **bcryptjs (bukan bcrypt native)** karena pure JavaScript, tidak perlu compile native module di Windows.
- **Hanya 4 dependency runtime utama** (`express`, `mysql2`, `jsonwebtoken`, `bcryptjs`) + 3 middleware kecil. Instalasi cepat, dependensi tidak meledak.

## 3. Database Schema (MySQL 8.0)

### Catatan Penting MySQL vs PostgreSQL

1. **Tidak ada tipe UUID di MySQL** — semua PK memakai `INT AUTO_INCREMENT`.
2. **Harga pakai `INT`, bukan `DECIMAL`** — Rupiah tidak punya pecahan sen dalam konteks kantin, dan `mysql2` mengembalikan `DECIMAL` sebagai string yang merepotkan. Nilai disimpan dalam Rupiah penuh (contoh: `35000`).
3. **`pickup_at` pakai `DATETIME`, bukan VARCHAR** — supaya filter "order hari ini" dan sorting per slot berjalan otomatis.
4. **`expires_at` ditambahkan di `orders`** untuk mendukung auto-cancel order yang tidak dibayar dalam 10 menit (FR-11).
5. **`order_code` ditambahkan** untuk ID yang human-readable di UI (`#ORD-241011-001`), karena `id` integer tidak enak ditampilkan ke pengguna.
6. **Charset `utf8mb4`** untuk semua tabel — mendukung teks Indonesia dan emoji.
7. **`sql_mode` harus menyertakan `STRICT_TRANS_TABLES`** agar data yang kepotong akan error, bukan disimpan diam-diam terpotong.

### Tabel `users`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `nim` | VARCHAR(20) | UNIQUE, NULLABLE (admin tidak punya NIM) |
| `nama` | VARCHAR(100) | NOT NULL |
| `email` | VARCHAR(255) | UNIQUE, NOT NULL |
| `password` | VARCHAR(255) | NOT NULL (bcrypt hash, 60 char) |
| `role` | ENUM('mahasiswa','admin') | NOT NULL DEFAULT 'mahasiswa' |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

### Tabel `menus`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `nama` | VARCHAR(100) | NOT NULL |
| `deskripsi` | TEXT | NULLABLE |
| `kategori` | VARCHAR(50) | NULLABLE (per FR-05) |
| `harga` | INT | NOT NULL (dalam Rupiah penuh) |
| `stok` | INT | NOT NULL DEFAULT 0 |
| `image_url` | VARCHAR(255) | NULLABLE |
| `is_active` | TINYINT(1) | NOT NULL DEFAULT 1 |
| `updated_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### Tabel `orders`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `order_code` | VARCHAR(20) | UNIQUE, NOT NULL (contoh: `ORD-241011-001`) |
| `user_id` | INT | FK -> users.id, NOT NULL |
| `total_price` | INT | NOT NULL |
| `pickup_at` | DATETIME | NOT NULL (misal: `2024-10-11 12:00:00`) |
| `catatan` | VARCHAR(255) | NULLABLE (catatan khusus user, FR-08) |
| `status` | ENUM('pending','paid','ready','completed','cancelled') | NOT NULL DEFAULT 'pending' |
| `payment_token` | VARCHAR(255) | NULLABLE |
| `expires_at` | DATETIME | NOT NULL (auto-cancel threshold, FR-11) |
| `created_at` | DATETIME | DEFAULT CURRENT_TIMESTAMP |

### Tabel `order_items`

| Kolom | Tipe | Constraint |
|---|---|---|
| `id` | INT | PK, AUTO_INCREMENT |
| `order_id` | INT | FK -> orders.id (ON DELETE CASCADE), NOT NULL |
| `menu_id` | INT | FK -> menus.id, NOT NULL |
| `quantity` | INT | NOT NULL |
| `price_at_order` | INT | NOT NULL (harga saat order, supaya historis tidak berubah) |

### 3.1 Transaksi Anti Oversell (WAJIB di `POST /api/orders`)

NFR mensyaratkan 500 concurrent users pada jam makan siang. DuaMahasiswa yang order item terakhir di detik yang sama akan saling menimpa stok dan menghasilkan oversell kalau query dijalankan tanpa locking.

`POST /api/orders` **wajib** dibungkus transaksi MySQL berikut:

```
BEGIN;
  SELECT id, nama, harga, stok FROM menus
    WHERE id IN (?, ?, ?) FOR UPDATE;     -- lock baris, tunggu transaksi lain
  -- validasi stok setiap item, kalau kurang: ROLLBACK + return 409
  UPDATE menus SET stok = stok - ? WHERE id = ?;
  INSERT INTO orders (...) VALUES (...);
  INSERT INTO order_items (...) VALUES (...), (...);
  -- generate Snap Token Midtrans
COMMIT;
```

Kalau generate Snap Token gagal, `ROLLBACK` dan kembalikan stok. Pattern ini yang membuat claim "support 500 concurrent users" di NFR benar, dan bisa ditunjukkan saat demo.

### 3.2 Struktur Folder Backend

```
backend/
  src/
    db.ts              createPool mysql2, export query helpers
    server.ts          entrypoint Express app
    routes/
      auth.ts  menus.ts  orders.ts  payments.ts
      admin/
        menus.ts  orders.ts
    controllers/       handler per route
    middleware/
      auth.ts          validasi JWT + cek role
      rateLimit.ts     rate limiting auth & order
    services/
      order.ts         business logic pembuatan order (transaksi)
      midtrans.ts      generate Snap Token
  migrations/
    001_create_users.sql
    002_create_menus.sql
    003_create_orders.sql
  .env.example
```

### 3.3 Cara Run Migration

```bash
mysql -u root -p -e "CREATE DATABASE kantin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p kantin < backend/migrations/001_create_users.sql
mysql -u root -p kantin < backend/migrations/002_create_menus.sql
mysql -u root -p kantin < backend/migrations/003_create_orders.sql
```

## 4. API Endpoints

### Autentikasi
- `POST /api/auth/register` - Daftar mahasiswa baru (Validasi NIM, email unik, password min 8 char).
- `POST /api/auth/login` - Login user/admin (Rate limit: 5x gagal = blokir 10 menit).
- `POST /api/auth/reset-password` - Request reset password via email.

### Menu & Stok
- `GET /api/menus` - Ambil daftar menu aktif hari ini.
- `PATCH /api/admin/menus/:id/stock` - Admin update stok menu.

### Pre-Order & Checkout
- `POST /api/orders` - Buat pesanan baru dengan pilihan `pickup_at`, hitung total, kurangi stok (di dalam transaksi dengan `FOR UPDATE`), return Snap Token. Lihat section 3.1.
- `GET /api/orders/me` - Riwayat dan status tracking pesanan mahasiswa.
- `GET /api/orders/:id` - Detail satu order untuk halaman tracking (dipakai polling status, lihat section 6).

### Admin Kantin
- `GET /api/admin/orders` - List semua order hari ini (bisa difilter berdasarkan slot pickup time).
- `PATCH /api/admin/orders/:id/status` - Update status order (misal dari `paid` -> `ready` -> `completed`).

### Webhook
- `POST /api/payments/webhook` - Midtrans notification endpoint untuk update status order otomatis (`pending` -> `paid`).

### Job Penjaga
- `POST /api/orders/sweep-expired` - Dipanggil dari `setInterval` di `server.ts`: batalkan order `pending` yang `expires_at` sudah lewat, kembalikan stok ke `menus`.

## 5. Non-Functional & Keamanan
- HTTPS mandatory.
- JWT expiry: 12 jam.
- Rate limiting pada endpoint auth dan order (`express-rate-limit`, 5x gagal login = blokir 10 menit).
- Connection pooling pada MySQL via `mysql2` `createPool` (limit default 10 koneksi).
- Query selalu pakai **prepared statement** (`pool.execute` dengan placeholder `?`) untuk mencegah SQL injection.
- Transaksi + `SELECT ... FOR UPDATE` pada pembuatan order untuk mencegah oversell.
- Secret diambil dari `.env`, tidak di-hardcode.

## 6. Workflow Payment
1. User pilih menu & pickup time -> `POST /api/orders` -> transaksi deduct stok, insert order dengan status `pending`, set `expires_at` = now + 10 menit.
2. Response berisi Midtrans Snap Token.
3. User bayar via Midtrans (diarahkan ke halaman Midtrans).
4. Midtrans hit webhook -> `POST /api/payments/webhook` -> status update `paid`.
5. Admin mark order `ready`.
6. User melihat status `Siap Diambil` — **v1 tidak ada push notification, user melakukan polling `GET /api/orders/:id` setiap 5 detik di halaman tracking**.
7. User ambil makanan -> admin mark `completed`.

> Catatan v1: karena tanpa notifikasi, status `ready` hanya diketahui pengguna bila ia sedang membuka halaman tracking. Ini tradeoff yang disengaja untuk menjaga scope. Push notification (FCM) dipindahkan ke v2.

## 7. Implementasi Phases

### Phase 1: Setup & Auth
- Setup Next.js project + Tailwind + shadcn/ui.
- Setup Express API + MySQL connection pool (`mysql2`).
- Create database `kantin` (charset utf8mb4).
- Implement JWT auth (register, login, middleware) dengan bcryptjs.
- Migration script untuk tabel `users`.

### Phase 2: Menu & Stok
- Tabel `menus` migration.
- Admin CRUD menu.
- Endpoint `GET /api/menus` untuk mahasiswa.
- Real-time stok visibility.

### Phase 3: Pre-Order & Payment
- Tabel `orders` & `order_items` migration.
- Endpoint `POST /api/orders`.
- Midtrans integration (Snap Token generation).
- Webhook handler.

### Phase 4: Admin Dashboard & Auto-Cancel
- Admin view orders + filter by pickup time slot.
- Admin mark order ready/completed.
- Auto-cancel order `pending` yang lewat `expires_at` + kembalikan stok.
- ~~Push notification (FCM/email)~~ — **dipindahkan ke v2**, lihat section 6.

### Phase 5: Testing & Polish
- Rate limiting implementation.
- Error handling & validation.
- Mobile responsive testing.
- Verifikasi anti-oversell pada `POST /api/orders` (section 3.1).
