# Laporan Verifikasi Fungsional — Pre-Order Kantin Kampus

> **Tanggal:** 2026-10-24
> **Lingkup:** API backend dan alur UI mahasiswa + admin untuk seluruh task v1 (T-01 s.d. T-17).
> **Status:** ⚠️ **Lolos dengan catatan** — 107/107 pemeriksaan API lulus, alur UI utama berjalan,
> ditemukan 1 bug fungsional sedang dan 3 catatan rendah.

---

## 1. Lingkungan Pengujian

| Komponen | Detail |
|---|---|
| Database | `kantin_test` (terpisah dari DB development `kantin`) |
| Backend API | `http://127.0.0.1:5000/api` — tanpa Midtrans server key |
| Backend webhook | `http://127.0.0.1:5001/api` — `MIDTRANS_SERVER_KEY=test-server-key` |
| Frontend | `http://127.0.0.1:8080` |
| Akun seed | `admin@kantin.com` / `user@kantin.com`, password `password123` |
| Alat | Node.js 24 (107 assertion API) + browser in-app (alur UI) |
| Isolasi | DB development tidak disentuh; rate-limit direset di akhir pengujian |

Skenario webhook memakai server kedua di port 5001 agar signature SHA-512 dapat diuji dengan
server key tiruan tanpa memanggil Midtrans sungguhan. Pembuatan order tetap dilakukan di port
5000 supaya memakai token dummy.

---

## 2. Hasil Ringkas

| Area | Hasil |
|---|---|
| Health check & CORS preflight | ✅ Lulus |
| Autentikasi, register, JWT, role guard | ✅ 15/15 lulus |
| Katalog menu publik & admin | ✅ Lulus |
| Validasi pembuatan pesanan | ✅ 9/9 lulus |
| Perhitungan total, kode order, expiry, pemotongan stok | ✅ Lulus |
| Riwayat & detail pesanan (ownership) | ✅ Lulus |
| Batas 3 pesanan pending | ✅ Lulus |
| Daftar pesanan admin + filter tanggal | ✅ Lulus |
| Transisi status & pengembalian stok | ✅ Lulus |
| CRUD menu, stok, aktif/nonaktif | ✅ 15/15 lulus |
| Webhook: signature, gross amount, idempotensi, fraud, order tak dikenal | ✅ 13/13 lulus |
| Webhook: expire, late settlement, stok tidak cukup | ✅ 8/8 lulus |
| Auto-cancel via sweep + pengembalian stok | ✅ Lulus |
| Rate limit auth (5x/10 menit) & order (20x/menit) | ✅ Lulus |
| UI mahasiswa: login → menu → keranjang → pickup → bayar → sukses → lacak → riwayat | ✅ Berjalan |
| UI admin: login → papan pesanan → status → kelola stok/aktif menu → akun → logout | ✅ Berjalan |

**Total assertion API:** 107 lulus, 0 gagal.

Alur UI yang diverifikasi langsung:

1. Login mahasiswa, pencarian menu (`teh` → Es Teh Manis), tambah ke keranjang.
2. Stepper jumlah 1 → 2, subtotal berubah menjadi Rp 60.000.
3. Pilih slot pickup, isi catatan, lanjut ke pembayaran, bayar (mode dummy) → halaman sukses
   dengan order `ORD-4531267D`.
4. Halaman lacak menampilkan timeline dan rincian; halaman pesanan menampilkan riwayat.
5. Keranjang kosong setelah order dibuat; stok Ayam Bakar berkurang 2.
6. Login admin, papan pesanan menampilkan order hari ini, transisi `pending → paid → ready → completed`
   berjalan, toast status muncul.
7. Kelola stok (+1/−1) dan aktif/nonaktif menu berjalan; menu nonaktif bertahan setelah reload.
8. Akun mahasiswa/admin dan logout berjalan; tidak ada error console atau request gagal (semua 200/204).

---

## 3. Temuan

### 3.1 [Sedang] Slot pickup < 5 menit tetap bisa dipilih, lalu ditolak backend

- **Lokasi:** `frontend/js/app.js:114-115` berbanding `backend/controllers/orders.php:36`.
- **Deskripsi:** UI hanya menonaktifkan slot dengan `diff <= 0` ("Sudah lewat"), sehingga slot
  yang tinggal 1–4 menit masih dapat dipilih. Backend mewajibkan pickup minimal 5 menit ke depan
  (`$pickupTs < time() + 5 * 60`).
- **Reproduksi:** pukul 12:57 pilih slot 13:00 (±2 menit) → klik **Bayar Sekarang** → toast
  "Waktu pengambilan sudah lewat"; order tidak dibuat.
- **Dampak:** checkout gagal pada jendela 5 menit sebelum setiap slot, padahal UI menyatakan slot
  tersedia. Pengguna baru mengetahui setelah menekan bayar.
- **Saran:** samakan aturan UI dengan backend, misalnya `disabled: diff < 5` dan label
  "Minimal 5 menit dari sekarang". Alternatifnya, backend memakai toleransi yang sama dengan UI,
  tetapi memperketat UI lebih aman karena aturan 5 menit sudah tertulis di T-10.

### 3.2 [Rendah] Admin dapat mengubah `pending → paid`, sedangkan TECH-SPEC menyebut webhook

- **Lokasi:** `backend/controllers/admin_orders.php:71-74`, tombol **Tandai Dibayar** di
  `frontend/js/app.js:772` (handler `frontend/js/app.js:1153`), dan tabel transisi di
  `.agents/2-TECH-SPEC.md:270`.
- **Deskripsi:** T-14 dan UI memang mengizinkan admin menandai pesanan sudah dibayar (berguna untuk
  pembayaran tunai), tetapi TECH-SPEC menyatakan `pending → paid` hanya terjadi lewat webhook
  Midtrans. Perilaku ini terverifikasi berfungsi (200).
- **Dampak:** bukan kegagalan fungsi, tetapi dokumen dan implementasi tidak konsisten; status
  pembayaran dapat berubah tanpa bukti transaksi gateway.
- **Saran:** putuskan satu kebijakan. Jika konfirmasi manual dipertahankan, tambahkan kolom/catatan
  "dikonfirmasi manual oleh admin" di TECH-SPEC dan pertimbangkan audit log. Jika tidak, hapus
  transisi `pending → paid` dari admin dan sembunyikan tombolnya.

### 3.3 [Rendah] Hitungan "perlu disiapkan" di header tidak mengikuti filter tanggal

- **Lokasi:** `frontend/js/app.js:329-333` berbanding stat card `frontend/js/app.js:825`.
- **Deskripsi:** header menghitung seluruh order `paid` tanpa melihat tanggal, sedangkan stat card
  dan papan memakai filter tanggal aktif. Saat pengujian, header menampilkan "2 perlu disiapkan"
  (order besok) sementara kartu "Perlu disiapkan" hari ini menampilkan 0.
- **Dampak:** admin bisa mengira ada pesanan hari ini yang belum disiapkan.
- **Saran:** gunakan sumber data yang sudah terfilter (`scoped`) untuk header, atau beri label
  "total semua tanggal" bila memang disengaja.

### 3.4 [Rendah] Toast "dinotifikasi" padahal belum ada mekanisme notifikasi

- **Lokasi:** `frontend/js/app.js:1160`.
- **Deskripsi:** saat admin menandai pesanan `ready`, toast berbunyi
  "<nama> dinotifikasi: pesanan siap." Padahal notifikasi push/email belum diimplementasikan
  (ditunda ke v2) dan halaman notifikasi hanya berisi preferensi. Mahasiswa hanya melihat perubahan
  lewat polling 30 detik bila halaman tracking terbuka.
- **Dampak:** informasi menyesatkan admin.
- **Saran:** ubah teks menjadi "Pesanan ditandai siap diambil" sampai notifikasi benar-benar ada.

---

## 4. Perilaku yang Terverifikasi Sesuai Dokumen

- Harga, stok, dan total dihitung di server; `price_at_order` disimpan sebagai snapshot.
- Anti-oversell berjalan: stok dipotong dalam transaksi, order dengan stok kurang ditolak.
- `order_code` berformat `ORD-` + 8 hex; `expires_at` ±10 menit.
- Batas 3 pesanan pending per user menghasilkan 409 pada pesanan ke-4.
- Pembatalan mengembalikan stok tepat satu kali; transisi ganda ditolak.
- Sweep membatalkan order kedaluwarsa dan mengembalikan stok tanpa cron.
- Webhook menolak signature salah (403), gross amount tidak cocok (400), dan server tanpa key (503);
  settlement idempoten; `expire` mengembalikan stok; late settlement mengaktifkan kembali pesanan
  atau ditolak 409 bila stok tidak cukup.
- Otorisasi: endpoint admin menolak mahasiswa (403) dan tanpa token (401); detail order hanya
  dapat diakses pemiliknya.
- Rate limit auth dan order berfungsi (429 setelah batas), termasuk mengunci login yang benar
  selama jendela aktif.
- Mode demo tanpa Midtrans: token dummy, frontend melewati Snap dan menampilkan halaman sukses.

---

## 5. Belum Diuji / Risiko Tersisa

- Uji beban dan konkurensi nyata (dua checkout bersamaan untuk stok terakhir).
- Audit responsif 320/768/1024 px, Lighthouse, dan aksesibilitas menyeluruh.
- Transaksi Midtrans sandbox sungguhan (pengujian memakai server key tiruan).
- Dark mode dan push notification (memang ditunda ke v2).
- Reset password via email (v2).

---

## 6. Artefak & Catatan

- Database `kantin_test` sengaja dipertahankan berisi data pengujian agar temuan dapat diperiksa.
  Database ini terpisah dan aman dihapus kapan saja; DB development tidak berubah.
- Server uji di port 5000/5001/8080 dihentikan setelah pengujian.
- File rate-limit di `backend/storage/ratelimit` sudah dibersihkan.
- Skrip pengujian API berada di luar repo (`%TEMP%/kantin-functional-test.js`) dan tidak di-commit.
- Tidak ada perubahan kode aplikasi yang dilakukan selama pengujian; working tree berisi perubahan
  yang sudah ada sebelumnya.
