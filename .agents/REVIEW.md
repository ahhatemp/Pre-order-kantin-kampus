# Laporan Verifikasi Fungsional — Pre-Order Kantin Kampus

> **Tanggal:** 2026-10-25
> **Lingkup:** perubahan yang belum di-commit setelah `3ad35c6` — soft delete menu, profil &
> jam operasional kantin, Web Push/PWA, order demo otomatis `paid`, penghapusan transisi admin
> `pending → paid`, serta penyesuaian UI terkait.
> **Status:** **Lulus dengan catatan** — 66/66 assertion API dan 22/22 assertion kriptografi Web
> Push lulus; alur UI admin dan mahasiswa berjalan tanpa error console. Ditemukan **1 temuan
> tinggi** (kebocoran kunci VAPID saat deploy Apache) dan beberapa catatan rendah lama/baru.

---

## 1. Lingkungan Pengujian

| Komponen | Detail |
|---|---|
| Database | `kantin_verify2` (terpisah; database development tidak disentuh) |
| Backend API | `http://127.0.0.1:5010/api` — Midtrans placeholder (token `dummy-`) |
| Backend webhook | `http://127.0.0.1:5011/api` — `MIDTRANS_SERVER_KEY=test-server-key` |
| Frontend + API | `http://127.0.0.1:8080` lewat `router.php` |
| PHP / DB | PHP 8.3.33, MariaDB 10.4.32 (`sql_mode` non-strict) |
| Akun seed | `admin@kantin.com` / `user@kantin.com`, password `password123` |
| Alat | Node.js 24 (66 assertion API + 22 assertion crypto), browser in-app |
| Migrasi | `005`–`008` dijalankan pada DB baru, lalu diulang untuk menguji idempotensi — lulus |

Pemeriksaan dasar: `php -l` seluruh file PHP tanpa error; `php tests/jwt_test.php` 14/14 lulus.

---

## 2. Hasil Ringkas

| Area | Hasil |
|---|---|
| Migrasi `005`–`008` + `schema.sql` | Lulus, idempoten |
| Soft delete menu: sembunyi dari publik/admin, detail/update 404, order ditolak | Lulus |
| Riwayat order tetap menampilkan nama menu yang dihapus | Lulus |
| Profil kantin publik + update admin (validasi panjang) | Lulus |
| Jam operasional publik + update admin (7 hari unik, format jam, buka < tutup) | Lulus |
| Validasi pickup mengikuti jam operasional per hari | Lulus |
| Order demo otomatis `paid`; admin tidak bisa set `paid` | Lulus |
| Transisi `paid → ready`, `ready → completed`, `paid → cancelled` | Lulus |
| Web Push: public key, subscribe/unsubscribe, allowlist endpoint, panjang field | Lulus |
| Kriptografi Web Push: enkripsi `aes128gcm` + tanda tangan VAPID ES256 | Lulus (22/22) |
| UI admin: papan pesanan, hapus menu, profil, jam operasional, notifikasi | Lulus |
| UI mahasiswa: menu, slot pickup, checkout demo, halaman sukses | Lulus |
| Error console browser | Tidak ada |

---

## 3. Temuan Baru

### 3.1 [TINGGI] Kunci privat VAPID dapat diunduh lewat web saat deploy Apache/Laragon

- **Lokasi:** `backend/storage/vapid.json` (berisi `privateKey`), `backend/.htaccess:11-14`,
  `backend/README.md:27-28`.
- **Deskripsi:** `.htaccess` hanya menolak `.env|sql|md|log|ini` dan PHP selain `index.php`.
  Aturan rewrite hanya mengarahkan path yang **tidak** menunjuk file yang ada, sehingga file
  nyata seperti `storage/vapid.json` tetap disajikan Apache. README menempatkan folder backend
  di `htdocs/kantin-backend/`, jadi URL seperti
  `http://localhost/kantin-backend/storage/vapid.json` dapat diakses publik.
  Pada server uji `router.php` file ini 404 (router tidak menyajikan `storage/`), sehingga
  kebocoran tidak terlihat di mode development tersebut.
- **Dampak:** siapa pun yang mengetahui URL dapat mengambil kunci privat VAPID dan mengirim
  push notification atas nama aplikasi ke seluruh pelanggan, atau menyalahgunakannya.
- **Saran:** (1) tolak seluruh akses langsung ke `backend/storage/` di `.htaccess`
  (`RewriteRule ^storage/ - [F,L]` atau `Require all denied`; `uploads` sudah disajikan lewat
  controller `upload_serve`), (2) tambahkan `json` ke `FilesMatch`, (3) pindahkan kunci ke luar
  web root / env, dan (4) rotasi kunci VAPID bila build ini pernah di-deploy.

### 3.2 [Rendah] `stok` pecahan diterima dan dipotong diam-diam

- **Lokasi:** `backend/controllers/admin_menus.php:41-42` dan `:143-144`.
- **Deskripsi:** create/update menu menerima `is_float`, lalu menyimpan `(int)$stok`.
  `stok: 3.9` tersimpan sebagai 3 tanpa peringatan.
- **Saran:** wajibkan bilangan bulat (`is_int` atau `filter_var(..., FILTER_VALIDATE_INT)`).

### 3.3 [Rendah] `day_of_week` pecahan dinormalisasi, bukan ditolak

- **Lokasi:** `backend/controllers/kantin.php:84-87`.
- **Deskripsi:** `day_of_week: 1.9` lolos validasi dan di-cast menjadi hari 1. Karena cast
  dilakukan sebelum cek duplikat, input tidak konsisten tetap diterima.
- **Saran:** tolak nilai non-integer sebelum cast.

### 3.4 [Rendah] Subscribe push menerima kunci `p256dh`/`auth` kosong atau tidak valid

- **Lokasi:** `backend/controllers/push.php:29-42`.
- **Deskripsi:** hanya panjang maksimum yang divalidasi; string kosong atau base64 rusak tetap
  disimpan. Pengiriman berikutnya akan gagal di sisi gateway tanpa umpan balik ke klien.
- **Saran:** validasi base64url dan panjang kunci (`p256dh` 65 byte, `auth` 16 byte), atau
  minimal tolak string kosong.

### 3.5 [Rendah] Catatan "dibatalkan otomatis dalam 10 menit" muncul pada mode demo

- **Lokasi:** `frontend/js/app.js:890`.
- **Deskripsi:** pada mode demo order langsung `paid` saat checkout, tetapi halaman pembayaran
  tetap menampilkan ancaman pembatalan 10 menit. Halaman sukses sudah benar (tanpa catatan itu).
- **Saran:** tampilkan catatan hanya bila order masih `pending`/gateway aktif.

### 3.6 [Rendah] Tombol Batal pesanan tidak ada di UI admin

- **Lokasi:** `frontend/js/app.js:2139` (hanya aksi `ready`), berbanding `.agents/3-TASKS.md`
  T-17 yang menyebut tombol Siap/Selesai/Batal dan `backend/controllers/admin_orders.php`
  yang mengizinkan `paid → cancelled`.
- **Deskripsi:** admin tidak bisa membatalkan pesanan dari papan UI; hanya lewat API.
- **Saran:** tambahkan tombol Batal untuk status `paid` atau selaraskan T-17 dengan UI aktual.

### 3.7 [Rendah] Drift dokumentasi jam operasional dan papan pesanan

- **Lokasi:** `.agents/2-TECH-SPEC.md`, `.agents/1-PRD.md` (jam backend 07:00–17:00),
  `.agents/3-TASKS.md` T-10/T-13 (slot 12:00–16:00, pengelompokan per slot/tanggal).
- **Deskripsi:** backend kini memakai jam dinamis dari tabel `kantin_hours` (seed 07:00–16:00,
  Jumat 15:00, Sabtu/Minggu tutup), sedangkan papan admin menampilkan daftar datar tanpa filter
  tanggal. Dokumen belum mengikuti perubahan ini.
- **Saran:** perbarui PRD/TECH-SPEC/TASKS agar sesuai implementasi aktual.

### 3.8 [Rendah] Kontak di halaman Bantuan di-hardcode

- **Lokasi:** `frontend/js/app.js` (teks bantuan `0812-3456-7890`).
- **Deskripsi:** profil kantin dapat diubah admin, tetapi halaman Bantuan tetap menampilkan
  nomor lama.
- **Saran:** ambil kontak dari `state.profile.kontak`.

---

## 4. Temuan Lama (Status Terkini)

| No | Temuan | Status |
|---|---|---|
| 3.1 | Create menu belum memvalidasi panjang nama/kategori/URL | **Masih terbuka** (`admin_menus.php:38-42`) |
| 3.2 | Multipart kosong dijawab 413, bukan 400 | **Masih terbuka** (`uploads.php:12-17`) |
| 3.3 | `menu_id`/`quantity` pecahan diterima lalu dipotong | **Masih terbuka** (`orders.php:28-30`) |
| 3.4 | `image_url` dirender ke `src` tanpa escaping | **Masih terbuka** (`app.js:808, 831, 1279`) |
| 4.1 | Slot pickup < 5 menit bisa dipilih | **Selesai** — `MIN_PICKUP_LEAD = 5` (`app.js:108,154`) |
| 4.2 | Admin bisa `pending → paid` | **Selesai** — endpoint menolak `paid`, tombol dihapus |
| 4.3 | Hitungan header papan pesanan | **Selesai** — satu sumber `state.orders`; sisa drift dokumen masuk 3.7 |
| 4.4 | Toast "dinotifikasi" padahal belum ada notifikasi | **Selesai** — kini "Pesanan ditandai siap diambil." (`app.js:2139`) |

---

## 5. Perilaku yang Terverifikasi

- `POST /api/admin/menus/{id}/delete` melakukan soft delete: menu hilang dari katalog publik
  dan daftar admin, detail/update 404, order baru dengan menu itu ditolak, tetapi nama menu pada
  order lama tetap ada.
- Profil kantin publik menampilkan nama/lokasi/kontak/deskripsi; PATCH admin menolak tipe salah
  dan panjang berlebih; perubahan langsung terlihat di endpoint publik.
- PATCH jam operasional menolak jumlah hari ≠ 7, hari duplikat, `is_open` non-boolean, format jam
  salah, dan `open >= close` saat buka; pickup di luar jam/hari tutup ditolak 409.
- Mode demo (`payment_token` berawalan `dummy-`) membuat order langsung `paid`; endpoint admin
  menolak status `paid`; `paid → ready` dan `ready → completed` berhasil.
- Push: tanpa token 401, mahasiswa 403; endpoint di luar allowlist ditolak; subscribe menyimpan
  satu baris per endpoint (upsert); unsubscribe menghapus berdasarkan user + hash endpoint.
- Crypto: payload dapat didekripsi dengan kunci subscriber; header `Authorization: vapid` ES256
  terverifikasi terhadap public key.
- UI admin: papan pesanan tidak lagi menampilkan "Tandai Dibayar", tombol "Siap" muncul untuk
  order `paid`; modal hapus menu berfungsi, baris hilang, API detail 404, toast benar.
- UI admin: profil terisi dari API dan simpan berhasil; jam operasional menampilkan 7 baris,
  toggle + simpan berhasil (baseline dikembalikan setelah uji).
- UI notifikasi: service worker terdaftar/aktif, public key 200, status izin `denied`
  ditampilkan dengan pesan yang tepat.
- UI mahasiswa: hari tutup menampilkan "Kantin tutup hari ini · Pickup mulai 12:00 besok";
  slot hanya menampilkan hari buka berikutnya; checkout demo berakhir di halaman
  "Pembayaran Berhasil" tanpa catatan 10 menit; order muncul "Sudah Dibayar" di halaman Pesanan.
- Tidak ada pesan error di console browser selama pengujian UI.

---

## 6. Belum Diuji / Risiko Tersisa

- Render gambar menu tidak dapat diverifikasi visual karena viewport browser in-app tersembunyi
  (screenshot tidak tersedia); file aset gambar memang ada.
- Push notification end-to-end ke perangkat nyata (izin browser di lingkungan uji `denied`).
- Transaksi Midtrans sandbox sungguhan dan webhook dari Midtrans asli.
- Uji beban/konkurensi, termasuk race batas 3 order pending dan pengiriman push massal.
- Audit responsif/aksesibilitas menyeluruh.
- Uji eksploit XSS langsung untuk temuan 3.4 (diblokir kebijakan alat).
- CORS `*`, `JWT_SECRET` default, dan kunci VAPID produksi perlu ditinjau sebelum deploy.

---

## 7. Artefak & Pembersihan

- Database `kantin_verify2` dipertahankan agar temuan dapat diperiksa ulang; terpisah dari DB
  development.
- `.verify-tmp/` (skrip uji API & crypto) sudah dihapus.
- Tiga bucket rate-limit hasil uji dihapus; dua bucket lama (2026-10-24) dibiarkan.
- Menu uji `Menu UI Hapus 1792946913988` sudah di-soft-delete lewat UI; order uji
  `ORD-7B96057E` dibuat dalam mode demo lalu dibatalkan lewat API agar data tidak menggantung.
- Profil kantin dan jam operasional dikembalikan ke nilai awal setelah uji.
- Server uji 5010/5011/8080 dihentikan setelah pengujian.
- Tidak ada perubahan kode aplikasi selama verifikasi.
