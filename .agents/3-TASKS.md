# Task List: Sistem Pre-Order Kantin Kampus

## Modul 1: Setup & Autentikasi

### T-01: Setup Next.js + Tailwind + shadcn/ui (Frontend)
- **Deskripsi:** Inisialisasi project Next.js dengan App Router, install Tailwind CSS, setup shadcn/ui untuk komponennya.
- **Modul:** Setup
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** -
- **Estimasi:** 1 jam
- **File yang diubah:** `frontend/package.json`, `frontend/tailwind.config.ts`, `frontend/components.json`

### T-02: Setup Express + MySQL Connection Pool (Backend)
- **Deskripsi:** Inisialisasi project backend Node.js + TypeScript dengan Express. Install `express`, `mysql2`, `jsonwebtoken`, `bcryptjs`, `cors`, `dotenv`, `express-rate-limit`, plus dev deps `typescript`, `tsx`, `@types/*`. Setup MySQL connection pool di `db.ts` dengan konfigurasi `charset: 'utf8mb4'`. Create database `kantin`. Environment config via `.env` + `.env.example`.
- **Modul:** Setup
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** -
- **Estimasi:** 1 jam
- **File yang diubah:** `backend/package.json`, `backend/tsconfig.json`, `backend/src/db.ts`, `backend/src/server.ts`, `backend/.env.example`

### T-03: DB Migration - Tabel `users`
- **Deskripsi:** Buat file SQL migration `001_create_users.sql` untuk tabel `users`: id (INT AUTO_INCREMENT PK), nim (VARCHAR(20) UNIQUE NULLABLE), nama, email (UNIQUE), password VARCHAR(255), role ENUM('mahasiswa','admin'), created_at DATETIME. Jalankan via MySQL CLI.
- **Modul:** Auth
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-02
- **Estimasi:** 30 menit
- **File yang diubah:** `backend/migrations/001_create_users.sql`

### T-04: API Auth - Register, Login, Middleware Role
- **Deskripsi:** Implement endpoints: `POST /api/auth/register` (validasi NIM unik, email format, password min 8, hash dengan bcryptjs cost 12), `POST /api/auth/login` (rate limit 5x fail = blokir 10 menit, return JWT expiry 12 jam), `middleware/auth.ts` untuk validasi token & cek role admin/mahasiswa.
- **Modul:** Auth
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-03
- **Estimasi:** 2 jam
- **File yang diubah:** `backend/src/routes/auth.ts`, `backend/src/controllers/auth.ts`, `backend/src/middleware/auth.ts`, `backend/src/middleware/rateLimit.ts`

### T-05: ~~API Auth - Reset Password via Email~~ → v2
- **Deskripsi:** Di-defer ke v2. Butuh layanan email (SendGrid/SMTP) yang belum ditentukan untuk v1. Tidak dikerjakan di v1.
- **Modul:** Auth
- **Prioritas:** ~~Mid~~ (v2)
- **Status:** Deferred
- **Dependensi:** -
- **Estimasi:** 1 jam (v2)
- **File yang diubah:** -

---

## Modul 2: Menu & Stok

### T-06: DB Migration - Tabel `menus`
- **Deskripsi:** Buat file SQL migration `002_create_menus.sql`: id (INT AUTO_INCREMENT), nama, deskripsi (TEXT), kategori, harga (INT, Rupiah penuh), stok (INT DEFAULT 0), image_url, is_active (TINYINT(1) DEFAULT 1), updated_at (DATETIME ON UPDATE CURRENT_TIMESTAMP). Charset utf8mb4.
- **Modul:** Menu
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-02
- **Estimasi:** 30 menit
- **File yang diubah:** `backend/migrations/002_create_menus.sql`

### T-07: API `GET /api/menus` - Ambil Menu Aktif Real-time Stok
- **Deskripsi:** Implement endpoint untuk mahasiswa ambil daftar menu aktif (is_active=1) dengan raw SQL, include stok real-time, filter by kategori (opsional via query param), response <300ms. Pakai prepared statement.
- **Modul:** Menu
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-06
- **Estimasi:** 1 jam
- **File yang diubah:** `backend/src/routes/menus.ts`, `backend/src/controllers/menus.ts`

### T-08: API Admin - CRUD Menu & Update Stok
- **Deskripsi:** Implement admin endpoints: `POST /api/admin/menus` (create), `GET /api/admin/menus/:id`, `PATCH /api/admin/menus/:id` (edit nama/harga), `PATCH /api/admin/menus/:id/stock` (update stok). Require role=admin via middleware.
- **Modul:** Menu
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-04, T-06
- **Estimasi:** 1.5 jam
- **File yang diubah:** `backend/src/routes/admin/menus.ts`, `backend/src/controllers/admin/menus.ts`

---

## Modul 3: Pre-Order & Pembayaran

### T-09: DB Migration - Tabel `orders` & `order_items`
- **Deskripsi:** Buat file SQL migration `003_create_orders.sql`. `orders`: id (INT AUTO_INCREMENT), order_code VARCHAR(20) UNIQUE, user_id FK, total_price INT, pickup_at DATETIME, catatan VARCHAR(255) NULL, status ENUM, payment_token NULL, expires_at DATETIME, created_at. `order_items`: id, order_id FK (ON DELETE CASCADE), menu_id FK, quantity INT, price_at_order INT.
- **Modul:** Pre-Order
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-03, T-06
- **Estimasi:** 45 menit
- **File yang diubah:** `backend/migrations/003_create_orders.sql`

### T-10: API `POST /api/orders` - Create Order dengan Pickup Time
- **Deskripsi:** Implement endpoint create order dengan proteksi anti-oversell: `BEGIN` → `SELECT ... FROM menus WHERE id IN (?) FOR UPDATE` → validasi stok tiap item (kalau kurang: `ROLLBACK` + 409) → `UPDATE menus SET stok = stok - ?` → insert `orders` (status `pending`, `expires_at` = now + 10 menit, generate `order_code`) → insert `order_items` → `COMMIT`. Validasi pickup_at hanya slot 11:00–16:00 dan tidak di masa lalu. Maks 10 item per order (FR-09).
- **Modul:** Pre-Order
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-04, T-09
- **Estimasi:** 2.5 jam
- **File yang diubah:** `backend/src/routes/orders.ts`, `backend/src/controllers/orders.ts`, `backend/src/services/order.ts`

### T-11: Integration Midtrans - Snap Token & Webhook
- **Deskripsi:** Integrate Midtrans payment gateway: setelah transaksi stok commit, generate Snap Token via `services/midtrans.ts` lalu simpan ke `orders.payment_token` dan return ke client. Setup webhook `POST /api/payments/webhook` yang validasi signature hash lalu update status `pending`→`paid`. Kalau generate token gagal, `ROLLBACK` transaksi dan kembalikan stok.
- **Modul:** Payment
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-10
- **Estimasi:** 2 jam
- **File yang diubah:** `backend/src/services/midtrans.ts`, `backend/src/routes/payments.ts`, `backend/src/controllers/payments.ts`

### T-12: API `GET /api/orders/me` & `GET /api/orders/:id`
- **Deskripsi:** `GET /api/orders/me` — riwayat order mahasiswa + status, include item details, `pickup_at`, `total_price`, `order_code`. `GET /api/orders/:id` — detail satu order milik user yang sedang login, untuk polling status di halaman tracking (pembatas status `completed`/`cancelled`).
- **Modul:** Pre-Order
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-09
- **Estimasi:** 1 jam
- **File yang diubah:** `backend/src/routes/orders.ts`, `backend/src/controllers/orders.ts`

---

## Modul 4: Admin Dashboard & Order Lifecycle

### T-13: API Admin `GET /api/admin/orders` - Filter by Pickup Time Slot
- **Deskripsi:** Implement endpoint untuk admin: list semua order hari ini (`DATE(pickup_at) = CURDATE()`), grouped/sortable per slot pickup_at, include nama customer + items + status. Mendukung query param filter slot. Support client-side polling tiap 5 detik.
- **Modul:** Admin
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-04, T-09
- **Estimasi:** 1.5 jam
- **File yang diubah:** `backend/src/routes/admin/orders.ts`, `backend/src/controllers/admin/orders.ts`

### T-14: API Admin `PATCH /api/admin/orders/:id/status` - Update Order Status
- **Deskripsi:** Implement endpoint admin untuk update status dengan validasi transisi state yang sah: `pending`→`paid` (via webhook saja), `paid`→`ready`, `ready`→`completed`, plus `paid`/`pending`→`cancelled` (kembalikan stok dalam transaksi). Tolak transisi tidak valid dengan 409.
- **Modul:** Admin
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-04, T-13
- **Estimasi:** 1 jam
- **File yang diubah:** `backend/src/routes/admin/orders.ts`, `backend/src/controllers/admin/orders.ts`

### T-15: Auto-Cancel Order Kedaluwarsa (pengganti Notification Service)
- **Deskripsi:** Implement `setInterval` job di `server.ts` (jalankan tiap 60 detik): cari order status `pending` dengan `expires_at < NOW()`, dalam satu transaksi update status jadi `cancelled` dan kembalikan stok ke `menus` sesuai `order_items`. Dipanggil juga manual lewat `POST /api/orders/sweep-expired` untuk testing. Pengganti scope notifikasi v1 — user tetap bisa monitoring via polling halaman tracking.
- **Modul:** Order Lifecycle
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-10
- **Estimasi:** 1 jam
- **File yang diubah:** `backend/src/services/orderExpiry.ts`, `backend/src/server.ts`, `backend/src/routes/orders.ts`

---

## Modul 5: Frontend UI & Polish

### T-16: UI Mahasiswa - Auth, Menu, Cart, Checkout, Tracking
- **Deskripsi:** Build Next.js frontend pages: Login/Register (form validation), Menu Browse (list + search + stok badge), Cart (add/remove items + qty stepper), Pickup Time Selector (slot 12:00–15:00, disable kalau sudah lewat), Payment redirect ke Midtrans Snap, Order Tracking page dengan **polling `GET /api/orders/:id` tiap 5 detik** (berhenti saat `completed`/`cancelled`). shadcn/ui + Tailwind, mobile-responsive.
- **Modul:** Frontend
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-01, T-10, T-11, T-12
- **Estimasi:** 4 jam
- **File yang diubah:** `frontend/app/`, `frontend/components/`, `frontend/lib/api.ts`

### T-17: UI Admin - Auth, Menu Management, Order Board by Slot
- **Deskripsi:** Build admin dashboard: Login page, Menu CRUD page (add/edit stok), Order board layout (group per slot `pickup_at`, show customer + items), status update buttons (Siap/Selesai), filter per slot. Auto-refresh setiap 5 detik.
- **Modul:** Frontend
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-01, T-08, T-13, T-14
- **Estimasi:** 3.5 jam
- **File yang diubah:** `frontend/app/admin/`, `frontend/components/admin/`

### T-18: Security & Performance Optimization
- **Deskripsi:** Verifikasi rate limiting berjalan (auth + order), JWT validation & role check tidak bisa di-bypass, semua query MySQL pakai prepared statement, input validation manual. Verifikasi proteksi anti-oversell (T-10) dengan mengirim beberapa request bersamaan untuk item stok terbatas. Mobile responsive check (320px / 768px / 1024px), page load < 2 detik.
- **Modul:** Polish
- **Prioritas:** High
- **Status:** Todo
- **Dependensi:** T-04, T-10, T-16, T-17
- **Estimasi:** 2 jam
- **File yang diubah:** `backend/src/middleware/rateLimit.ts`, `backend/src/middleware/auth.ts`, `frontend/next.config.js`

---

## Ringkasan
- **Total Task:** 18 aktif (T-05 deferred ke v2)
- **High Priority:** 15
- **Estimasi Total:** ~25 jam (turun ~2 jam karena notifikasi push dihapus dari v1)
- **Stack Final:** Express + TypeScript, MySQL 8.0 via mysql2, raw SQL tanpa ORM, Midtrans, tanpa push notification
- **Dependencies:** Linear per modul, parallelizable jika ada resource terpisah (Frontend & Backend)

## Deferred ke V2
- **T-05** — Reset password via email (butuh layanan email belum ditentukan)
- **Push notification** — FCM/email untuk status `paid` dan `ready` (user pakai polling di v1)
- **Verifikasi email** saat registrasi
- **Invoice PDF** — v1 tampilkan rincian order langsung di halaman tracking
