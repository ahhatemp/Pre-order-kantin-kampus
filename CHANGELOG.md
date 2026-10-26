# Changelog

Semua perubahan penting pada proyek ini didokumentasikan di file ini.

Format mengikuti [Keep a Changelog](https://keepachangelog.com/id/1.1.0/).

## [Unreleased] — 2026-10-26

### Changed

- **Backend — helper pesanan bersama**: menambahkan `order_items_by_order()`, `attach_order_items()`, dan `refund_order_stock()` di `backend/services/order.php`; sekarang dipakai oleh daftar pesanan user, daftar pesanan admin, pembatalan admin, webhook pembayaran, dan sweep pesanan kedaluwarsa sehingga duplikasi grouping item dan pengembalian stok hilang.
- **Backend — konfigurasi terpusat**: `config()` di `backend/helpers.php` memuat konfigurasi sekali dan menyimpannya di cache statis; `db.php`, `auth.php`, `check_db.php`, `midtrans.php`, `push.php`, dan test JWT memakai helper ini alih-alih `require config.php` berulang.
- **Backend — front controller**: `backend/api/index.php` memuat konfigurasi lebih awal agar zona waktu dan `.env` siap sebelum handler berjalan.
- **Backend — validasi dan query**: validasi harga/stok serta pembentukan field pada `admin_menus.php` digabung; daftar transisi status admin disederhanakan; query pengurangan stok dipersiapkan sekali di luar loop.
- **Backend — profil kantin**: `kantin_profile_fetch()` dipakai ulang oleh endpoint baca dan ubah profil.
- **Backend — web push**: pembuatan kunci EC duplikat dihapus dan divalidasi lewat satu jalur; pengecekan panjang subscription yang redundan dibuang.
- **Frontend — API client**: `api()` memakai parameter default; ditambahkan `mapUser()` dan `mapProfile()` untuk normalisasi data sesi dan profil.
- **Frontend — app**: menambahkan helper `lastOrder()`, `formErrorHtml()`, `authFormValid()`, dan `detachPushSubscription()`; peta route, tab, judul appbar, serta tampilan dipindahkan ke luar fungsi render; parameter default ditambahkan pada `goToRoute()` dan `navigate()`.
- **Service worker**: parsing payload push disederhanakan.

### Fixed

- Sweep pesanan kedaluwarsa tidak lagi melempar fatal error ketika koneksi database gagal; akuisisi koneksi dipindahkan ke dalam blok `try` sehingga kegagalan tetap dicatat ke log dan request lain dapat berjalan.
