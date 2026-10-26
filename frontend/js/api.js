/* Lapisan HTTP: dipakai app.js. Muat file ini SEBELUM js/app.js di tiap HTML. */
/* ---------- API (backend PHP + MySQL) ---------- */
const API_BASE = localStorage.getItem('kantin:api') || (function () {
  // Disajikan lewat Apache/Laragon: <root>/frontend/x.html -> <root>/backend/api
  if (/\/frontend\//.test(location.pathname)) {
    return location.origin + location.pathname.replace(/\/frontend\/.*$/, '/backend/api');
  }
  // ponytail: mode php -S (frontend di port 8080, API di 5000).
  // Tata letak lain: localStorage.setItem('kantin:api', '<origin>/backend/api')
  return 'http://localhost:5000/api';
})();
const AUTH_KEY = 'kantin:token';

async function api(path, opts = {}) {
  const isForm = typeof FormData !== 'undefined' && opts.body instanceof FormData;
  const headers = {};
  if (!isForm) headers['Content-Type'] = 'application/json';
  const token = localStorage.getItem(AUTH_KEY);
  if (token) headers.Authorization = 'Bearer ' + token;

  const res = await fetch(API_BASE + path, {
    method: opts.method || 'GET',
    headers: headers,
    body: opts.body ? (isForm ? opts.body : JSON.stringify(opts.body)) : undefined
  });

  let data = null;
  try { data = await res.json(); } catch (e) { /* body kosong */ }
  if (!res.ok) {
    const err = new Error((data && data.error) || ('Permintaan gagal (' + res.status + ')'));
    err.status = res.status;
    // Sesi hilang/kedaluwarsa: jangan tampilkan toast teknis, kembalikan ke login.
    if (res.status === 401 && !/masuk\.html$/.test(location.pathname)) {
      auth.clear();
      location.replace('masuk.html');
    }
    throw err;
  }
  return data;
}

const auth = {
  token: () => localStorage.getItem(AUTH_KEY),
  save: (token, user) => { localStorage.setItem(AUTH_KEY, token); sessionStorage.setItem('kantin:user', JSON.stringify(user)); },
  clear: () => { localStorage.removeItem(AUTH_KEY); sessionStorage.removeItem('kantin:user'); }
};

/* Gambar dari DB ('nasi_goreng.jpg') dicocokkan ke assets/ ('nasi-goreng.jpg'),
   dan jatuh ke placeholder bila file tidak ada. */
const IMG_FALLBACK = 'data:image/svg+xml;utf8,'
  + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><rect width="96" height="96" fill="#eceae4"/>'
    + '<path d="M34 44h28M34 54h28" stroke="#b9b4a8" stroke-width="3" stroke-linecap="round"/></svg>');

function menuImage(url) {
  if (!url) return IMG_FALLBACK;
  const raw = String(url).trim();
  if (/^https?:\/\//i.test(raw)) return raw;
  const clean = raw.replace(/^\/+/, '');
  if (/^uploads\//i.test(clean)) {
    return API_BASE + '/uploads/' + encodeURIComponent(clean.split('/').pop());
  }
  const name = clean.split('/').pop().toLowerCase().replace(/_/g, '-');
  // ponytail: alias untuk 3 seed yang fotonya belum ada; ganti begitu aset asli ditambahkan.
  const alias = { 'mie-ayam.jpg': 'mie-goreng.jpg', 'ayam-bakar.jpg': 'sate-ayam.jpg', 'es-jeruk.jpg': 'es-teh.jpg' };
  return 'assets/' + (alias[name] || name);
}

function mapUser(user) {
  return {
    id: String(user.id),
    nama: user.nama,
    nim: user.nim || '',
    email: user.email,
    role: user.role
  };
}

function mapProfile(row) {
  return {
    nama: row.nama || '',
    lokasi: row.lokasi || '',
    kontak: row.kontak || '',
    deskripsi: row.deskripsi || ''
  };
}

function mapMenu(row) {
  return {
    id: String(row.id),
    name: row.nama,
    desc: row.deskripsi || '',
    cat: row.kategori || 'Lainnya',
    price: Number(row.harga),
    stock: Number(row.stok),
    active: row.is_active === undefined ? true : Number(row.is_active) === 1,
    imageUrl: row.image_url || '',
    img: menuImage(row.image_url),
    w: 960,
    h: 720
  };
}

function mapHours(rows) {
  const names = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
  return (rows || []).map(r => {
    const day = Number(r.day_of_week);
    return {
      day: day,
      name: r.hari || names[day - 1] || '',
      open: !!r.is_open,
      openAt: String(r.open_time || '07:00').slice(0, 5),
      closeAt: String(r.close_time || '16:00').slice(0, 5)
    };
  });
}

function mapOrder(row) {
  const lines = (row.items || []).map(it => ({
    name: it.name !== undefined ? it.name : it.nama,
    qty: Number(it.qty !== undefined ? it.qty : it.quantity),
    price: Number(it.price !== undefined ? it.price : it.price_at_order)
  }));
  return {
    id: String(row.id),
    code: row.order_code,
    buyerName: row.user_nama || (state.user ? state.user.nama : ''),
    nim: row.user_nim || (state.user ? state.user.nim : '') || '-',
    items: lines,
    total: Number(row.total_price),
    pickup: String(row.pickup_at || '').slice(11, 16),
    pickupDate: String(row.pickup_at || '').slice(0, 10),
    note: row.catatan || '',
    status: row.status,
    createdAt: row.created_at || '',
    time: String(row.created_at || '').slice(11, 16),
    expiresAt: row.expires_at || null
  };
}
