# PRD: Sistem Pre-Order Kantin Kampus

## 1. Visi & Tujuan Produk

### Visi Produk
Sistem pre-order kantin kampus yang eliminasi antrian dengan let mahasiswa order ahead, ambil on-time sesuai jadwal. Kantin dapat predictability stok dan workflow prep.

### Tujuan Utama
1. **Kurangi antrian fisik 60%** — metric: avg queue time jatuh dari 20 menit jadi 8 menit
2. **Increase kantin efficiency 40%** — metric: order accuracy 98%+, waste turun jadi <5%
3. **Improve mahasiswa experience** — metric: user retention 75%+ after 1 bulan

### Value Proposition
- **Pre-order dengan pickup time**: tidak usah antri, ambil exactly saat butuh
- **Real-time stok visibility**: tahu makanan tersedia sebelum order
- **Faster payment**: bayar saat order, ambil tanpa transaksi di tempat

---

## 2. User Persona

### Persona 1: Budi (Mahasiswa)
- **Usia/Pekerjaan:** 20, Mahasiswa Teknik Informatika
- **Level Teknis:** Menengah (familiar dengan app mobile)
- **Tujuan:** Order makanan sebelum kelas selesai, ambil langsung tanpa antri
- **Pain Points:** Antrian kantin 15-20 menit, sering stok habis saat tiba, terburu-buru antar kelas
- **Motivasi:** Hemat waktu, guaranteed dapat makanan favorit, bisa makan on-schedule

### Persona 2: Siti (Admin Kantin)
- **Usia/Pekerjaan:** 35, Operator/Admin Kantin
- **Level Teknis:** Pemula (basic computer skills, smartphone user)
- **Tujuan:** Tahu order masuk real-time, prep makanan sesuai demand, hindari over-prep atau under-prep
- **Pain Points:** Tidak tahu demand sebelumnya, banyak waste, customer komplain stok habis, manual order tracking ribet
- **Motivasi:** Efisiensi kerja, reduce waste, improve customer satisfaction, predictable workflow

---

## 3. User Stories

### Modul 1: Autentikasi & Akun
- Sebagai mahasiswa baru, saya ingin register dengan NIM dan email, agar bisa membuat akun.
- Sebagai mahasiswa, saya ingin login dengan email/NIM, agar bisa akses aplikasi.
- Sebagai mahasiswa, saya ingin reset password, agar bisa kembali akses jika lupa.
- Sebagai admin kantin, saya ingin login khusus admin, agar bisa manage orders dan stok.

### Modul 2: Browse & Pre-Order
- Sebagai mahasiswa, saya ingin lihat menu hari ini dengan harga, agar tahu ada apa.
- Sebagai mahasiswa, saya ingin lihat stok real-time per item, agar tahu makanan tersedia atau tidak.
- Sebagai mahasiswa, saya ingin order makanan dan pilih jam pickup (jam 12, 1, 2, dst), agar sesuai jadwal kelas.
- Sebagai mahasiswa, saya ingin add multiple items dalam satu order, agar beli bersamaan.
- Sebagai mahasiswa, saya ingin lihat total harga sebelum checkout, agar tahu budget.

### Modul 3: Pembayaran & Checkout
- Sebagai mahasiswa, saya ingin bayar via e-wallet/transfer bank, agar mudah dan aman.
- Sebagai mahasiswa, saya ingin confirm order dan terima invoice, agar punya bukti pembayaran.

### Modul 4: Order Management (Admin)
- Sebagai admin kantin, saya ingin lihat semua order masuk real-time, agar tahu demand.
- Sebagai admin kantin, saya ingin filter order by pickup time, agar organize prep schedule.
- Sebagai admin kantin, saya ingin mark order as ready, agar user tahu pesannya bisa diambil.
- Sebagai admin kantin, saya ingin update stok item, agar sistem akurat dan prevent over-order.

### Modul 5: Status Tracking *(v1 — tanpa push notification)*
- Sebagai mahasiswa, saya ingin status pesanan ter-update otomatis saat halaman tracking dibuka, agar tahu pesanan sudah siap diambil tanpa refresh manual.
- Sebagai mahasiswa, saya ingin melihat slot pickup time, agar tahu kapan harus datang ke kantin.

> **Catatan scope v1:** Push notification (FCM/email) tidak diimplementasikan di v1. Pengguna tahu status baru lewat polling otomatis di halaman tracking. Modul notifikasi penuh menjadi v2.

---

## 4. Functional Requirements

### Modul 1: Autentikasi & Akun

**FR-01: Registrasi Mahasiswa**
- **Input:** NIM, nama, email, password
- **Proses:** Validasi NIM unik, validasi email format, hash password (bcryptjs cost 12), simpan ke DB
- **Output:** Akun terdaftar dan langsung aktif (verifikasi email menjadi v2)
- **Aturan:** Password minimal 8 karakter, NIM harus format valid, email harus unik

**FR-02: Login Mahasiswa**
- **Input:** Email/NIM, password
- **Proses:** Verifikasi kredensial, generate JWT token
- **Output:** Token akses, redirect ke dashboard mahasiswa
- **Aturan:** 5x login gagal = blokir 10 menit

**FR-03: Login Admin**
- **Input:** Admin ID, password
- **Proses:** Verifikasi role admin, generate JWT token khusus
- **Output:** Token akses, redirect ke dashboard admin
- **Aturan:** Role "admin" only bisa akses panel

**FR-04: Reset Password**
- **Input:** Email terdaftar
- **Proses:** Generate reset token, kirim link via email, user set password baru
- **Output:** Password berhasil direset
- **Aturan:** Reset link valid 24 jam

### Modul 2: Menu & Stok

**FR-05: Lihat Menu Hari Ini**
- **Input:** Tanggal hari ini
- **Proses:** Query menu dari DB sesuai tanggal, ambil harga dan deskripsi
- **Output:** List menu dengan harga, gambar (opsional), kategori
- **Aturan:** Menu hanya tampil jika status aktif

**FR-06: Update Stok (Admin)**
- **Input:** Item ID, jumlah stok baru
- **Proses:** Update stok di DB, log perubahan
- **Output:** Stok terupdate; ditandai "stok menipis" di UI admin jika stok < threshold (misal 5 item)
- **Aturan:** Hanya admin yang bisa update stok

**FR-07: Real-time Stok Visibility**
- **Input:** Item ID
- **Proses:** Query stok saat itu dari DB
- **Output:** Tampil stok available, "Out of Stock" jika habis
- **Aturan:** Update setiap order confirmed/marked ready

### Modul 3: Pre-Order & Pickup Time

**FR-08: Create Order dengan Pickup Time**
- **Input:** User ID, item list, pickup time (jam), catatan khusus
- **Proses:** Validasi stok cukup, deduct stok sementara, create order record, generate order ID
- **Output:** Order created, order confirmation screen
- **Aturan:** Pickup time pilihan: 12:00, 13:00, 14:00, 15:00 (configurable by admin)

**FR-09: Add Multiple Items**
- **Input:** Multiple item IDs dengan qty
- **Proses:** Validate stok semua items, add ke cart
- **Output:** Cart terupdate, total harga
- **Aturan:** Max items per order = 10

**FR-10: Calculate Total Price**
- **Input:** Item list dengan qty
- **Proses:** Sum (item price × qty) + tax/fee jika ada
- **Output:** Total harga, breakdown itemized
- **Aturan:** Bisa ada diskon khusus (future feature)

### Modul 4: Pembayaran

**FR-11: Payment Gateway Integration**
- **Input:** Order ID, payment method (e-wallet/transfer)
- **Proses:** Call payment API, wait confirmation, update order status
- **Output:** Payment confirmed, order status = "paid"
- **Aturan:** Payment timeout 10 menit, order auto-cancel jika tidak bayar

**FR-12: Invoice / Rincian Pesanan**
- **Input:** Order ID
- **Proses:** Ambil rincian item, total harga, status pembayaran dari DB
- **Output:** Rincian order tampil di halaman tracking (dengan `order_code` yang human-readable, misal `ORD-241011-001`)
- **Aturan:** Generate file PDF untuk unduhan ditunda ke v2

### Modul 5: Order Management (Admin)

**FR-13: View All Orders Real-time**
- **Input:** Admin dashboard request
- **Proses:** Query semua orders hari ini, sort by pickup time
- **Output:** List orders dengan status (pending, paid, ready, completed)
- **Aturan:** Auto-refresh setiap 5 detik

**FR-14: Filter Orders by Pickup Time**
- **Input:** Pickup time selection (12:00, 13:00, dst)
- **Proses:** Filter orders dengan pickup time tersebut
- **Output:** List orders untuk jam tersebut
- **Aturan:** Help admin prepare makanan per slot

**FR-15: Mark Order as Ready**
- **Input:** Order ID
- **Proses:** Update order status ke "ready"
- **Output:** Order status updated; user melihat perubahan lewat polling halaman tracking
- **Aturan:** Hanya admin yang bisa mark ready

**FR-16: Order Tracking (User)**
- **Input:** Order ID
- **Proses:** Client polling `GET /api/orders/:id` setiap 5 detik saat halaman tracking terbuka
- **Output:** Current status (pending, paid, ready, completed) ter-update otomatis
- **Aturan:** Polling berhenti saat status `completed` atau `cancelled`

### Modul 6: Notifikasi *(v2 — tidak dikerjakan di v1)*

**FR-17: Order Confirmation Notification** *(v2)*
- **Input:** Order created
- **Proses:** Send push notification (FCM) / email
- **Output:** User ternotifikasi order diterima
- **Aturan:** Immediate after payment confirmed

**FR-18: Order Ready Notification** *(v2)*
- **Input:** Admin mark order ready
- **Proses:** Send push notification
- **Output:** User notified order siap diambil
- **Aturan:** Include pickup time reminder

**FR-19: Auto-Cancel Order Kedaluwarsa** *(baru, v1)*
- **Input:** Order dengan status `pending` yang `expires_at` sudah lewat
- **Proses:** Job `setInterval` di server membatalkan order dan mengembalikan stok ke `menus` dalam satu transaksi
- **Output:** Order berstatus `cancelled`, stok menu pulih
- **Aturan:** Berlaku 10 menit setelah order dibuat (FR-11)

---

## 5. Non-Functional Requirements

### Performa
- Waktu muat halaman utama < 2 detik
- API response time < 300ms (cold), < 100ms (warm)
- Support 500 concurrent users (peak lunch hour)

### Keamanan
- Password di-hash (bcryptjs, cost 12)
- HTTPS wajib semua traffic
- JWT token expiry 12 jam (kurangi dari 24)
- Role-based access control (mahasiswa/admin strict separation)
- Brute-force protection: 5x gagal login = blokir 10 menit
- Semua query pakai prepared statement (placeholder `?`) untuk mencegah SQL injection
- Pembuatan order dibungkus transaksi + row locking untuk mencegah stok oversell

### Skalabilitas
- Target 2000 mahasiswa aktif
- Database: MySQL 8.0 dengan connection pooling (`mysql2`)

### Availability & Reliability
- Uptime target 99.5% (downtime < 2 jam per bulan)
- Backup database harian
- Order data harus survive single server failure

### Usability
- Mobile-first responsive (80% user pakai mobile)
- Bahasa Indonesia (UI, messages)
- Offline fallback: bisa lihat menu cached, order baru butuh online

---

## 6. Out of Scope & Dependensi

### Out of Scope (Tidak Dikerjakan di V1)
- **Push notification (FCM/email)** — ditunda ke v2. Di v1 user polling status pesanan di halaman tracking. Config Firebase + service worker terlalu berat untuk scope v1.
- **Email verifikasi akun** — ditunda ke v2. Registrasi v1 langsung aktif setelah validasi NIM/email.
- **Invoice PDF** — ditunda ke v2. v1 menampilkan rincian order langsung di halaman tracking.
- **Delivery ke kelas** — ditunda ke v2 (butuh sistem logistik terpisah)
- **Rating & review menu** — ditunda ke v2
- **Loyalty points / rewards** — ditunda ke v2
- **Multiple kantin support** — v1 fokus 1 kantin dulu
- **Pre-order h+1 / h+2** — v1 hanya bisa order hari H
- **Split payment (bayar gabung dengan teman)** — kompleks, di v2

### Dependensi
- **Payment gateway** — Midtrans untuk e-wallet, QRIS, & transfer bank
- **Database** — MySQL 8.0
- **Backend framework** — Node.js + Express + TypeScript (final, lihat Tech Spec)
- **Frontend framework** — Next.js (App Router) + Tailwind CSS + shadcn/ui (final)
- **Push notification service** — Firebase Cloud Messaging (FCM) — **v2**, tidak dipakai di v1
- **Hosting** — Cloud provider (AWS/GCP/Railway) — TBD saat deployment

### Asumsi
- Mahasiswa punya smartphone & koneksi internet stabil
- Mahasiswa punya email aktif (dipakai sebagai identitas login)
- Mahasiswa punya e-wallet atau rekening bank untuk bayar
- Kantin punya 1 admin yang handle sistem (bisa multiple di v2)
- Menu kantin sudah terstruktur (tidak berubah drastis tiap hari)
- Kantin buka jam 07:00 - 17:00, pickup time configurable sesuai jam buka
