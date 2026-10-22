# Frontend — Kantin Kampus (HTML/CSS/JS polos)

Satu HTML per layar, nomor HP frame ala prototipe. Tanpa framework, tanpa build step.

## Struktur

```
frontend/
├── index.html        redirect sesuai sesi (masuk.html / menu.html)
├── masuk.html        login + daftar (mahasiswa & admin)
├── menu.html         daftar menu + keranjang
├── keranjang.html
├── pembayaran.html
├── sukses.html       pesanan dibuat + tenggat bayar 10 menit
├── pesanan.html      riwayat pesanan
├── lacak.html        pelacakan status pesanan
├── akun-mahasiswa.html
├── akun-admin.html
├── notifikasi.html, bantuan.html, tentang.html   (sub-halaman bersama)
├── profil-kantin.html, jam-operasional.html      (khusus admin)
├── admin-pesanan.html    papan pesanan masuk per slot jam
├── admin-stok.html       kelola stok menu
├── assets/           gambar menu
├── css/styles.css    design tokens + layout
└── js/
    ├── api.js    lapisan HTTP: API_BASE, api(), auth (JWT), mapMenu/mapOrder
    └── app.js    render semua layar + aksi (script ini dimuat SETELAH api.js)
```

## Menjalankan

```bash
# backend dulu (lihat ../backend/README.md)
cd ../backend && php migrate.php && php -S 127.0.0.1:5000 -t . api/index.php

# frontend
cd frontend
php -S 127.0.0.1:8080
```

Buka `http://127.0.0.1:8080/`. Akun awal (`../backend/schema.sql`), password `password123`:

- `admin@kantin.com` (admin)
- `user@kantin.com` (mahasiswa)

### Laragon / Apache

Taruh repo di www (atau junction), Start All, lalu:

- frontend: `http://kantin.test/frontend/`
- API: `http://kantin.test/backend/api/health`

`API_BASE` mendeteksi sendiri: kalau URL halaman berada di bawah `/frontend/`, basisnya
`<origin>/backend/api`. Untuk tata letak lain, set manual:

```js
localStorage.setItem('kantin:api', 'http://host/backend/api')
```

Alamat API bisa diganti lewat `localStorage.setItem('kantin:api', 'http://host:port/api')`.
