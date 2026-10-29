# PRD: Sistem Pre-Order Kantin Kampus

> **Status dokumen (v1):** PRD ini sudah diselaraskan dengan implementasi aktual di repo per
> 2026-10-27 — backend PHP 8 native + PDO/MySQL, frontend HTML/CSS/JS tanpa build step, termasuk
> Web Push/PWA, profil kantin, dan jam operasional. Rincian teknis ada di `2-TECH-SPEC.md`,
> status pekerjaan di `3-TASKS.md`.

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
- Sebagai mahasiswa, saya ingin login dengan email, agar bisa akses aplikasi.
- Sebagai mahasiswa, saya ingin reset password, agar bisa kembali akses jika lupa. *(v2)*
- Sebagai admin kantin, saya ingin login dengan akun admin, agar bisa manage orders dan stok.

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

### Modul 5: Status Tracking *(v1)*
- Sebagai mahasiswa, saya ingin status pesanan ter-update otomatis saat halaman tracking dibuka, agar tahu pesanan sudah siap diambil tanpa refresh manual.
- Sebagai mahasiswa, saya ingin melihat slot pickup time, agar tahu kapan harus datang ke kantin.
- Sebagai mahasiswa, saya ingin menerima notifikasi saat pesanan siap diambil, agar tidak perlu terus membuka aplikasi. *(v1 — Web Push)*

> **Catatan scope v1:** Web Push untuk status `ready` sudah diimplementasikan (VAPID + service
> worker di halaman Notifikasi). Halaman tracking tetap menyegarkan data lewat polling otomatis
> karena push bergantung pada izin browser dan hanya dikirim saat pesanan siap. Push untuk
> konfirmasi pembayaran (`paid`) dan kanal email/FCM tetap v2.

---

## 4. Functional Requirements

### Modul 1: Autentikasi & Akun

**FR-01: Registrasi Mahasiswa**
- **Input:** NIM (opsional), nama, email, password
- **Proses:** Validasi email format & keunikan, hash password dengan `password_hash()` bcrypt cost 12, simpan ke DB dengan role `mahasiswa`
- **Output:** Akun terdaftar dan langsung aktif (verifikasi email menjadi v2)
- **Aturan:** Nama dan email wajib, password minimal 8 karakter, NIM maksimal 20 karakter dan unik bila diisi

**FR-02: Login Mahasiswa**
- **Input:** Email, password
- **Proses:** Verifikasi kredensial, generate JWT HS256 berlaku 12 jam
- **Output:** Token akses, redirect ke halaman menu
- **Aturan:** Email dan password wajib diisi; kredensial salah mengembalikan 401 tanpa pembatasan percobaan

**FR-03: Login Admin**
- **Input:** Email, password
- **Proses:** Verifikasi kredensial dan role `admin`, generate JWT HS256
- **Output:** Token akses, redirect ke papan pesanan admin
- **Aturan:** Role "admin" saja yang bisa mengakses endpoint `/api/admin/*`

**FR-04: Reset Password** *(v2 — tidak dikerjakan di v1)*
- **Input:** Email terdaftar
- **Proses:** Generate reset token, kirim link via email, user set password baru
- **Output:** Password berhasil direset
- **Aturan:** Ditunda karena belum ada layanan email yang ditentukan

### Modul 2: Menu & Stok

**FR-05: Lihat Menu Aktif**
- **Input:** —
- **Proses:** Query menu dengan `is_active = 1`, urut nama
- **Output:** List menu dengan harga, gambar (opsional), kategori, dan stok terkini
- **Aturan:** Menu nonaktif tidak tampil ke mahasiswa; v1 memakai satu set menu (belum ada jadwal per tanggal)

**FR-06: Update Stok (Admin)**
- **Input:** Item ID, jumlah stok baru
- **Proses:** Update `menus.stok` di DB
- **Output:** Stok terupdate; UI admin menandai stok menipis/habis
- **Aturan:** Hanya admin yang bisa update stok; v1 belum menyimpan log perubahan stok

**FR-07: Stok Visibility**
- **Input:** Item ID
- **Proses:** Query stok saat itu dari DB
- **Output:** Tampil stok available, "Out of Stock" jika habis
- **Aturan:** Stok dipotong saat order dibuat dan dikembalikan saat order dibatalkan/kedaluwarsa

### Modul 3: Pre-Order & Pickup Time

**FR-08: Create Order dengan Pickup Time**
- **Input:** Item list, `pickup_at` (tanggal + jam), catatan khusus (opsional)
- **Proses:** Validasi stok dalam transaksi + row lock, potong stok, buat order `pending`, set `expires_at` = sekarang + 10 menit
- **Output:** Order created beserta `order_code`, total, dan `payment_token`
- **Aturan:** Slot pickup mengikuti jam operasional di tabel `kantin_hours` (seed: Senin–Kamis 07:00–16:00, Jumat 07:00–15:00, Sabtu/Minggu tutup). UI menawarkan slot per jam mulai `max(12:00, jam buka)` sampai jam tutup pada hari buka pertama yang masih punya slot; backend menerima pickup minimal 5 menit ke depan, maksimal 7 hari ke depan, dan hanya pada hari serta jam buka.

**FR-09: Add Multiple Items**
- **Input:** Multiple item IDs dengan qty
- **Proses:** Validasi stok semua item, simpan ke keranjang (client) lalu checkout
- **Output:** Cart terupdate, total harga
- **Aturan:** Maksimal 10 baris item per order; qty per item 1–50; item dengan menu_id sama digabung

**FR-10: Calculate Total Price**
- **Input:** Item list dengan qty
- **Proses:** Sum (harga menu saat order × qty), harga disimpan di `order_items.price_at_order`
- **Output:** Total harga, breakdown itemized
- **Aturan:** Tidak ada pajak/biaya tambahan di v1; diskon khusus ditunda ke v2

### Modul 4: Pembayaran

**FR-11: Payment Gateway Integration**
- **Input:** Order ID, metode pembayaran dipilih di halaman Snap Midtrans (e-wallet/QRIS/transfer bank)
- **Proses:** Generate Snap Token saat order dibuat; Midtrans mengirim webhook untuk update status
- **Output:** Payment confirmed, order status = "paid"
- **Aturan:** Payment timeout 10 menit, order auto-cancel jika tidak bayar. Bila Midtrans belum dikonfigurasi, order otomatis dianggap sudah dibayar saat checkout (mode demo) tanpa konfirmasi manual admin.

**FR-12: Invoice / Rincian Pesanan**
- **Input:** Order ID
- **Proses:** Ambil rincian item, total harga, status pembayaran dari DB
- **Output:** Rincian order tampil di halaman tracking (dengan `order_code` human-readable, misal `ORD-A1B2C3D4`)
- **Aturan:** Generate file PDF untuk unduhan ditunda ke v2

### Modul 5: Order Management (Admin)

**FR-13: View All Orders Real-time**
- **Input:** Admin dashboard request, filter tanggal opsional (`?pickup_date=`)
- **Proses:** Query orders, sort by `pickup_at`, sertakan item dan data pemesan
- **Output:** List orders dengan status (pending, paid, ready, completed, cancelled)
- **Aturan:** Halaman admin memuat ulang data setiap 30 detik selama terbuka; API mendukung filter `?pickup_date=`, tetapi UI saat ini menampilkan semua order (tanpa filter tanggal)

**FR-14: Filter Orders by Status (Admin)**
- **Input:** Pilihan filter status di papan pesanan (Semua, Perlu disiapkan, Siap diambil, Selesai)
- **Proses:** Filter daftar order yang sudah dimuat di client berdasarkan `status`
- **Output:** Daftar order dengan status terpilih, diurutkan dari pesanan terbaru
- **Aturan:** Papan menampilkan daftar datar (belum dikelompokkan per slot/tanggal) dan menyegarkan data setiap 30 detik

**FR-15: Mark Order as Ready**
- **Input:** Order ID
- **Proses:** Update order status ke "ready"
- **Output:** Order status updated; user melihat perubahan lewat polling halaman tracking
- **Aturan:** Hanya admin yang bisa mark ready

**FR-16: Order Tracking (User)**
- **Input:** Order ID
- **Proses:** Saat halaman tracking terbuka, client memuat ulang `GET /api/orders/:id` setiap 30 detik (dan saat tab kembali aktif)
- **Output:** Current status (pending, paid, ready, completed) ter-update otomatis
- **Aturan:** Status final (`completed`/`cancelled`) tidak berubah lagi; halaman tetap menyegarkan data selama terbuka

### Modul 6: Notifikasi *(v1 — Web Push pesanan siap; konfirmasi order masih v2)*

**FR-17: Order Confirmation Notification** *(v2 — tidak dikerjakan di v1)*
- **Input:** Order created
- **Proses:** Send push notification (FCM) / email
- **Output:** User ternotifikasi order diterima
- **Aturan:** Immediate after payment confirmed

**FR-18: Order Ready Notification** *(v1 — Web Push)*
- **Input:** Admin menandai order `ready`
- **Proses:** Backend mengirim Web Push berpayload (`title`, `body`, `url`, `tag`) ke seluruh subscription aktif milik pemesan lewat gateway push browser
- **Output:** Notifikasi "Pesananmu telah siap" muncul; klik membuka halaman pesanan
- **Aturan:** Subscription disimpan per perangkat dengan validasi endpoint & kunci, pengiriman maksimal `TTL` 24 jam, subscription yang ditolak gateway dihapus. User mengaktifkan notifikasi lewat halaman Notifikasi (izin browser)

**FR-19: Auto-Cancel Order Kedaluwarsa** *(v1)*
- **Input:** Order dengan status `pending` yang `expires_at` sudah lewat
- **Proses:** Sweep di sela request API (maksimal sekali per menit) membatalkan order dan mengembalikan stok ke `menus` dalam satu transaksi
- **Output:** Order berstatus `cancelled`, stok menu pulih
- **Aturan:** Berlaku 10 menit setelah order dibuat (FR-11); tidak memakai cron/daemon terpisah

### Modul 7: Profil & Jam Operasional Kantin *(v1)*

**FR-20: Lihat Profil Kantin**
- **Input:** —
- **Proses:** Query baris tunggal `kantin_profile`
- **Output:** Nama, lokasi, kontak, dan deskripsi kantin tampil di halaman Tentang, Bantuan, dan header aplikasi
- **Aturan:** Endpoint publik; data seed awal "Kantin Kampus"

**FR-21: Kelola Profil & Jam Operasional (Admin)**
- **Input:** Nama (wajib, ≤ 100 karakter), lokasi (≤ 150), kontak (≤ 30), deskripsi (≤ 255); serta 7 baris jam operasional (`day_of_week` 1–7, `is_open`, `open_time`, `close_time` format `HH:MM`)
- **Proses:** PATCH admin memvalidasi tipe/panjang, keunikan 7 hari, dan `open < close` saat buka; perubahan jam disimpan dalam satu transaksi
- **Output:** Profil dan jam operasional langsung dipakai frontend (slot pickup) dan halaman admin
- **Aturan:** Hanya admin; validasi backend menolak jumlah hari ≠ 7, hari duplikat, dan format jam salah

---

## 5. Non-Functional Requirements

### Performa
- Waktu muat halaman utama < 2 detik
- API response time < 300ms (cold), < 100ms (warm)
- Support 500 concurrent users (peak lunch hour)

### Keamanan
- Password di-hash dengan `password_hash()` bcrypt cost 12
- HTTPS wajib semua traffic (di luar scope kode aplikasi)
- JWT HS256 dengan expiry 12 jam
- Role-based access control (mahasiswa/admin strict separation)
- Semua query memakai prepared statement PDO (placeholder `?`) untuk mencegah SQL injection
- Pembuatan order dibungkus transaksi + `SELECT ... FOR UPDATE` untuk mencegah stok oversell
- Webhook Midtrans divalidasi dengan `signature_key` dan `gross_amount`
- Subscription Web Push divalidasi (allowlist host gateway, panjang endpoint, `p256dh` 65 byte,
  `auth` 16 byte); kunci privat VAPID disimpan di `backend/storage/` yang diblokir `.htaccess`

### Skalabilitas
- Target 2000 mahasiswa aktif
- Database: MySQL 8.0 diakses lewat PDO (satu koneksi per request, tanpa connection pool aplikasi)
- Rate limit disimpan di file lokal; cocok untuk satu server, perlu pindah ke DB/Redis bila multi-server

### Availability & Reliability
- Uptime target 99.5% (downtime < 2 jam per bulan)
- Backup database harian (prosedur operasional, belum diotomasi di repo)
- Order data harus survive single server failure (tanggung jawab konfigurasi database/hosting)

### Usability
- Mobile-first responsive (80% user pakai mobile)
- Bahasa Indonesia (UI, messages)
- Tidak ada mode offline di v1; semua data menu dan pesanan diambil dari API

---

## 6. Out of Scope & Dependensi

### Out of Scope (Tidak Dikerjakan di V1)
- **Push/email konfirmasi order** — ditunda ke v2. Web Push untuk status `ready` sudah ada di v1;
  konfirmasi pembayaran dan kanal email/FCM belum.
- **Notifikasi email** — ditunda ke v2; v1 memakai Web Push browser.
- **Email verifikasi akun** — ditunda ke v2. Registrasi v1 langsung aktif setelah validasi email/NIM.
- **Reset password via email** — ditunda ke v2 karena belum ada layanan email.
- **Invoice PDF** — ditunda ke v2. v1 menampilkan rincian order langsung di halaman tracking.
- **Delivery ke kelas** — ditunda ke v2 (butuh sistem logistik terpisah)
- **Rating & review menu** — ditunda ke v2
- **Loyalty points / rewards** — ditunda ke v2
- **Multiple kantin support** — v1 fokus 1 kantin dulu
- **Split payment (bayar gabung dengan teman)** — kompleks, di v2
- **Jadwal menu per tanggal** — v1 memakai satu daftar menu aktif; order bisa untuk hari ini atau beberapa hari ke depan (maksimal 7 hari)

### Dependensi
- **Payment gateway** — Midtrans Snap (e-wallet, QRIS, transfer bank)
- **Database** — MySQL 8.0 / MariaDB 10.3+, charset `utf8mb4`
- **Backend** — PHP 8.0+ native tanpa framework, PDO, ekstensi `curl`
- **Frontend** — HTML + CSS + JavaScript polos, tanpa build step
- **Web server** — PHP built-in server (development) atau Apache/Laragon (`.htaccess`)
- **Push notification service** — Web Push standar (VAPID ES256 + payload `aes128gcm`) lewat
  browser Push API, tanpa layanan pihak ketiga. Gateway yang diizinkan: FCM, Mozilla, Apple, WNS.
  Butuh HTTPS atau localhost.
- **Hosting** — Cloud provider (AWS/GCP/Railway) — TBD saat deployment

### Asumsi
- Mahasiswa punya smartphone & koneksi internet stabil
- Mahasiswa punya email aktif (dipakai sebagai identitas login)
- Mahasiswa punya e-wallet atau rekening bank untuk bayar
- Kantin punya 1 admin yang handle sistem (bisa multiple di v2)
- Menu kantin sudah terstruktur (tidak berubah drastis tiap hari)
- Jam operasional kantin diatur admin lewat tabel `kantin_hours` (seed: Senin–Kamis 07:00–16:00, Jumat 07:00–15:00, Sabtu/Minggu tutup); frontend menawarkan slot pickup per jam pada hari buka dan backend menolak jam di luar jam operasional
