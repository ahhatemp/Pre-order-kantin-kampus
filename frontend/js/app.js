/* ============================================================
   Kantin Kampus — Pre-Order (frontend + backend PHP/MySQL)
   Shared engine: one HTML file per screen, state persisted
   in sessionStorage so the flow survives page-to-page navigation.
   ============================================================ */
'use strict';

/* ---------- Page <-> file map ---------- */
const PAGES = {
  auth: 'masuk.html',
  menu: 'menu.html',
  cart: 'keranjang.html',
  payment: 'pembayaran.html',
  success: 'sukses.html',
  orders: 'pesanan.html',
  tracking: 'lacak.html',
  accountStudent: 'akun-mahasiswa.html',
  accountAdmin: 'akun-admin.html',
  notifikasi: 'notifikasi.html',
  bantuan: 'bantuan.html',
  tentang: 'tentang.html',
  profilKantin: 'profil-kantin.html',
  jamOperasional: 'jam-operasional.html',
  board: 'admin-pesanan.html',
  stock: 'admin-stok.html'
};

/* Sub-pages reachable from the two profile screens, grouped per role so the
   Akun tab stays highlighted while the user is inside them. */
const STUDENT_ACCOUNT_SUB = ['notifikasi', 'bantuan', 'tentang'];
const ADMIN_ACCOUNT_SUB = ['notifikasi', 'bantuan', 'profilKantin', 'jamOperasional'];

const ADMIN_ONLY = ['board', 'stock', 'accountAdmin', 'profilKantin', 'jamOperasional'];
const STUDENT_PAGES = ['menu', 'cart', 'payment', 'success', 'orders', 'tracking', 'accountStudent'];
const REFRESH_PAGES = ['menu', 'cart', 'payment', 'success', 'orders', 'tracking', 'board', 'stock'];
const POLL_PAGES = ['menu', 'stock', 'board', 'orders', 'tracking'];

let PAGE = (document.body && document.body.dataset.page) || 'menu';

/* ---------- Icons ---------- */
const ICONS = {
  chevronLeft: '<path d="m15 18-6-6 6-6"/>',
  chevronRight: '<path d="m9 18 6-6-6-6"/>',
  search: '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  minus: '<path d="M5 12h14"/>',
  cart: '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',
  utensils: '<path d="M3 2v7c0 1.1.9 2 2 2a2 2 0 0 0 2-2V2"/><path d="M5 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/>',
  receipt: '<path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8M8 11h8M8 15h5"/>',
  user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  store: '<path d="m2 7 1.5-4h17L22 7"/><path d="M2 7h20v3a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0Z"/><path d="M4 12v8a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-8"/>',
  layout: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
  box: '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
  clock: '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
  check: '<path d="M20 6 9 17l-5-5"/>',
  checkCircle: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
  x: '<path d="M18 6 6 18M6 6l12 12"/>',
  trash: '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
  pencil: '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
  camera: '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
  upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>',
  alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
  bell: '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
  wallet: '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2"/><path d="M21 12H8a2 2 0 0 0-2 2v0a2 2 0 0 0 2 2h13a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1Z"/>',
  card: '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
  qr: '<rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/><rect width="5" height="5" x="3" y="16" rx="1"/><path d="M21 16h-3a2 2 0 0 0-2 2v3M21 21v.01M12 7v3a2 2 0 0 1-2 2H7M3 12h.01M12 3h.01M12 16v.01M16 12h1M21 12v.01M12 21v-1"/>',
  logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
  bookmark: '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>',
  info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
  arrowRight: '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
  key: '<path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777Zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>'
};

function icon(name, size) {
  const s = size || 20;
  return '<svg width="' + s + '" height="' + s + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || '') + '</svg>';
}

const fmt = n => 'Rp ' + n.toLocaleString('id-ID');

const STATUS = {
  pending:   { label: 'Menunggu Pembayaran', short: 'Menunggu',   color: 'var(--status-pending)',   icon: 'clock' },
  paid:      { label: 'Sudah Dibayar',       short: 'Dibayar',    color: 'var(--status-paid)',      icon: 'check' },
  ready:     { label: 'Siap Diambil',        short: 'Siap',       color: 'var(--status-ready)',     icon: 'box' },
  completed: { label: 'Selesai',             short: 'Selesai',    color: 'var(--status-completed)', icon: 'checkCircle' },
  cancelled: { label: 'Dibatalkan',          short: 'Batal',      color: 'var(--status-cancelled)', icon: 'x' }
};
const STATUS_ORDER = ['pending', 'paid', 'ready', 'completed'];

function statusBadge(status) {
  const m = STATUS[status];
  return '<span class="badge" style="color:' + m.color + ';background:color-mix(in srgb,' + m.color + ' 14%,transparent)">'
    + icon(m.icon, 13) + '<span>' + m.label + '</span></span>';
}

/* Kategori diambil dari data menu di server. */
function categories() {
  const set = [];
  state.menu.forEach(i => { if (i.cat && set.indexOf(i.cat) === -1) set.push(i.cat); });
  return ['Semua'].concat(set);
}

const SLOT_TIMES = ['12:00', '13:00', '14:00', '15:00', '16:00'];

function clockNow(d) {
  d = d || new Date();
  return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
}

function slotMinutes(t) {
  return Number(t.slice(0, 2)) * 60 + Number(t.slice(3));
}

/* Slot hari ini; kalau semua sudah lewat, tampilkan slot besok. */
function pickupSlots(now) {
  now = now || new Date();
  const nowMin = now.getHours() * 60 + now.getMinutes();
  const tomorrow = SLOT_TIMES.every(t => slotMinutes(t) <= nowMin);
  return SLOT_TIMES.map(t => {
    if (tomorrow) return { t: t, wait: 'Besok', disabled: false };
    const diff = slotMinutes(t) - nowMin;
    return { t: t, wait: diff <= 0 ? 'Sudah lewat' : '±' + diff + ' menit', disabled: diff <= 0 };
  });
}

function nextPickupHint() {
  const next = pickupSlots().find(s => !s.disabled);
  if (!next) return '-';
  return next.wait === 'Besok' ? next.t + ' besok' : next.t;
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

function dateOnly(d) {
  return d.getFullYear() + '-'
    + String(d.getMonth() + 1).padStart(2, '0') + '-'
    + String(d.getDate()).padStart(2, '0');
}

function fmtDate(ymd) {
  const parts = String(ymd).split('-');
  return Number(parts[2]) + ' ' + (MONTHS[Number(parts[1]) - 1] || parts[1]);
}

/* Tanggal pickup harus selalu terlihat supaya order besok tidak dikira hari ini. */
function pickupLabel(o) {
  if (!o) return '-';
  const date = o.pickupDate || o.date || '';
  const time = o.pickup || o.time || '';
  if (!date) return time || '-';
  const today = dateOnly(new Date());
  const tomorrow = dateOnly(new Date(Date.now() + 86400000));
  const day = date === today ? 'Hari ini'
    : date === tomorrow ? 'Besok'
    : fmtDate(date);
  return day + ' ' + time;
}

function plannedPickup() {
  const now = new Date();
  if (state.pickup && slotMinutes(state.pickup) <= now.getHours() * 60 + now.getMinutes()) {
    now.setDate(now.getDate() + 1);
  }
  return { date: dateOnly(now), time: state.pickup || '' };
}

/* ---------- Store (persisted per-tab) ---------- */
const STORE_KEY = 'kantin-kampus:v2';

function defaultState() {
  return {
    route: PAGE,
    role: null,
    authRole: 'student',
    authMode: 'login',
    formError: null,
    loading: false,
    user: null,
    menu: [],
    cart: {},
    pickup: null,
    note: null,
    payMethod: 'ewallet',
    lastOrderId: null,
    orders: [],
    boardFilter: 'all',
    category: 'Semua',
    search: '',
    flash: null,
    menuForm: null
  };
}

function persist() {
  /* Token tidak ikut di sini: disimpan terpisah di localStorage oleh auth.save(). */
  const snap = Object.assign({}, state);
  delete snap.loading;
  delete snap.menuForm;
  try { sessionStorage.setItem(STORE_KEY, JSON.stringify(snap)); } catch (e) { /* ignore */ }
}

let state = defaultState();
(function restore() {
  try {
    const raw = sessionStorage.getItem(STORE_KEY);
    if (raw) state = Object.assign(defaultState(), JSON.parse(raw));
  } catch (e) { /* fresh */ }
  try {
    const raw = sessionStorage.getItem('kantin:user');
    if (raw) state.user = JSON.parse(raw);
  } catch (e) { /* no user */ }
})();
state.route = PAGE;
state.loading = false;

/* ---------- Helpers ---------- */
const $ = id => document.getElementById(id);
const cartCount = () => Object.values(state.cart).reduce((a, b) => a + b, 0);
const cartTotal = () => Object.entries(state.cart).reduce((s, [id, q]) => {
  const it = item(id);
  return it ? s + it.price * q : s;
}, 0);
const item = id => state.menu.find(i => i.id === String(id));
const stockLeft = id => {
  const it = item(id);
  return it ? it.stock - (state.cart[String(id)] || 0) : 0;
};
/* Backend sudah mengirim pesanan terbaru duluan. */
const myOrders = () => state.orders;
const orderById = id => state.orders.find(o => o.id === String(id));
function esc(s) { return String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }

function toast(msg, ic) {
  const el = $('toast');
  el.innerHTML = icon(ic || 'checkCircle', 18) + '<span>' + esc(msg) + '</span>';
  el.setAttribute('data-show', 'true');
  clearTimeout(toast._t);
  toast._t = setTimeout(() => el.setAttribute('data-show', 'false'), 2400);
}

function showFlash() {
  if (!state.flash) return;
  toast(state.flash.msg, state.flash.ic);
  state.flash = null;
  persist();
}

/* ---------- Navigation ---------- */
const LEAVE_MS = 160;
const reducedMotion = () => window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const TITLES = {
  auth: 'Masuk — Kantin Kampus',
  menu: 'Menu — Kantin Kampus',
  cart: 'Keranjang — Kantin Kampus',
  payment: 'Pembayaran — Kantin Kampus',
  success: 'Pesanan Dibuat — Kantin Kampus',
  orders: 'Pesanan Saya — Kantin Kampus',
  tracking: 'Lacak Pesanan — Kantin Kampus',
  accountStudent: 'Akun — Kantin Kampus',
  accountAdmin: 'Akun Admin — Kantin Kampus',
  notifikasi: 'Preferensi Notifikasi — Kantin Kampus',
  bantuan: 'Bantuan — Kantin Kampus',
  tentang: 'Tentang Kantin — Kantin Kampus',
  profilKantin: 'Profil Kantin — Kantin Kampus',
  jamOperasional: 'Jam Operasional — Kantin Kampus',
  board: 'Pesanan Masuk — Admin Kantin',
  stock: 'Menu & Stok — Admin Kantin'
};

const PAGE_BY_FILE = {};
Object.keys(PAGES).forEach(k => { PAGE_BY_FILE[PAGES[k]] = k; });

function roleHome() { return state.role === 'admin' ? 'board' : 'menu'; }

function pageFromPath(path) {
  const file = String(path || '').split('?')[0].split('/').pop();
  return PAGE_BY_FILE[file] || roleHome();
}

/* Halaman yang butuh role tertentu dialihkan tanpa memuat ulang dokumen. */
function guardPage(page) {
  if (page === 'auth') return state.role ? roleHome() : 'auth';
  if (!state.role) return 'auth';
  if (ADMIN_ONLY.includes(page) && state.role !== 'admin') return roleHome();
  if (STUDENT_PAGES.includes(page) && state.role === 'admin') return 'board';
  return page;
}

function backFallback() {
  if (PAGE === 'payment') return 'cart';
  if (PAGE === 'success' || PAGE === 'tracking') return 'orders';
  if (ADMIN_ACCOUNT_SUB.includes(PAGE)) return 'accountAdmin';
  if (STUDENT_ACCOUNT_SUB.includes(PAGE)) return 'accountStudent';
  if (PAGE === 'stock') return 'board';
  return roleHome();
}

let navSeq = 0;

/* Modal/kamera dilepas saat pindah layar supaya tidak menempel di halaman baru. */
function resetOverlays() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(t => t.stop());
    cameraStream = null;
  }
  const cam = $('camera');
  if (cam) resetCamera(cam);
  releaseMenuPreview();
  const modal = $('modal');
  if (modal) resetMenuForm(modal);
  state.menuForm = null;
}

/* Navigasi antar layar tanpa memuat ulang dokumen: appbar + tabbar tetap. */
async function goToRoute(page, opts) {
  opts = opts || {};
  const target = guardPage(page);
  const replace = !!opts.replace || target !== page;
  const same = target === PAGE;
  const seq = ++navSeq;

  resetOverlays();
  PAGE = target;
  state.route = target;
  state.loading = false;
  document.body.dataset.page = target;
  document.title = TITLES[target] || TITLES.menu;

  if (opts.history !== 'none' && (replace || !same)) {
    const depth = (history.state && Number(history.state.depth)) || 0;
    const entry = { page: target, depth: replace ? depth : depth + 1 };
    if (replace) history.replaceState(entry, '', PAGES[target]);
    else history.pushState(entry, '', PAGES[target]);
  }

  const needs = pageNeeds(target);
  const cached = (!needs.menu || dataLoaded.menu || state.menu.length)
    && (!needs.orders || dataLoaded.orders || state.orders.length);
  const before = cached ? dataSignature(target) : null;

  state.loading = !cached;
  renderView();
  if (cached) enterScreen();
  const screen = $('screen');
  if (screen) screen.scrollTop = 0;

  try {
    await loadData();
  } catch (e) {
    if (seq === navSeq) toast(e.message, 'alert');
  }
  if (seq !== navSeq) return;
  state.loading = false;

  if (!cached || dataSignature(target) !== before) {
    if (screen) screen.classList.remove('is-entering');
    renderView();
    if (screen) screen.scrollTop = 0;
    if (!cached) enterScreen();
  }
  showFlash();
  schedulePolling();
}

function navigate(page, opts) {
  opts = opts || {};
  if (opts.params) Object.assign(state, opts.params);
  if (opts.flash) state.flash = opts.flash;
  goToRoute(page, opts);
}

function back() {
  if (history.state && Number(history.state.depth) > 0) { history.back(); return; }
  navigate(backFallback(), { replace: true });
}

/* ---------- Appbar / Tabbar ---------- */
const STUDENT_TABS = [
  { route: 'menu', label: 'Menu', icon: 'utensils' },
  { route: 'cart', label: 'Keranjang', icon: 'cart' },
  { route: 'orders', label: 'Pesanan', icon: 'receipt' },
  { route: 'accountStudent', label: 'Akun', icon: 'user' }
];
const ADMIN_TABS = [
  { route: 'board', label: 'Pesanan', icon: 'layout' },
  { route: 'stock', label: 'Menu & Stok', icon: 'box' },
  { route: 'accountAdmin', label: 'Akun', icon: 'user' }
];

function tabsFor() {
  if (!state.role) return [];
  return state.role === 'admin' ? ADMIN_TABS : STUDENT_TABS;
}

function renderAppbar() {
  const bar = $('appbar');
  const r = state.route;
  if (r === 'auth') { bar.innerHTML = ''; bar.className = 'appbar'; bar.hidden = true; return; }
  bar.hidden = false;

  const stop = '<button class="iconbtn" data-action="back" aria-label="Kembali">' + icon('chevronLeft', 22) + '</button>';
  let html = '';

  if (r === 'menu') {
    html = '<div class="od-fill"><div class="appbar__sub">Kantin Kampus</div>'
      + '<div class="appbar__title">Halo, ' + esc(state.user ? state.user.nama.split(' ')[0] : 'Mahasiswa') + '</div></div>'
      + '<button class="iconbtn iconbtn--end" data-action="go" data-route="cart" aria-label="Keranjang">' + icon('cart', 22) + '</button>';
  } else if (r === 'cart') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Keranjang</div></div>';
  } else if (r === 'payment') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Pembayaran</div></div>';
  } else if (r === 'success') {
    html = '<div class="od-fill"><div class="appbar__title">Pesanan Dibuat</div></div>';
  } else if (r === 'orders') {
    html = '<div class="od-fill"><div class="appbar__title">Pesanan Saya</div></div>';
  } else if (r === 'tracking') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Lacak Pesanan</div></div>';
  } else if (r === 'accountStudent' || r === 'accountAdmin') {
    html = '<div class="od-fill"><div class="appbar__title">Akun</div></div>';
  } else if (r === 'notifikasi') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Preferensi Notifikasi</div></div>';
  } else if (r === 'bantuan') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Bantuan</div></div>';
  } else if (r === 'tentang') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Tentang Kantin</div></div>';
  } else if (r === 'profilKantin') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Profil Kantin</div></div>';
  } else if (r === 'jamOperasional') {
    html = stop + '<div class="od-fill"><div class="appbar__title">Jam Operasional</div></div>';
  } else if (r === 'board') {
    const pending = state.orders.filter(o => o.status === 'paid').length;
    html = '<div class="od-fill"><div class="appbar__sub">Admin · ' + clockNow() + '</div>'
      + '<div class="appbar__title">Pesanan Masuk</div></div>'
      + '<span class="badge" style="color:var(--status-paid);background:color-mix(in srgb,var(--status-paid) 14%,transparent)">'
      + icon('bell', 13) + '<span>' + pending + ' perlu disiapkan</span></span>';
  } else if (r === 'stock') {
    html = '<div class="od-fill"><div class="appbar__title">Menu &amp; Stok</div></div>'
      + '<button class="iconbtn iconbtn--end" data-action="new-menu" aria-label="Tambah menu">' + icon('plus', 22) + '</button>';
  }
  bar.className = 'appbar appbar--bordered';
  bar.innerHTML = html;
}

function renderTabbar() {
  const bar = $('tabbar');
  const tabs = tabsFor();
  if (!tabs.length) { bar.innerHTML = ''; bar.hidden = true; return; }
  bar.hidden = false;
  const count = cartCount();
  const prevCount = renderTabbar._count;
  renderTabbar._count = count;
  bar.innerHTML = tabs.map(t => {
    const active = t.route === state.route
      || (t.route === 'cart' && ['payment', 'success'].includes(state.route))
      || (t.route === 'orders' && state.route === 'tracking')
      || (t.route === 'accountStudent' && STUDENT_ACCOUNT_SUB.includes(state.route))
      || (t.route === 'accountAdmin' && ADMIN_ACCOUNT_SUB.includes(state.route));
    const pop = count > prevCount && prevCount !== undefined;
    const badge = (t.route === 'cart' && count) ? '<span class="tab__badge' + (pop ? ' tab__badge--pop' : '') + '">' + count + '</span>' : '';
    return '<a class="tab" href="' + PAGES[t.route] + '" data-action="go" data-route="' + t.route + '" aria-current="' + (active ? 'page' : 'false') + '">'
      + badge + icon(t.icon, 22) + '<span>' + t.label + '</span></a>';
  }).join('');
}

function renderCartbar() {
  const el = $('cartbar');
  if (!el) return;
  const show = state.route === 'menu' && cartCount() > 0;
  const key = show ? cartCount() + ':' + cartTotal() : '';
  if (el.dataset.key === key) return;
  el.dataset.key = key;
  if (!show) { el.innerHTML = ''; return; }
  const fresh = !el.firstElementChild;
  el.innerHTML = '<div class="cartbar"><div class="cartbar__row">'
    + '<div class="od-stack" style="--od-gap:2px"><span class="cartbar__label">' + cartCount() + ' item di keranjang</span>'
    + '<span class="cartbar__total od-nowrap">' + fmt(cartTotal()) + '</span></div>'
    + '<button class="btn btn--primary" data-action="go" data-route="cart">Lihat Keranjang' + icon('arrowRight', 18) + '</button>'
    + '</div></div>';
  el.setAttribute('aria-live', 'polite');
  if (fresh) el.firstElementChild.classList.add('cartbar--enter');
}

/* ---------- Views ---------- */
function field(id, label, type, placeholder, value, required) {
  return '<div class="field">'
    + '<label class="field__label" for="f-' + id + '">' + label + (required ? ' <span class="field__req" aria-hidden="true">*</span>' : '') + '</label>'
    + '<input class="input" id="f-' + id + '" name="' + id + '" type="' + type + '" placeholder="' + placeholder + '"'
    + (value ? ' value="' + esc(value) + '"' : '')
    + (required ? ' required aria-required="true"' : '') + ' autocomplete="off" />'
    + '</div>';
}

function viewAuth() {
  const isAdmin = state.authRole === 'admin';
  const mode = state.authMode;
  let form = '';

  if (isAdmin) {
    form = field('adminid', 'Email admin', 'email', 'admin@kantin.com', '', true)
      + field('password', 'Password', 'password', '••••••••', '', true);
  } else if (mode === 'login') {
    form = field('login', 'Email terdaftar', 'email', 'budi@kampus.ac.id', '', true)
      + field('password', 'Password', 'password', '••••••••', '', true)
      + '<div style="display:flex;justify-content:flex-end"><button class="auth__link" data-action="set-auth-mode" data-mode="forgot">Lupa password?</button></div>';
  } else if (mode === 'register') {
    form = field('nama', 'Nama lengkap', 'text', 'Budi Santoso', '', true)
      + field('nim', 'NIM', 'text', '2211102034', '', true)
      + field('email', 'Email kampus', 'email', 'budi@kampus.ac.id', '', true)
      + field('hp', 'Nomor HP', 'tel', '0812xxxxxxx', '', true)
      + field('password', 'Password', 'password', 'Minimal 8 karakter', '', true);
  } else {
    form = field('email', 'Email terdaftar', 'email', 'budi@kampus.ac.id', '', true);
  }

  const err = state.formError
    ? '<div class="field__error" role="alert">' + icon('alert', 15) + '<span>' + esc(state.formError) + '</span></div>'
    : '';

  const primaryLabel = isAdmin ? 'LOGIN ADMIN' : (mode === 'login' ? 'MASUK' : mode === 'register' ? 'DAFTAR' : 'KIRIM LINK RESET');
  const primaryAction = isAdmin ? 'login' : (mode === 'login' ? 'login' : mode === 'register' ? 'register' : 'forgot');

  const foot = isAdmin
    ? '<div class="auth__foot"><span>Bukan admin? </span><button class="auth__link" style="padding:0" data-action="set-auth-role" data-role="student">Masuk sebagai mahasiswa</button></div>'
    : mode === 'login'
      ? '<div class="auth__foot"><span>Belum punya akun? </span><button class="auth__link" style="padding:0" data-action="set-auth-mode" data-mode="register"><strong>Daftar di sini</strong></button></div>'
      : '<div class="auth__foot"><span>Sudah punya akun? </span><button class="auth__link" style="padding:0" data-action="set-auth-mode" data-mode="login"><strong>Masuk</strong></button></div>';

  return '<div class="auth">'
    + '<div class="auth__brand">'
    + '<div class="logo">' + icon('utensils', 34) + '</div>'
    + '<h1 class="auth__title">' + (isAdmin ? 'Panel Admin Kantin' : 'Pre-Order Kantin Kampus') + '</h1>'
    + '<p class="auth__tag">' + (isAdmin ? 'Kelola pesanan dan stok secara real-time.' : 'Pre-order. Ambil on-time. Makan tenang.') + '</p>'
    + '</div>'
    + '<div class="seg" role="group" aria-label="Pilih peran">'
    + '<button class="seg__btn" data-action="set-auth-role" data-role="student" aria-pressed="' + (!isAdmin) + '">Mahasiswa</button>'
    + '<button class="seg__btn" data-action="set-auth-role" data-role="admin" aria-pressed="' + isAdmin + '">Admin</button>'
    + '</div>'
    + '<form class="auth__form" id="authform" novalidate>' + form + err
    + '<button type="submit" class="btn btn--accent btn--lg btn--block" data-action="' + primaryAction + '">' + primaryLabel + '</button>'
    + '</form>'
    + foot
    + '</div>';
}

function viewMenu() {
  const q = state.search.trim().toLowerCase();
  const list = state.menu.filter(i =>
    (state.category === 'Semua' || i.cat === state.category) &&
    (!q || i.name.toLowerCase().includes(q) || i.desc.toLowerCase().includes(q))
  );

  const chips = '<div class="chips" role="group" aria-label="Filter kategori">'
    + categories().map(c => '<button class="chip" data-action="set-category" data-cat="' + esc(c) + '" aria-pressed="' + (state.category === c) + '">' + esc(c) + '</button>').join('')
    + '</div>';

  const hero = '<div class="hero" style="margin-top:var(--space-3)">'
    + '<span class="hero__eyebrow">Pre-order hari ini</span>'
    + '<span class="hero__title">Ambil sesuai jadwal, tanpa antre</span>'
    + '<div class="hero__meta">' + icon('clock', 15) + '<span>Kantin buka 07:00–17:00 · Pickup mulai ' + nextPickupHint() + '</span></div>'
    + '</div>';

  const cards = list.length ? '<div class="menu-grid">' + list.map(dishCard).join('') + '</div>'
    : '<div class="empty"><div class="empty__ic">' + icon('search', 26) + '</div>'
      + '<div><div style="font-weight:600;color:var(--foreground)">Menu tidak ditemukan</div>'
      + '<div style="font-size:13px;margin-top:4px">Coba kata kunci atau kategori lain.</div></div></div>';

  return hero
    + '<div class="search-wrap" style="margin-top:var(--space-4)">'
    + '<label class="sr-only" for="search">Cari menu</label>'
    + icon('search', 19)
    + '<input class="input search-input" id="search" type="search" placeholder="Cari menu…" value="' + esc(state.search) + '" />'
    + '</div>'
    + '<div style="margin-top:var(--space-3)">' + chips + '</div>'
    + cards;
}

function dishCard(i) {
  const left = stockLeft(i.id);
  const out = i.stock <= 0;
  const low = !out && i.stock <= 5;
  const stockCls = out ? 'dish__stock--out' : low ? 'dish__stock--low' : '';
  const stockText = out ? 'Habis' : 'Stok ' + i.stock;
  const stockIc = out ? 'alert' : low ? 'alert' : 'check';
  return '<article class="dish">'
    + '<div class="dish__media">'
    + '<img class="od-media od-media-cover dish__img" src="' + i.img + '" width="' + i.w + '" height="' + i.h + '" alt="' + esc(i.name) + '" loading="lazy" />'
    + '<span class="dish__stock ' + stockCls + '">' + icon(stockIc, 12) + stockText + '</span>'
    + '</div>'
    + '<div class="dish__body">'
    + '<h3 class="dish__name od-clamp-2">' + esc(i.name) + '</h3>'
    + '<p class="dish__desc od-clamp-2">' + esc(i.desc) + '</p>'
    + '<div class="dish__foot">'
    + '<span class="dish__price od-nowrap">' + fmt(i.price) + '</span>'
    + '<button class="addbtn" data-action="add-to-cart" data-item="' + i.id + '" aria-label="Tambah ' + esc(i.name) + ' ke keranjang"' + (out || left <= 0 ? ' disabled' : '') + '>' + icon('plus', 20) + '</button>'
    + '</div></div></article>';
}

function viewCart() {
  const ids = Object.keys(state.cart).filter(id => item(id));
  if (!ids.length) {
    return '<div class="empty" style="padding-top:var(--space-12)">'
      + '<div class="empty__ic">' + icon('cart', 26) + '</div>'
      + '<div><div style="font-weight:600;color:var(--foreground)">Keranjang masih kosong</div>'
      + '<div style="font-size:13px;margin-top:4px">Pilih menu favoritmu dulu ya.</div></div>'
      + '<button class="btn btn--primary" data-action="go" data-route="menu">' + icon('utensils', 18) + 'Lihat Menu</button></div>';
  }
  const lines = ids.map(id => {
    const i = item(id), qty = state.cart[id];
    return '<div class="line"><img class="line__img" src="' + i.img + '" width="' + i.w + '" height="' + i.h + '" alt="" />'
      + '<div class="line__body"><div class="od-row-top" style="--od-gap:6px"><div class="od-fill"><div class="line__name od-clamp-2">' + esc(i.name) + '</div>'
      + '<div class="line__price od-nowrap">' + fmt(i.price) + ' / porsi</div></div>'
      + '<button class="iconbtn iconbtn--end" data-action="remove" data-item="' + id + '" aria-label="Hapus ' + esc(i.name) + '">' + icon('trash', 18) + '</button></div>'
      + '<div class="line__foot"><div class="stepper" role="group" aria-label="Jumlah ' + esc(i.name) + '">'
      + '<button class="stepper__btn" data-action="dec" data-item="' + id + '" aria-label="Kurangi"' + (qty <= 1 ? ' disabled' : '') + '>' + icon('minus', 17) + '</button>'
      + '<span class="stepper__val" aria-live="polite">' + qty + '</span>'
      + '<button class="stepper__btn" data-action="inc" data-item="' + id + '" aria-label="Tambah"' + (qty >= 10 || qty >= i.stock ? ' disabled' : '') + '>' + icon('plus', 17) + '</button>'
      + '</div>'
      + '<span class="line__sub od-nowrap">' + fmt(i.price * qty) + '</span></div></div></div>';
  }).join('');

  const pickup = '<div class="section-title">Kapan mau ambil?</div><div class="option-list" role="group" aria-label="Pilih jam ambil">'
    + pickupSlots().map(p => '<button class="pick" data-action="select-pickup" data-time="' + p.t + '" aria-pressed="' + (state.pickup === p.t) + '"' + (p.disabled ? ' disabled' : '') + '>'
      + '<span class="pick__ring" aria-hidden="true"></span>'
      + '<span class="od-fill od-stack" style="--od-gap:2px"><span class="pick__time">' + p.t + '</span>'
      + '<span class="pick__wait">' + p.wait + '</span></span></button>').join('')
    + '</div>';

  return '<div style="margin-top:var(--space-2)">' + lines + '</div>'
    + '<div class="card" style="padding:var(--space-4);margin-top:var(--space-4)">'
    + '<div class="summary-row"><span class="muted">Subtotal</span><span class="od-nowrap">' + fmt(cartTotal()) + '</span></div>'
    + '<div class="summary-row"><span class="muted">Biaya layanan</span><span class="od-nowrap">Rp 0</span></div>'
    + '<div class="summary-row summary-row--total"><span>Total</span><span class="od-nowrap">' + fmt(cartTotal()) + '</span></div>'
    + '</div>'
    + pickup
    + '<div class="section-title">Catatan (opsional)</div>'
    + '<textarea class="input" id="note" rows="2" maxlength="255" placeholder="Contoh: tanpa sambal" style="resize:vertical;font-family:inherit">' + esc(state.note || '') + '</textarea>'
    + '<button class="btn btn--accent btn--lg btn--block" style="margin-top:var(--space-4)" data-action="to-payment"' + (state.pickup ? '' : ' disabled') + '>'
    + (state.pickup ? 'Lanjut ke Pembayaran' : 'Pilih jam ambil dulu') + icon('arrowRight', 18) + '</button>';
}

function viewPayment() {
  const methods = [
    { id: 'ewallet', name: 'E-Wallet', hint: 'OVO · GoPay · DANA', ic: 'wallet' },
    { id: 'transfer', name: 'Transfer Bank', hint: 'BCA · Mandiri · BNI', ic: 'card' },
    { id: 'qris', name: 'QRIS', hint: 'Scan semua e-wallet', ic: 'qr' }
  ];
  return '<div class="card card--section" style="margin-top:var(--space-2)">'
    + '<div class="cartbar__label">Total Bayar</div>'
    + '<div style="font-family:var(--font-display);font-weight:800;font-size:32px;letter-spacing:-.02em" class="od-nowrap">' + fmt(cartTotal()) + '</div>'
    + '<div class="hero__meta" style="color:var(--foreground-muted);margin-top:6px">' + icon('clock', 15)
    + '<span>Pickup ' + esc(state.pickup ? pickupLabel(plannedPickup()) : '-') + ' · ' + cartCount() + ' item</span></div>'
    + '</div>'
    + '<div class="section-title">Metode Pembayaran</div>'
    + '<div class="option-list" role="group" aria-label="Metode pembayaran">'
    + methods.map(m => '<button class="pay" data-action="select-pay" data-method="' + m.id + '" aria-pressed="' + (state.payMethod === m.id) + '">'
      + '<span class="pay__logo">' + icon(m.ic, 20) + '</span>'
      + '<span class="od-fill od-stack" style="--od-gap:2px"><span class="pay__name">' + m.name + '</span>'
      + '<span class="pay__hint">' + m.hint + '</span></span>'
      + '<span class="pick__ring" aria-hidden="true"></span></button>').join('')
    + '</div>'
    + '<div class="card" style="padding:var(--space-3);margin-top:var(--space-4);display:flex;gap:var(--space-2);align-items:flex-start">'
    + '<span style="color:var(--foreground-muted);flex:none">' + icon('info', 17) + '</span>'
    + '<span style="font-size:12.5px;line-height:1.5;color:var(--foreground-secondary)">Pesanan dibatalkan otomatis bila pembayaran tidak selesai dalam 10 menit.</span>'
    + '</div>'
    + '<button class="btn btn--accent btn--lg btn--block" style="margin-top:var(--space-4)" data-action="pay">Bayar Sekarang</button>';
}

function viewSuccess() {
  const o = orderById(state.lastOrderId) || myOrders()[0];
  if (!o) return viewMenu();
  const deadline = o.expiresAt ? String(o.expiresAt).slice(11, 16) : null;
  return '<div style="display:grid;place-items:center;gap:var(--space-3);padding:var(--space-8) 0 var(--space-4);text-align:center">'
    + '<div style="width:84px;height:84px;border-radius:50%;display:grid;place-items:center;background:color-mix(in srgb,var(--status-completed) 16%,transparent);color:var(--status-completed)">' + icon('checkCircle', 44) + '</div>'
    + '<div style="font-family:var(--font-display);font-weight:800;font-size:22px">Pesanan Dibuat</div>'
    + '<div class="muted" style="font-size:13.5px">Menunggu konfirmasi pembayaran.</div>'
    + '</div>'
    + '<div class="card" style="padding:var(--space-5)">'
    + '<div class="summary-row"><span class="muted">Order ID</span><span style="font-weight:600">' + esc(o.code || o.id) + '</span></div>'
    + '<div class="summary-row"><span class="muted">Pengambilan</span><span style="font-weight:600">' + esc(pickupLabel(o)) + '</span></div>'
    + '<div class="summary-row"><span class="muted">Jumlah item</span><span style="font-weight:600">' + o.items.reduce((s, l) => s + l.qty, 0) + '</span></div>'
    + '<div class="summary-row summary-row--total"><span>Total</span><span class="od-nowrap">' + fmt(o.total) + '</span></div>'
    + '</div>'
    + (deadline ? '<div class="note" style="margin-top:var(--space-4)">' + icon('clock', 14)
      + '<span>Bayar sebelum pukul ' + deadline + ', kalau lewat pesanan dibatalkan dan stok kembali.</span></div>' : '')
    + '<div style="display:grid;gap:var(--space-3);margin-top:var(--space-4)">'
    + '<button class="btn btn--primary btn--lg btn--block" data-action="order-detail" data-order="' + o.id + '">Lacak Pesanan</button>'
    + '<button class="btn btn--secondary btn--block" data-action="go" data-route="menu">Kembali ke Menu</button>'
    + '</div>';
}

function viewOrders() {
  const list = myOrders();
  if (!list.length) {
    return '<div class="empty" style="padding-top:var(--space-12)">'
      + '<div class="empty__ic">' + icon('receipt', 26) + '</div>'
      + '<div><div style="font-weight:600;color:var(--foreground)">Belum ada pesanan</div>'
      + '<div style="font-size:13px;margin-top:4px">Pesanan aktifmu akan muncul di sini.</div></div>'
      + '<button class="btn btn--primary" data-action="go" data-route="menu">Mulai Pesan</button></div>';
  }
  return '<div style="margin-top:var(--space-2)">' + list.map(o => {
    return '<button class="order-card" style="width:100%;text-align:left;cursor:pointer;margin-top:var(--space-3)" data-action="order-detail" data-order="' + o.id + '">'
      + '<div class="od-row"><div class="od-fill od-stack" style="--od-gap:2px"><span style="font-weight:600;font-size:14px">' + esc(o.code || o.id) + '</span>'
      + '<span class="muted" style="font-size:12px">Pickup ' + esc(pickupLabel(o)) + ' · ' + o.items.length + ' jenis item</span></div>'
      + statusBadge(o.status) + '</div>'
      + '<div class="od-row"><span class="od-fill" style="font-family:var(--font-display);font-weight:700;font-size:16px" >' + fmt(o.total) + '</span>'
      + '<span style="color:var(--foreground-muted)">' + icon('chevronRight', 18) + '</span></div></button>';
  }).join('') + '</div>';
}

function viewTracking() {
  const o = orderById(state.lastOrderId) || myOrders()[0];
  if (!o) return viewOrders();
  const cancelled = o.status === 'cancelled';
  const idx = STATUS_ORDER.indexOf(o.status);

  const steps = STATUS_ORDER.map((s, i) => {
    const m = STATUS[s];
    const done = !cancelled && i < idx;
    const current = !cancelled && i === idx;
    const nodeCls = done ? 'tl__node--done' : current ? 'tl__node--current' : '';
    const connCls = done ? 'tl__conn--done' : '';
    const meta = {
      pending: 'Order dibuat · menunggu pembayaran',
      paid: 'Pembayaran dikonfirmasi · Rp ' + o.total.toLocaleString('id-ID'),
      ready: 'Ambil di counter kantin',
      completed: 'Pesanan sudah diambil'
    }[s];
    const title = '<span class="tl__title' + (current ? ' tl__title--current' : '') + '">' + m.label + '</span>';
    return '<div class="tl"><div class="tl__rail"><span class="tl__node ' + nodeCls + '">'
      + (done ? icon('check', 14) : current ? icon(m.icon, 13) : icon(m.icon, 12)) + '</span>'
      + (i < STATUS_ORDER.length - 1 ? '<span class="tl__conn ' + connCls + '"></span>' : '')
      + '</div><div class="tl__body">' + title
      + '<div class="tl__meta">' + (current ? (o.time || '') + ' · sekarang' : meta) + '</div></div></div>';
  }).join('');

  const cancelBadge = cancelled
    ? '<div class="card" style="padding:var(--space-4);border-color:var(--status-cancelled);margin-bottom:var(--space-4)">'
      + '<div style="display:flex;align-items:center;gap:var(--space-2);color:var(--status-cancelled);font-weight:600">' + icon('x', 18) + 'Pesanan Dibatalkan</div></div>'
    : '';

  /* Tombol batal/selesai milik mahasiswa tidak ditampilkan: backend hanya
     mengizinkan admin yang mengubah status pesanan. */

  const items = '<div class="card" style="padding:var(--space-4);margin-top:var(--space-4)">'
    + '<div class="cartbar__label" style="margin-bottom:8px">Rincian Pesanan</div>'
    + o.items.map(l => '<div class="summary-row"><span class="muted">' + esc(l.name) + ' × ' + l.qty + '</span><span class="od-nowrap">' + fmt(l.price * l.qty) + '</span></div>').join('')
    + '<div class="summary-row summary-row--total"><span>Total</span><span class="od-nowrap">' + fmt(o.total) + '</span></div>'
    + (o.note ? '<div class="note" style="margin-top:var(--space-2)">' + icon('info', 14) + '<span>Catatan: ' + esc(o.note) + '</span></div>' : '')
    + '</div>';

  return '<div class="od-row" style="margin-top:var(--space-2)"><div class="od-fill"><div class="appbar__sub">Order ID</div>'
    + '<div style="font-family:var(--font-display);font-weight:700;font-size:16px">' + esc(o.code || o.id) + '</div>'
    + '<span class="muted" style="font-size:12px">Ambil ' + esc(pickupLabel(o)) + '</span></div>'
    + statusBadge(o.status) + '</div>'
    + cancelBadge
    + '<div class="card" style="padding:var(--space-5);margin-top:var(--space-4)"><div class="timeline">' + steps + '</div></div>'
    + items;
}

function accountHeader(name, meta, roleLabel) {
  const initial = name.trim().charAt(0).toUpperCase();
  return '<div class="card" style="margin-top:var(--space-2)"><div class="profile">'
    + '<div class="avatar">' + initial + '</div>'
    + '<div class="od-stack" style="--od-gap:4px"><div style="font-family:var(--font-display);font-weight:700;font-size:18px">' + esc(name) + '</div>'
    + '<div class="muted" style="font-size:12.5px">' + esc(meta) + '</div>'
    + '<span class="badge badge--soft" style="align-self:flex-start;color:var(--primary);background:color-mix(in srgb,var(--primary) 16%,transparent)">'
    + roleLabel + '</span></div></div></div>';
}

function accountRows(rows) {
  return '<div class="card" style="padding:0 var(--space-4);margin-top:var(--space-4)">'
    + rows.map(r => '<a class="rowlink" href="' + PAGES[r[2]] + '" data-action="go" data-route="' + r[2] + '"><span class="rowlink__ic">' + icon(r[0], 20) + '</span>'
      + '<span class="od-fill">' + r[1] + '</span>' + icon('chevronRight', 18) + '</a>').join('')
    + '</div>'
    + '<button class="btn btn--danger btn--block" style="margin-top:var(--space-4)" data-action="logout">' + icon('logout', 18) + 'Keluar</button>';
}

function viewAccountStudent() {
  const name = state.user ? state.user.nama : 'Mahasiswa';
  const id = state.user ? state.user.nim : '-';
  const rows = [['receipt', 'Riwayat Pesanan', 'orders'], ['bell', 'Preferensi Notifikasi', 'notifikasi'], ['info', 'Bantuan & Panduan', 'bantuan'], ['store', 'Tentang Kantin', 'tentang']];
  return accountHeader(name, (id && id !== '-' ? 'NIM ' + id : (state.user ? state.user.email : '')), 'Mahasiswa') + accountRows(rows);
}

function viewAccountAdmin() {
  const name = state.user ? state.user.nama : 'Admin Kantin';
  const id = state.user ? state.user.email : '';
  const rows = [['store', 'Profil Kantin', 'profilKantin'], ['bell', 'Preferensi Notifikasi', 'notifikasi'], ['clock', 'Jam Operasional', 'jamOperasional'], ['info', 'Bantuan', 'bantuan']];
  return accountHeader(name, 'Admin Kantin · ' + id, 'Admin') + accountRows(rows);
}

/* ---------- Account sub-pages (one page per profile menu item) ---------- */
function subpageHead(title, sub) {
  return '<div class="subpage-head"><h1 class="subpage-head__title">' + title + '</h1>'
    + (sub ? '<div class="subpage-head__sub">' + sub + '</div>' : '') + '</div>';
}

function toggleRow(label, desc, on) {
  return '<div class="pref"><div class="od-fill">'
    + '<div class="pref__label">' + label + '</div>'
    + (desc ? '<div class="pref__desc">' + desc + '</div>' : '') + '</div>'
    + '<button class="toggle" data-action="toggle-pref" role="switch" aria-checked="' + (on ? 'true' : 'false') + '" aria-label="' + label + '">'
    + '<span class="toggle__track"><span class="toggle__dot"></span></span></button></div>';
}

function viewNotifikasi() {
  const admin = state.role === 'admin';
  const rows = admin
    ? [['Pesanan baru masuk', 'Saat mahasiswa membayar pesanannya', true],
       ['Stok menipis', 'Peringatan saat stok di bawah 5 porsi', true],
       ['Ulasan mahasiswa', 'Saat ada rating atau masukan baru', false],
       ['Laporan harian', 'Ringkasan penjualan tiap pukul 17.00', true]]
    : [['Pesanan siap diambil', 'Saat admin menandai pesananmu siap', true],
       ['Pengingat jam ambil', '10 menit sebelum slot pengambilan', true],
       ['Promo & diskon', 'Info menu baru dan promo kantin', false],
       ['Ringkasan transaksi', 'Rekap pembelian bulanan', false]];
  return subpageHead('Preferensi Notifikasi', admin ? 'Atur pemberitahuan untuk panel admin.' : 'Pilih kabar yang ingin kamu terima.')
    + '<div class="card" style="padding:0 var(--space-4);margin-top:var(--space-4)">'
    + rows.map(r => toggleRow(r[0], r[1], r[2])).join('') + '</div>';
}

function viewBantuan() {
  const admin = state.role === 'admin';
  const faqs = admin
    ? [['Bagaimana menandai pesanan siap?', 'Buka papan Pesanan, lalu tekan tombol Siap pada kartu berstatus Dibayar.'],
       ['Bagaimana mengubah stok?', 'Buka Menu & Stok, gunakan tombol tambah atau kurang pada tiap hidangan.'],
       ['Apa arti tiap status?', 'Menunggu: belum dibayar. Dibayar: siap disiapkan. Siap: menunggu diambil. Selesai: sudah diambil.']]
    : [['Bagaimana cara memesan?', 'Pilih menu, tambahkan ke keranjang, tentukan jam ambil, lalu bayar.'],
       ['Metode pembayaran apa saja?', 'E-Wallet, Transfer Bank, dan QRIS tersedia di layar pembayaran.'],
       ['Bisakah membatalkan pesanan?', 'Batal hanya bisa lewat admin kantin. Hubungi admin sebelum pesanan disiapkan.'],
       ['Kapan pesanan bisa diambil?', 'Sesuai slot jam yang kamu pilih setelah pembayaran berhasil.']];
  return subpageHead(admin ? 'Bantuan' : 'Bantuan & Panduan', 'Pertanyaan yang sering muncul.')
    + '<div class="card" style="padding:0 var(--space-4);margin-top:var(--space-4)">'
    + faqs.map(f => '<details class="faq"><summary>' + f[0] + icon('chevronRight', 18) + '</summary><p>' + f[1] + '</p></details>').join('')
    + '</div>'
    + '<div class="card" style="padding:var(--space-4);margin-top:var(--space-4)"><div class="od-row">'
    + '<span class="rowlink__ic">' + icon('info', 20) + '</span>'
    + '<div class="od-fill"><div style="font-weight:600;font-size:14px">Masih butuh bantuan?</div>'
    + '<div class="muted" style="font-size:12.5px">Hubungi admin kantin di 0812-3456-7890</div></div></div></div>';
}

function viewTentang() {
  const info = [['Lokasi', 'Gedung A, Lantai 1'], ['Jam buka', 'Senin - Jumat, 07.00 - 17.00'], ['Kontak', '0812-3456-7890'], ['Versi', '1.0.0 (prototipe)']];
  return subpageHead('Tentang Kantin', 'Pre-order makanan kampus tanpa antre.')
    + '<div class="card" style="padding:var(--space-5);margin-top:var(--space-4);display:grid;gap:var(--space-2)">'
    + '<span class="logo" style="width:60px;height:60px;border-radius:18px">' + icon('utensils', 26) + '</span>'
    + '<div style="font-family:var(--font-display);font-weight:700;font-size:16px;margin-top:var(--space-2)">Kantin Kampus</div>'
    + '<p class="muted" style="font-size:13px;line-height:1.6;margin:0">Layanan pre-order kantin kampus yang membantu mahasiswa memesan lebih cepat dan memilih jam pengambilan tanpa mengantre, serta membantu pengelola kantin mengatur pesanan dan stok.</p></div>'
    + '<div class="card" style="padding:0 var(--space-4);margin-top:var(--space-4)">'
    + info.map(x => '<div class="rowlink" style="cursor:default"><span class="od-fill muted" style="font-size:13px">' + x[0] + '</span>'
        + '<span style="font-weight:600;font-size:13.5px">' + x[1] + '</span></div>').join('')
    + '</div>';
}

function viewProfilKantin() {
  return subpageHead('Profil Kantin', 'Informasi yang dilihat mahasiswa di aplikasi.')
    + '<div class="card" style="padding:var(--space-4);margin-top:var(--space-4);display:grid;gap:var(--space-3)">'
    + field('kantin-nama', 'Nama kantin', 'text', '', 'Kantin Kampus')
    + field('kantin-lokasi', 'Lokasi', 'text', '', 'Gedung A, Lantai 1')
    + field('kantin-kontak', 'Kontak', 'tel', '', '0812-3456-7890')
    + '<div class="field"><label class="field__label" for="f-kantin-desk">Deskripsi</label>'
    + '<input class="input" id="f-kantin-desk" name="kantin-desk" type="text" value="Masakan rumahan segar setiap hari." autocomplete="off" /></div>'
    + '</div>'
    + '<button class="btn btn--primary btn--block" style="margin-top:var(--space-4)" data-action="save-pref">' + icon('check', 18) + 'Simpan perubahan</button>';
}

function viewJamOperasional() {
  const days = [['Senin', '07.00 - 16.00', true], ['Selasa', '07.00 - 16.00', true], ['Rabu', '07.00 - 16.00', true], ['Kamis', '07.00 - 16.00', true], ['Jumat', '07.00 - 15.00', true], ['Sabtu', '08.00 - 13.00', false], ['Minggu', 'Tutup', false]];
  return subpageHead('Jam Operasional', 'Atur jam buka kantin per hari.')
    + '<div class="card" style="padding:0 var(--space-4);margin-top:var(--space-4)">'
    + days.map(d => toggleRow(d[0], d[1], d[2])).join('') + '</div>'
    + '<div class="note" style="margin-top:var(--space-4)">' + icon('info', 14) + '<span>Perubahan jam berlaku untuk slot pengambilan besok.</span></div>';
}

/* ---------- Admin views ---------- */
function statBox(label, value, color) {
  return '<div class="card" style="padding:10px 12px;display:grid;gap:2px">'
    + '<span style="font-family:var(--font-display);font-weight:800;font-size:20px;color:' + color + '">' + value + '</span>'
    + '<span style="font-size:10.5px;font-weight:600;color:var(--foreground-muted);line-height:1.2">' + label + '</span></div>';
}

function orderCardAdmin(o) {
  const items = o.items.map(l => '<span><b>' + esc(l.name) + '</b> × ' + l.qty + '</span>').join('');
  let actions = '';
  if (o.status === 'pending') {
    actions = '<button class="btn btn--secondary btn--sm" data-action="admin-confirm" data-order="' + o.id + '">Tandai Dibayar</button>';
  } else if (o.status === 'paid') {
    actions = '<button class="btn btn--primary btn--sm" data-action="admin-ready" data-order="' + o.id + '">' + icon('box', 16) + 'Siap</button>';
  } else if (o.status === 'ready') {
    actions = '<button class="btn btn--secondary btn--sm" data-action="admin-complete" data-order="' + o.id + '">' + icon('check', 16) + 'Selesai</button>';
  } else {
    actions = '<span class="muted" style="font-size:12px;align-self:center">' + (o.status === 'completed' ? 'Pesanan tuntas' : 'Dibatalkan') + '</span>';
  }
  return '<div class="order-card">'
    + '<div class="od-row"><div class="od-fill od-stack" style="--od-gap:2px">'
    + '<span style="font-weight:600;font-size:14px">' + esc(o.buyerName) + '</span>'
    + '<span class="muted" style="font-size:11.5px">' + o.nim + ' · masuk ' + o.time + ' · ambil ' + esc(pickupLabel(o)) + '</span></div>'
    + statusBadge(o.status) + '</div>'
    + '<div class="order-card__items">' + items + '</div>'
    + (o.note ? '<div class="note" style="margin-top:6px">' + icon('info', 14) + '<span>Catatan: ' + esc(o.note) + '</span></div>' : '')
    + '<div class="od-row"><span class="od-fill od-nowrap" style="font-family:var(--font-display);font-weight:700;font-size:15px">' + fmt(o.total) + '</span>'
    + '<div class="order-card__actions" style="flex:0 0 auto">' + actions + '</div></div>'
    + '</div>';
}

const BOARD_FILTERS = [
  { id: 'all', label: 'Semua' },
  { id: 'paid', label: 'Perlu disiapkan' },
  { id: 'ready', label: 'Siap diambil' },
  { id: 'completed', label: 'Selesai' }
];

function viewBoard() {
  /* Pesanan terbaru selalu di atas; id jadi pembeda bila created_at sama. */
  const all = state.orders.slice().sort((a, b) => {
    const ta = a.createdAt || '';
    const tb = b.createdAt || '';
    if (ta !== tb) return tb.localeCompare(ta);
    return Number(b.id) - Number(a.id);
  });
  const list = state.boardFilter === 'all' ? all : all.filter(o => o.status === state.boardFilter);
  const active = BOARD_FILTERS.find(f => f.id === state.boardFilter) || BOARD_FILTERS[0];

  const rail = '<div class="slot-rail" role="group" aria-label="Filter status pesanan" style="margin-top:var(--space-3)">'
    + BOARD_FILTERS.map(f => '<button class="chip" data-action="admin-filter" data-filter="' + f.id + '" aria-pressed="' + (state.boardFilter === f.id) + '">'
      + f.label + '</button>').join('')
    + '</div>';

  const summary = '<div class="od-grid" style="--od-cols:3;--od-gap:8px;margin-top:var(--space-3)">'
    + statBox('Perlu disiapkan', all.filter(o => o.status === 'paid').length, 'var(--status-paid)')
    + statBox('Siap diambil', all.filter(o => o.status === 'ready').length, 'var(--status-ready)')
    + statBox('Selesai', all.filter(o => o.status === 'completed').length, 'var(--status-completed)')
    + '</div>';

  const head = '<div class="board-head" style="margin-top:var(--space-4)"><span class="board-head__slot">' + active.label + '</span>'
    + '<span class="board-head__count">' + list.length + ' order</span></div>';

  const cards = list.length
    ? list.map(orderCardAdmin).join('')
    : '<div class="empty" style="padding-top:var(--space-10)"><div class="empty__ic">' + icon('layout', 26) + '</div>'
      + '<div><div style="font-weight:600;color:var(--foreground)">' + (state.boardFilter === 'all' ? 'Belum ada pesanan masuk' : 'Tidak ada order dengan status ini') + '</div>'
      + '<div style="font-size:13px;margin-top:4px">' + (state.boardFilter === 'all' ? 'Pesanan baru muncul otomatis di sini.' : 'Coba pilih filter lain.') + '</div></div></div>';

  return summary + '<div>' + rail + '</div>' + head + cards;
}

function viewStock() {
  const lowCount = state.menu.filter(i => i.active && i.stock <= 5).length;
  const banner = lowCount
    ? '<div class="card" style="padding:var(--space-3);border-color:var(--status-pending);margin-top:var(--space-2);display:flex;gap:var(--space-3);align-items:center">'
      + '<span style="color:var(--status-pending);flex:none">' + icon('alert', 20) + '</span>'
      + '<span class="od-fill" style="font-size:12.5px;color:var(--foreground-secondary)"><b>' + lowCount + ' item</b> berstok rendah. Segera restock agar tidak kehabisan.</span></div>'
    : '';
  const rows = state.menu.map(i => {
    const out = i.stock <= 0;
    const low = !out && i.stock <= 5;
    const badge = !i.active
      ? '<span class="badge" style="color:var(--foreground-muted);background:var(--background-section)">Nonaktif</span>'
      : out ? '<span class="badge" style="color:var(--status-cancelled);background:color-mix(in srgb,var(--status-cancelled) 14%,transparent)">Habis</span>'
        : low ? '<span class="badge" style="color:var(--status-pending);background:color-mix(in srgb,var(--status-pending) 18%,transparent)">Rendah</span>' : '';
    return '<div class="stock-row"><img class="stock-row__img" src="' + i.img + '" width="' + i.w + '" height="' + i.h + '" alt="" />'
      + '<div class="stock-row__body"><div class="od-row"><div class="od-fill od-stack" style="--od-gap:2px">'
      + '<span style="font-weight:600;font-size:13.5px" class="od-clamp-2">' + esc(i.name) + '</span>'
      + '<span class="muted od-nowrap" style="font-size:12px">' + fmt(i.price) + ' · ' + i.cat + '</span></div>'
      + badge
      + '<button class="iconbtn iconbtn--end" data-action="edit-menu" data-item="' + i.id + '" aria-label="Edit ' + esc(i.name) + '">' + icon('pencil', 18) + '</button>'
      + '</div>'
      + '<div class="od-row"><span class="od-fill" style="font-size:12px;color:var(--foreground-muted)">Stok tersedia</span>'
      + '<div class="stepper" role="group" aria-label="Stok ' + esc(i.name) + '">'
      + '<button class="stepper__btn" data-action="stock-dec" data-item="' + i.id + '" aria-label="Kurangi stok ' + esc(i.name) + '"' + (i.stock <= 0 ? ' disabled' : '') + '>' + icon('minus', 17) + '</button>'
      + '<span class="stepper__val">' + i.stock + '</span>'
      + '<button class="stepper__btn" data-action="stock-inc" data-item="' + i.id + '" aria-label="Tambah stok ' + esc(i.name) + '">' + icon('plus', 17) + '</button>'
      + '</div></div>'
      + '<div class="od-row" style="margin-top:8px"><span class="od-fill" style="font-size:12px;color:var(--foreground-muted)">'
      + (i.active ? 'Tampil di menu mahasiswa' : 'Disembunyikan dari menu') + '</span>'
      + '<button class="btn ' + (i.active ? 'btn--secondary' : 'btn--primary') + ' btn--sm" data-action="toggle-menu" data-item="' + i.id + '">'
      + (i.active ? 'Nonaktifkan' : 'Aktifkan') + '</button></div>'
      + (out && i.active ? '<div class="warn">' + icon('alert', 14) + 'Habis — segera restock</div>' : '')
      + '</div></div>';
  }).join('');
  const head = '<div class="od-row" style="margin-top:var(--space-2)">'
    + '<div class="section-title od-fill" style="margin:0">Daftar Menu</div>'
    + '<button class="btn btn--primary btn--sm" data-action="new-menu">' + icon('plus', 16) + 'Tambah Menu</button></div>';
  return banner + head + '<div style="margin-top:var(--space-3)">' + rows + '</div>';
}

/* ---------- Modal tambah/edit menu ---------- */
function menuFormField(id, label, input) {
  return '<div class="field"><label class="field__label" for="' + id + '">' + label + '</label>' + input + '</div>';
}

function viewMenuForm() {
  const f = state.menuForm;
  if (!f) return '';
  const edit = f.mode === 'edit';
  const it = edit ? item(f.id) : null;
  if (edit && !it) return '';
  const v = it
    ? { name: it.name, desc: it.desc, cat: it.cat, price: it.price, stock: it.stock, image: it.imageUrl }
    : { name: '', desc: '', cat: '', price: '', stock: 0, image: '' };
  const catOptions = state.menu.map(i => i.cat).filter((c, i, all) => c && all.indexOf(c) === i);

  const nameField = menuFormField('f-menu-nama', 'Nama menu <span class="field__req" aria-hidden="true">*</span>',
    '<input class="input" id="f-menu-nama" name="menu-nama" type="text" maxlength="100" value="' + esc(v.name) + '" placeholder="Contoh: Nasi Goreng Spesial" required aria-required="true" autocomplete="off" />');

  const priceField = menuFormField('f-menu-harga', 'Harga (Rp) <span class="field__req" aria-hidden="true">*</span>',
    '<input class="input" id="f-menu-harga" name="menu-harga" type="number" min="1" step="1" value="' + esc(v.price) + '" placeholder="25000" required aria-required="true" inputmode="numeric" />');

  const stockField = edit ? '' : menuFormField('f-menu-stok', 'Stok awal',
    '<input class="input" id="f-menu-stok" name="menu-stok" type="number" min="0" step="1" value="' + esc(v.stock) + '" placeholder="0" inputmode="numeric" />');

  const catField = menuFormField('f-menu-kategori', 'Kategori',
    '<input class="input" id="f-menu-kategori" name="menu-kategori" type="text" maxlength="50" value="' + esc(v.cat) + '" placeholder="Contoh: Makanan Utama" list="menu-kategori-list" autocomplete="off" />'
    + '<datalist id="menu-kategori-list">' + catOptions.map(c => '<option value="' + esc(c) + '"></option>').join('') + '</datalist>');

  const descField = menuFormField('f-menu-desc', 'Deskripsi',
    '<textarea class="input" id="f-menu-desc" name="menu-desc" rows="3" maxlength="255" style="resize:vertical;font-family:inherit" placeholder="Deskripsi singkat menu">' + esc(v.desc) + '</textarea>');

  const imageField = '<div class="field"><label class="field__label">Foto menu</label>'
    + '<div class="menu-upload"><img class="menu-preview" id="menu-image-preview" src="' + esc(menuImage(v.image)) + '" width="64" height="64" alt="Pratinjau foto menu" />'
    + '<div class="od-fill od-stack" style="--od-gap:6px">'
    + '<span class="menu-upload__name" id="menu-image-name">' + (v.image ? esc(v.image.split('/').pop()) : 'Belum ada foto') + '</span>'
    + '<div class="od-row" style="--od-gap:6px">'
    + '<button type="button" class="btn btn--secondary btn--sm" data-action="pick-menu-image">' + icon('upload', 16) + 'Upload</button>'
    + '<button type="button" class="btn btn--secondary btn--sm" data-action="capture-menu-image">' + icon('camera', 16) + 'Kamera</button>'
    + '</div></div></div>'
    + '<input type="file" id="f-menu-image-file" accept="image/jpeg,image/png,image/webp" hidden />'
    + '<input type="file" id="f-menu-camera" accept="image/*" capture="environment" hidden />'
    + '<div class="note" style="padding:0">' + icon('info', 14) + '<span>JPG, PNG, atau WebP, maksimal 5 MB. Foto dari kamera langsung bisa dipakai.</span></div></div>';

  const err = '<div id="menu-form-error">'
    + (f.error ? '<div class="field__error" role="alert">' + icon('alert', 15) + '<span>' + esc(f.error) + '</span></div>' : '')
    + '</div>';

  return '<div class="modal-backdrop" data-action="close-menu-form"></div>'
    + '<form class="modal-sheet" id="menu-form" role="dialog" aria-modal="true" aria-labelledby="menu-form-title" novalidate>'
    + '<div class="modal-sheet__head"><div class="od-fill od-stack" style="--od-gap:2px">'
    + '<span class="modal-sheet__eyebrow">' + (edit ? 'Edit menu' : 'Menu baru') + '</span>'
    + '<span class="modal-sheet__title" id="menu-form-title">' + (edit ? esc(it.name) : 'Tambah Menu') + '</span></div>'
    + '<button type="button" class="iconbtn iconbtn--end" data-action="close-menu-form" aria-label="Tutup">' + icon('x', 20) + '</button></div>'
    + '<div class="modal-sheet__body">'
    + nameField
    + (edit
      ? priceField
      : '<div class="od-grid" style="--od-cols:2;--od-gap:var(--space-3)">' + priceField + stockField + '</div>')
    + catField + descField + imageField
    + err
    + '</div>'
    + '<div class="modal-sheet__foot">'
    + '<button type="button" class="btn btn--secondary" data-action="close-menu-form">Batal</button>'
    + '<button type="submit" class="btn btn--primary" data-action="save-menu">'
    + icon(edit ? 'check' : 'plus', 18) + (edit ? 'Simpan Perubahan' : 'Tambah Menu') + '</button></div>'
    + '</form>';
}

function renderMenuForm(force) {
  const root = $('modal');
  if (!root) return;
  if (!state.menuForm) {
    if (root.hidden || root.classList.contains('is-closing')) return;
    if (reducedMotion()) { resetMenuForm(root); return; }
    root.classList.add('is-closing');
    setTimeout(() => { if (!state.menuForm) resetMenuForm(root); }, LEAVE_MS);
    return;
  }
  root.classList.remove('is-closing');
  /* Jangan render ulang saat polling supaya isian form tidak hilang. */
  const key = state.menuForm.mode + ':' + (state.menuForm.id || '');
  if (!force && !root.hidden && root.dataset.form === key) return;
  const html = viewMenuForm();
  root.innerHTML = html;
  root.dataset.form = key;
  root.hidden = !html;
}

function resetMenuForm(root) {
  root.innerHTML = '';
  root.hidden = true;
  root.dataset.form = '';
  root.classList.remove('is-closing');
}

function showMenuFormError(message) {
  const box = $('menu-form-error');
  if (box) {
    box.innerHTML = message
      ? '<div class="field__error" role="alert">' + icon('alert', 15) + '<span>' + esc(message) + '</span></div>'
      : '';
  }
}

/* ---------- Render ---------- */
function enterScreen() {
  const s = $('screen');
  if (!s) return;
  s.classList.add('is-entering');
  clearTimeout(enterScreen._t);
  enterScreen._t = setTimeout(() => s.classList.remove('is-entering'), 700);
}

function renderView() {
  const map = {
    auth: viewAuth, menu: viewMenu, cart: viewCart, payment: viewPayment, success: viewSuccess,
    orders: viewOrders, tracking: viewTracking, accountStudent: viewAccountStudent, accountAdmin: viewAccountAdmin,
    notifikasi: viewNotifikasi, bantuan: viewBantuan, tentang: viewTentang, profilKantin: viewProfilKantin, jamOperasional: viewJamOperasional,
    board: viewBoard, stock: viewStock
  };
  const s = $('screen');
  const top = s ? s.scrollTop : 0;
  state.route = PAGE;
  renderAppbar();
  s.innerHTML = state.loading ? skeleton() : (map[PAGE] || viewMenu)();
  renderCartbar();
  renderTabbar();
  renderMenuForm();
  $('app').setAttribute('data-route', PAGE);
  s.scrollTop = top;
  persist();
}

/* ---------- Actions ---------- */
/* ---------- Data + aksi ke API ---------- */
const dataLoaded = { menu: false, orders: false };

function pageNeeds(page) {
  return {
    menu: ['menu', 'cart', 'payment', 'stock'].includes(page),
    orders: ['orders', 'tracking', 'success', 'board'].includes(page)
  };
}

/* Dipakai untuk melewati render ulang saat data hasil refresh tidak berubah. */
function dataSignature(page) {
  const needs = pageNeeds(page);
  return (needs.menu ? JSON.stringify(state.menu) : '')
    + '|' + (needs.orders ? JSON.stringify(state.orders) : '');
}

async function loadData() {
  const needs = pageNeeds(PAGE);

  const jobs = [];
  if (needs.menu) {
    jobs.push(api(PAGE === 'stock' ? '/admin/menus' : '/menus').then(rows => {
      state.menu = rows.map(mapMenu);
      dataLoaded.menu = true;
    }));
  }
  if (needs.orders) {
    jobs.push(api(PAGE === 'board' ? '/admin/orders' : '/orders/me').then(rows => {
      state.orders = rows.map(mapOrder);
      dataLoaded.orders = true;
    }));
  }
  await Promise.all(jobs);
}

function setSession(token, user) {
  state.role = user.role === 'admin' ? 'admin' : 'student';
  state.user = {
    id: String(user.id),
    nama: user.nama,
    nim: user.nim || '',
    email: user.email,
    role: user.role
  };
  /* Jangan bawa cache data milik sesi sebelumnya ke akun yang baru login. */
  state.menu = [];
  state.orders = [];
  dataLoaded.menu = false;
  dataLoaded.orders = false;
  auth.save(token, state.user);
}

async function doLogin() {
  const form = $('authform');
  if (form && !form.checkValidity()) { form.reportValidity(); return; }

  const isAdmin = state.authRole === 'admin';
  const emailEl = $(isAdmin ? 'f-adminid' : 'f-login');
  const email = emailEl ? emailEl.value.trim() : '';
  const password = $('f-password') ? $('f-password').value : '';

  try {
    const res = await api('/auth/login', { method: 'POST', body: { email: email, password: password } });
    if (isAdmin && res.user.role !== 'admin') {
      state.formError = 'Akun ini bukan admin.';
      renderView();
      return;
    }
    state.formError = null;
    setSession(res.token, res.user);
    navigate(state.role === 'admin' ? 'board' : 'menu', {
      replace: true,
      flash: { msg: 'Selamat datang, ' + state.user.nama.split(' ')[0] + '!', ic: 'checkCircle' }
    });
  } catch (e) {
    state.formError = e.message;
    renderView();
  }
}

async function doRegister() {
  const form = $('authform');
  if (form && !form.checkValidity()) { form.reportValidity(); return; }

  const payload = {
    nim: ($('f-nim') ? $('f-nim').value.trim() : '') || null,
    nama: $('f-nama').value.trim(),
    email: $('f-email').value.trim(),
    password: $('f-password') ? $('f-password').value : ''
  };

  if (payload.password.length < 8) {
    state.formError = 'Password minimal 8 karakter.';
    renderView();
    return;
  }

  try {
    await api('/auth/register', { method: 'POST', body: payload });
    const res = await api('/auth/login', { method: 'POST', body: { email: payload.email, password: payload.password } });
    state.formError = null;
    setSession(res.token, res.user);
    navigate('menu', { replace: true, flash: { msg: 'Akun terdaftar. Selamat datang!', ic: 'checkCircle' } });
  } catch (e) {
    state.formError = e.message;
    renderView();
  }
}

async function doPay() {
  if (!state.pickup || !cartCount()) return;

  const items = Object.entries(state.cart).map(([id, qty]) => ({ menu_id: Number(id), quantity: qty }));
  const btn = document.querySelector('[data-action="pay"]');
  if (btn) btn.disabled = true;

  try {
    const pickup = plannedPickup();
    const res = await api('/orders', {
      method: 'POST',
      body: {
        items: items,
        pickup_at: pickup.date + 'T' + pickup.time + ':00',
        catatan: state.note && state.note.trim() ? state.note.trim() : null
      }
    });

    state.lastOrderId = String(res.id);
    state.cart = {};
    state.pickup = null;
    state.note = null;

    // Sinkronkan stok menu + riwayat pesanan setelah order dibuat.
    try {
      state.menu = (await api('/menus')).map(mapMenu);
      state.orders = (await api('/orders/me')).map(mapOrder);
    } catch (e) { /* pakai data lama */ }

    const done = (msg, ic) => navigate('success', { replace: true, flash: { msg: msg, ic: ic } });

    let cfg = null;
    try { cfg = await api('/payments/config'); } catch (e) { /* gateway opsional */ }

    if (!cfg || !cfg.enabled || !res.payment_token || /^dummy-/.test(res.payment_token)) {
      // ponytail: Midtrans belum dikonfigurasi -> anggap pesanan menunggu konfirmasi manual.
      done('Pesanan dibuat · ' + res.order_code, 'checkCircle');
      return;
    }

    try { await loadSnap(cfg); } catch (e) {
      done('Pesanan dibuat, gateway pembayaran gagal dimuat', 'alert');
      return;
    }

    window.snap.pay(res.payment_token, {
      onSuccess: () => done('Pembayaran berhasil · ' + res.order_code, 'checkCircle'),
      onPending: () => done('Pembayaran menunggu penyelesaian · ' + res.order_code, 'info'),
      onError: () => {
        toast('Pembayaran gagal. Coba lagi dari halaman Pesanan.', 'alert');
        navigate('orders');
      },
      onClose: () => navigate('orders', { flash: { msg: 'Pembayaran belum selesai', ic: 'info' } })
    });
  } catch (e) {
    if (btn) btn.disabled = false;
    toast(e.message, 'alert');
  }
}

let snapPromise = null;
function loadSnap(cfg) {
  if (window.snap) return Promise.resolve();
  if (snapPromise) return snapPromise;
  snapPromise = new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = (cfg.is_production ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com') + '/snap/snap.js';
    s.setAttribute('data-client-key', cfg.client_key);
    s.onload = () => resolve();
    s.onerror = () => { snapPromise = null; reject(new Error('Gagal memuat Snap')); };
    document.head.appendChild(s);
  });
  return snapPromise;
}

async function updateOrderStatus(id, status, message) {
  try {
    await api('/admin/orders/' + id + '/status', { method: 'PATCH', body: { status: status } });
    state.orders = (await api('/admin/orders')).map(mapOrder);
    renderView();
    toast(message, 'checkCircle');
  } catch (e) {
    toast(e.message, 'alert');
  }
}

async function setMenuStock(id, stock) {
  const it = item(id);
  if (!it) return;
  try {
    await api('/admin/menus/' + id + '/stock', { method: 'PATCH', body: { stok: stock } });
    it.stock = stock;
    renderView();
  } catch (e) {
    toast(e.message, 'alert');
  }
}

async function setMenuActive(id, active) {
  const it = item(id);
  if (!it) return;
  try {
    await api('/admin/menus/' + id, { method: 'PATCH', body: { is_active: active } });
    it.active = active;
    renderView();
    toast(active ? 'Menu diaktifkan.' : 'Menu dinonaktifkan.', 'checkCircle');
  } catch (e) {
    toast(e.message, 'alert');
  }
}

let cameraStream = null;

function openMenuForm(mode, id) {
  if (mode === 'edit' && !item(id)) return;
  closeCamera();
  state.menuForm = { mode: mode, id: id || null, error: null, imageFile: null };
  renderMenuForm(true);
  const first = $('f-menu-nama');
  if (first) first.focus();
}

function releaseMenuPreview() {
  const preview = $('menu-image-preview');
  if (preview && preview.dataset.objectUrl) {
    URL.revokeObjectURL(preview.dataset.objectUrl);
    delete preview.dataset.objectUrl;
  }
}

function closeMenuForm() {
  closeCamera();
  releaseMenuPreview();
  state.menuForm = null;
  renderMenuForm();
}

function setMenuImageFile(file, fromCamera) {
  const allowed = ['image/jpeg', 'image/png', 'image/webp'];
  if (allowed.indexOf(file.type) === -1) {
    toast('Format foto harus JPG, PNG, atau WebP.', 'alert');
    return false;
  }
  if (file.size > 5 * 1024 * 1024) {
    toast('Ukuran foto maksimal 5 MB.', 'alert');
    return false;
  }

  if (state.menuForm) state.menuForm.imageFile = fromCamera ? file : null;
  if (fromCamera) {
    ['f-menu-image-file', 'f-menu-camera'].forEach(id => { const el = $(id); if (el) el.value = ''; });
  }

  const preview = $('menu-image-preview');
  if (preview) {
    if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
    const url = URL.createObjectURL(file);
    preview.dataset.objectUrl = url;
    preview.src = url;
  }
  const label = $('menu-image-name');
  if (label) label.textContent = file.name;
  return true;
}

function handleMenuImageFile(input) {
  const file = input.files && input.files[0];
  if (!file) return;
  if (!setMenuImageFile(file, false)) {
    input.value = '';
    return;
  }
  const other = $(input.id === 'f-menu-image-file' ? 'f-menu-camera' : 'f-menu-image-file');
  if (other) other.value = '';
}

function isMobileCamera() {
  return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)
    || (navigator.maxTouchPoints > 1 && /Mac/i.test(navigator.platform || ''));
}

/* Di desktop, input capture hanya membuka folder. getUM memberi pratinjau kamera asli. */
function fallbackCamera(message) {
  if (isMobileCamera()) {
    const el = $('f-menu-camera');
    if (el) { el.click(); return; }
  }
  toast(message || 'Kamera tidak tersedia. Gunakan tombol Upload.', 'alert');
}

function renderCamera() {
  const root = $('camera');
  if (!root) return;
  root.innerHTML = '<div class="modal-backdrop" data-action="close-camera"></div>'
    + '<div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="camera-title">'
    + '<div class="modal-sheet__head"><div class="od-fill od-stack" style="--od-gap:2px">'
    + '<span class="modal-sheet__eyebrow">Foto menu</span>'
    + '<span class="modal-sheet__title" id="camera-title">Ambil dari kamera</span></div>'
    + '<button type="button" class="iconbtn iconbtn--end" data-action="close-camera" aria-label="Tutup">' + icon('x', 20) + '</button></div>'
    + '<div class="modal-sheet__body">'
    + '<video class="camera-preview" id="camera-video" autoplay playsinline muted></video>'
    + '<div class="note" style="padding:0">' + icon('info', 14) + '<span>Dekatkan kamera ke menu, lalu tekan Ambil Foto.</span></div>'
    + '</div>'
    + '<div class="modal-sheet__foot">'
    + '<button type="button" class="btn btn--secondary" data-action="close-camera">Batal</button>'
    + '<button type="button" class="btn btn--primary" id="camera-shutter" data-action="snap-camera" disabled>' + icon('camera', 18) + 'Ambil Foto</button>'
    + '</div></div>';
  root.hidden = false;
}

function openCamera() {
  if (!state.menuForm) return;
  const root = $('camera');
  if (!root || !root.hidden) return;

  renderCamera();

  if (!window.isSecureContext || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    closeCamera();
    fallbackCamera('Kamera langsung butuh HTTPS atau localhost. Gunakan tombol Upload.');
    return;
  }

  navigator.mediaDevices.getUserMedia({
    audio: false,
    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 960 } }
  }).then(stream => {
    if (!state.menuForm || root.hidden) {
      stream.getTracks().forEach(track => track.stop());
      return;
    }
    cameraStream = stream;
    const video = $('camera-video');
    if (!video) { closeCamera(); return; }
    const enable = () => { const btn = $('camera-shutter'); if (btn) btn.disabled = false; };
    video.addEventListener('loadedmetadata', enable, { once: true });
    video.srcObject = stream;
    const played = video.play();
    if (played && played.catch) played.catch(() => {});
    if (video.readyState >= 1) enable();
  }).catch(err => {
    closeCamera();
    const denied = err && (err.name === 'NotAllowedError' || err.name === 'SecurityError');
    fallbackCamera(denied
      ? 'Izin kamera ditolak. Gunakan tombol Upload untuk memilih foto.'
      : 'Kamera tidak tersedia di perangkat ini.');
  });
}

function closeCamera() {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
  const root = $('camera');
  if (!root || root.hidden || root.classList.contains('is-closing')) return;
  if (reducedMotion()) { resetCamera(root); return; }
  root.classList.add('is-closing');
  setTimeout(() => { if (root.classList.contains('is-closing')) resetCamera(root); }, LEAVE_MS);
}

function resetCamera(root) {
  root.innerHTML = '';
  root.hidden = true;
  root.classList.remove('is-closing');
}

function snapCamera() {
  const video = $('camera-video');
  if (!video || !cameraStream || !video.videoWidth) {
    toast('Kamera belum siap. Tunggu sebentar lalu coba lagi.', 'alert');
    return;
  }

  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0);
  canvas.toBlob(blob => {
    if (!blob) { toast('Gagal mengambil foto.', 'alert'); return; }
    const file = new File([blob], 'foto-menu-' + Date.now() + '.jpg', { type: 'image/jpeg' });
    if (!setMenuImageFile(file, true)) return;
    closeCamera();
    toast('Foto siap dipakai.', 'checkCircle');
  }, 'image/jpeg', 0.92);
}

async function saveMenuForm() {
  const form = $('menu-form');
  if (!form) return;
  if (!form.checkValidity()) { form.reportValidity(); return; }

  const f = state.menuForm;
  if (!f) return;
  showMenuFormError('');
  const btn = form.querySelector('[data-action="save-menu"]');
  if (btn) btn.disabled = true;

  const fileInput = $('f-menu-image-file');
  const cameraInput = $('f-menu-camera');
  const newFile = f.imageFile || (fileInput && fileInput.files[0]) || (cameraInput && cameraInput.files[0]) || null;
  const current = f.mode === 'edit' ? item(f.id) : null;

  const payload = {
    nama: $('f-menu-nama').value.trim(),
    deskripsi: $('f-menu-desc').value.trim() || null,
    kategori: $('f-menu-kategori').value.trim() || null,
    harga: Math.round(Number($('f-menu-harga').value)),
    image_url: current ? (current.imageUrl || null) : null
  };
  if (f.mode === 'create') payload.stok = Math.max(0, Math.round(Number($('f-menu-stok').value || 0)));

  try {
    if (newFile) {
      const fd = new FormData();
      fd.append('image', newFile);
      const uploaded = await api('/admin/menus/upload', { method: 'POST', body: fd });
      payload.image_url = uploaded.image_url;
    }
    if (f.mode === 'create') {
      await api('/admin/menus', { method: 'POST', body: payload });
    } else {
      await api('/admin/menus/' + f.id, { method: 'PATCH', body: payload });
    }
    state.menu = (await api('/admin/menus')).map(mapMenu);
    releaseMenuPreview();
    state.menuForm = null;
    renderView();
    toast(f.mode === 'create' ? 'Menu baru ditambahkan.' : 'Menu berhasil diperbarui.', 'checkCircle');
  } catch (e) {
    if (btn) btn.disabled = false;
    state.menuForm.error = e.message;
    showMenuFormError(e.message);
  }
}

function handle(action, el) {
  const d = el.dataset;
  switch (action) {
    case 'back': back(); return;
    case 'go': navigate(d.route); return;
    case 'noop': toast('Fitur ini belum tersedia di prototipe.', 'info'); return;

    case 'toggle-pref': {
      const on = el.getAttribute('aria-checked') === 'true';
      el.setAttribute('aria-checked', on ? 'false' : 'true');
      return;
    }
    case 'save-pref': toast('Perubahan disimpan.', 'checkCircle'); return;

    case 'set-auth-role':
      state.authRole = d.role; state.formError = null; state.authMode = 'login';
      renderView(); return;
    case 'set-auth-mode':
      state.authMode = d.mode; state.formError = null; renderView(); return;

    case 'login': doLogin(); return;
    case 'register': doRegister(); return;
    case 'forgot':
      if (!$('authform').checkValidity()) { $('authform').reportValidity(); return; }
      state.formError = null;
      toast('Link reset dikirim ke emailmu.', 'checkCircle');
      state.authMode = 'login'; renderView(); return;

    case 'logout':
      auth.clear();
      state.role = null; state.user = null; state.cart = {}; state.pickup = null;
      state.authMode = 'login'; state.formError = null;
      navigate('auth', { replace: true, flash: { msg: 'Kamu sudah keluar.', ic: 'logout' } }); return;

    case 'set-category': state.category = d.cat; renderView(); return;

    case 'add-to-cart': {
      const it = item(d.item);
      if (!it || stockLeft(d.item) <= 0) return;
      state.cart[d.item] = (state.cart[d.item] || 0) + 1;
      renderView();
      toast(it.name + ' ditambahkan', 'cart');
      return;
    }
    case 'inc': {
      const it = item(d.item);
      if (!it) return;
      if (state.cart[d.item] >= 10 || state.cart[d.item] >= it.stock) { toast('Stok tidak mencukupi', 'alert'); return; }
      state.cart[d.item]++; renderView(); return;
    }
    case 'dec':
      if (state.cart[d.item] > 1) { state.cart[d.item]--; } else { delete state.cart[d.item]; }
      renderView(); return;
    case 'remove':
      delete state.cart[d.item]; renderView(); return;

    case 'select-pickup': state.pickup = d.time; renderView(); return;
    case 'select-pay': state.payMethod = d.method; renderView(); return;
    case 'to-payment':
      if (!state.pickup) return;
      navigate('payment'); return;
    case 'pay': doPay(); return;
    case 'order-detail': state.lastOrderId = String(d.order); navigate('tracking'); return;
    case 'complete-order':
    case 'cancel-order':
      toast('Status pesanan hanya bisa diubah oleh admin kantin.', 'info');
      return;

    case 'admin-filter': state.boardFilter = d.filter; renderView(); return;
    case 'admin-confirm': {
      const o = orderById(d.order);
      updateOrderStatus(d.order, 'paid', 'Pembayaran ' + (o ? o.code : d.order) + ' dikonfirmasi.');
      return;
    }
    case 'admin-ready': {
      const o = orderById(d.order);
      updateOrderStatus(d.order, 'ready', (o ? o.buyerName : 'Pesanan') + ' dinotifikasi: pesanan siap.');
      return;
    }
    case 'admin-complete':
      updateOrderStatus(d.order, 'completed', 'Pesanan selesai.');
      return;
    case 'stock-inc': {
      const it = item(d.item);
      if (it) setMenuStock(d.item, it.stock + 1);
      return;
    }
    case 'stock-dec': {
      const it = item(d.item);
      if (it && it.stock > 0) setMenuStock(d.item, it.stock - 1);
      return;
    }
    case 'toggle-menu': {
      const it = item(d.item);
      if (it) setMenuActive(d.item, !it.active);
      return;
    }
    case 'new-menu': openMenuForm('create'); return;
    case 'edit-menu': openMenuForm('edit', d.item); return;
    case 'close-menu-form': closeMenuForm(); return;
    case 'save-menu': saveMenuForm(); return;
    case 'pick-menu-image': {
      const el = $('f-menu-image-file');
      if (el) el.click();
      return;
    }
    case 'capture-menu-image': openCamera(); return;
    case 'close-camera': closeCamera(); return;
    case 'snap-camera': snapCamera(); return;
  }
}

/* ---------- Mount shell ---------- */
function mountShell() {
  document.body.insertAdjacentHTML('beforeend',
    '<div class="phone-frame" data-phone-shell data-platform="neutral">'
    + '<span class="hardware-button volume" aria-hidden="true"></span>'
    + '<span class="hardware-button power" aria-hidden="true"></span>'
    + '<section class="phone-screen" data-phone-screen>'
    + '<main class="phone-content" data-phone-content>'
    + '<div class="app" id="app">'
    + '<header class="appbar" id="appbar"></header>'
    + '<div class="screen" id="screen" role="main"></div>'
    + '<div id="cartbar"></div>'
    + '<nav class="tabbar" id="tabbar" aria-label="Navigasi utama"></nav>'
    + '</div>'
    + '</main>'
    + '<div class="modal-root" id="modal" hidden></div>'
    + '<div class="modal-root modal-root--camera" id="camera" hidden></div>'
    + '<div class="toast" id="toast" role="status" aria-live="polite" data-show="false"></div>'
    + '</section>'
    + '</div>'
  );
}

/* ---------- Events ---------- */
/* Rantai gambar: assets lokal -> folder referensi desain -> placeholder SVG.
   ponytail: buang rantai folder referensi begitu frontend/assets/ terisi. */
document.addEventListener('error', e => {
  const el = e.target;
  if (!el || el.tagName !== 'IMG' || el.dataset.fallback === 'final') return;
  if (el.dataset.fallback !== 'self') {
    el.dataset.fallback = 'self';
    el.src = '../Desain-Berdasarkan-PRD-dan-DESIGN/' + el.getAttribute('src');
  } else {
    el.dataset.fallback = 'final';
    el.src = IMG_FALLBACK;
  }
}, true);

document.addEventListener('click', e => {
  const el = e.target.closest('[data-action]');
  if (!el) return;
  if (el.tagName === 'BUTTON' && el.closest('form') && el.type === 'submit') return;
  if (el.tagName === 'BUTTON' || el.tagName === 'A') e.preventDefault();
  handle(el.dataset.action, el);
});
document.addEventListener('submit', e => {
  e.preventDefault();
  const btn = e.target.querySelector('button[type="submit"][data-action]') || e.target.querySelector('[data-action]');
  if (btn) handle(btn.dataset.action, btn);
});
document.addEventListener('input', e => {
  if (e.target.id === 'search') {
    state.search = e.target.value;
    renderView();
    const s = $('search');
    if (s) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
    return;
  }
  if (e.target.id === 'note') {
    state.note = e.target.value;
  }
});
document.addEventListener('change', e => {
  if (e.target.id === 'f-menu-image-file' || e.target.id === 'f-menu-camera') {
    handleMenuImageFile(e.target);
  }
});
document.addEventListener('keydown', e => {
  if (e.key !== 'Escape') return;
  const camera = $('camera');
  if (camera && !camera.hidden) {
    e.preventDefault();
    closeCamera();
    return;
  }
  if (state.menuForm) {
    e.preventDefault();
    closeMenuForm();
  }
});
window.addEventListener('popstate', () => {
  goToRoute(pageFromPath(location.pathname), { history: 'none' });
});
window.addEventListener('pagehide', () => {
  if (cameraStream) {
    cameraStream.getTracks().forEach(track => track.stop());
    cameraStream = null;
  }
});
window.addEventListener('pageshow', () => {
  const app = $('app');
  if (app) app.classList.remove('is-leaving');
});

/* ---------- Boot ---------- */
let refreshing = false;
async function refreshData() {
  const page = PAGE;
  if (refreshing || document.hidden || !state.role || !REFRESH_PAGES.includes(page)) return;
  refreshing = true;
  try {
    await loadData();
    if (page === PAGE) renderView();
  } catch (e) {
    /* data lama tetap tampil; toast hanya untuk aksi pengguna */
  } finally {
    refreshing = false;
  }
}

let pollTimer = null;
function schedulePolling() {
  clearInterval(pollTimer);
  pollTimer = null;
  if (!POLL_PAGES.includes(PAGE)) return;
  pollTimer = setInterval(refreshData, ['board', 'orders', 'tracking'].includes(PAGE) ? 30000 : 60000);
}

document.addEventListener('visibilitychange', () => {
  if (document.hidden) closeCamera();
  else refreshData();
});

function skeleton() {
  return '<div style="padding-top:var(--space-6);display:grid;gap:var(--space-3)">'
    + '<div class="card skeleton-card" style="height:88px"></div>'
    + '<div class="card skeleton-card" style="height:88px"></div>'
    + '<div class="muted" style="text-align:center;font-size:13px">Memuat data…</div></div>';
}

async function boot() {
  mountShell();

  const depth = (history.state && Number(history.state.depth)) || 0;
  history.replaceState({ page: PAGE, depth: depth }, '', location.href);

  /* Sesi lama dari localStorage divalidasi ulang ke server. */
  if (auth.token()) {
    try {
      const me = await api('/auth/me');
      state.role = me.role === 'admin' ? 'admin' : 'student';
      state.user = { id: String(me.id), nama: me.nama, nim: me.nim || '', email: me.email, role: me.role };
      auth.save(auth.token(), state.user);
    } catch (e) {
      auth.clear();
      state.role = null;
      state.user = null;
    }
  } else if (state.role) {
    /* Role tersimpan tapi token sudah tidak ada -> sesi tidak sah. */
    auth.clear();
    state.role = null;
    state.user = null;
  }

  if (PAGE === 'auth') {
    if (state.role) { goToRoute(roleHome(), { replace: true }); return; }
    renderView();
    enterScreen();
    showFlash();
    return;
  }

  if (!state.role) { goToRoute('auth', { replace: true }); return; }
  if (ADMIN_ONLY.includes(PAGE) && state.role !== 'admin') { goToRoute(roleHome(), { replace: true }); return; }
  if (state.role === 'admin' && STUDENT_PAGES.includes(PAGE)) { goToRoute('board', { replace: true }); return; }

  state.loading = true;
  renderView();
  try {
    await loadData();
  } catch (e) {
    toast(e.message, 'alert');
  }
  state.loading = false;
  renderView();
  enterScreen();
  showFlash();
  schedulePolling();
}

boot();
