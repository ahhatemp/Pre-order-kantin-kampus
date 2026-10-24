# Design System: Pre-Order Kantin Kampus

> **Status implementasi (v1):** Dokumen ini menjadi acuan visual. Implementasi aktual memakai
> CSS polos di `frontend/css/styles.css` (tanpa Tailwind/shadcn/build step). Token warna,
> tipografi, spacing, radius, shadow, dan status sudah dipakai. Dark mode belum diimplementasikan.

## 1. Design Principles

1. **Mobile-First, Thumb-Zone Optimized** — 80% user pakai HP. Tap targets min 44×44px, scroll-friendly. Critical actions dalam jangkauan ibu jari (bawah 60%).

2. **Appetizing but Fast** — Visual menarik (warna bold, kontras tinggi) tapi tidak *noisy*. Hierarchy jelas, tidak ada kompetisi elemen.

3. **Status Legibility** — Order status (`pending`/`paid`/`ready`/`completed`/`cancelled`) harus baca dalam 1 detik. Gunakan warna + icon + label Bahasa Indonesia.

4. **Admin Ergonomic** — Admin panel rancang untuk usage 6+ jam. Dense layout tapi tetap scannable, focus besar pada pickup-time board.

5. **Accessible by Default** — Contrast min 4.5:1 body text, 3:1 UI components. Reduced motion respected. Keyboard navigation supported.

---

## 2. Brand Identity

### Voice & Tone
- **Untuk Mahasiswa:** Santai, friendly, membantu, urgent (hemat waktu). Bahasa casual Bahasa Indonesia.
- **Untuk Admin:** Direct, clear, tidak berbelit. Focus on efficiency metrics.

### Tagline
_"Pre-order. Ambil on-time. Makan tenang."_

---

## 3. Color System

### Primitives (Raw Values)

```css
/* Warm Canvas */
--color-white: #FFFDF7;      /* Warm off-white, bukan pure white */
--color-cream: #FFF9F0;      /* Lighter warm */

/* Inks */
--color-ink-900: #1A1614;    /* Near-black, deep espresso */
--color-ink-700: #3D3530;    /* Dark gray-brown */
--color-ink-500: #6B6157;    /* Mid gray */
--color-ink-300: #B8AFA5;    /* Light gray */
--color-ink-200: #D9D3CD;    /* Very light gray */
--color-ink-100: #EFE9E2;    /* Almost white-gray */

/* Energetic Accents */
--color-lime-600: #A8E01F;   /* Electric lime (primary) */
--color-lime-500: #D4FF3F;   /* Bright lime (hover, highlight) */

--color-coral-600: #FF5A3C;  /* Hot coral (accent, CTA) */
--color-coral-500: #FF7357;  /* Lighter coral (hover) */

--color-violet-600: #7C4DFF; /* Electric violet (status: ready, admin action) */
--color-violet-500: #9D6DFF; /* Lighter violet (hover) */

/* Functional Status */
--color-amber-600: #FFB020;  /* Pending (warning) */
--color-blue-600: #3B82F6;   /* Paid (info) */
--color-green-600: #10B981;  /* Success (completed) */
--color-red-600: #EF4444;    /* Error / Cancelled */

/* Surfaces */
--color-surface-ink: #171412;      /* Dark section background */
--color-surface-light: #FAF7F2;    /* Light section background */
--color-border: #E5E0DB;           /* Border color */
--color-border-dark: #D4CCC3;      /* Darker border (focus) */
```

### Semantic Tokens

```css
/* Light Theme (Default) */
:root {
  /* Backgrounds */
  --background: var(--color-white);
  --background-secondary: var(--color-cream);
  --background-section: var(--color-surface-light);
  --background-dark: var(--color-surface-ink);
  
  /* Text */
  --foreground: var(--color-ink-900);
  --foreground-secondary: var(--color-ink-700);
  --foreground-muted: var(--color-ink-500);
  --foreground-subtle: var(--color-ink-300);
  
  /* Primary (Lime - Energy/Action) */
  --primary: var(--color-lime-600);
  --primary-hover: var(--color-lime-500);
  --primary-foreground: var(--color-ink-900);
  
  /* Accent (Coral - Highlight/CTA) */
  --accent: var(--color-coral-600);
  --accent-hover: var(--color-coral-500);
  --accent-foreground: white;
  
  /* Status Colors */
  --status-pending: var(--color-amber-600);
  --status-paid: var(--color-blue-600);
  --status-ready: var(--color-violet-600);
  --status-completed: var(--color-green-600);
  --status-cancelled: var(--color-red-600);
  
  /* UI */
  --border: var(--color-border);
  --input: var(--color-white);
  --ring: var(--color-lime-600);
  --ring-offset: var(--color-white);
}

/* Dark Theme */
@media (prefers-color-scheme: dark) {
  :root {
    --background: #0F0E0C;
    --background-secondary: #1A1614;
    --background-section: #262219;
    --background-dark: #1A1614;
    
    --foreground: #FFFDF7;
    --foreground-secondary: #E5E0DB;
    --foreground-muted: #B8AFA5;
    --foreground-subtle: #6B6157;
    
    --primary: var(--color-lime-500);
    --primary-hover: var(--color-lime-600);
    --primary-foreground: var(--color-ink-900);
    
    --accent: var(--color-coral-500);
    --accent-hover: var(--color-coral-600);
    --accent-foreground: white;
    
    --input: #262219;
    --border: #3D3530;
    --ring: var(--color-lime-500);
    --ring-offset: #1A1614;
  }
}
```

### Component Tokens

```css
:root {
  /* Button Primary */
  --button-primary-bg: var(--primary);
  --button-primary-fg: var(--primary-foreground);
  --button-primary-hover: var(--primary-hover);
  --button-primary-active: #8FCB1A;
  
  /* Button Accent */
  --button-accent-bg: var(--accent);
  --button-accent-fg: var(--accent-foreground);
  --button-accent-hover: var(--accent-hover);
  
  /* Button Subtle */
  --button-subtle-bg: var(--background-secondary);
  --button-subtle-fg: var(--foreground);
  --button-subtle-hover: var(--color-ink-100);
  
  /* Card */
  --card-bg: var(--background);
  --card-border: var(--border);
  --card-shadow: 0 2px 8px rgba(0 0 0 / 0.08);
  --card-shadow-hover: 0 8px 16px rgba(0 0 0 / 0.12);
  
  /* Input */
  --input-bg: var(--input);
  --input-border: var(--border);
  --input-border-focus: var(--ring);
  --input-placeholder: var(--foreground-muted);
  
  /* Badge */
  --badge-bg: var(--background-secondary);
  --badge-fg: var(--foreground);
  --badge-border: var(--border);
}
```

---

## 4. Typography

### Font Stack
- **Display/Heading:** Space Grotesk (700, 800)
- **Body/UI:** Inter (400, 500, 600)

### Scale

| Usage | Size | Weight | Line Height | Letter Spacing |
|---|---|---|---|---|
| **Display 1** | 48px | 800 | 1.1 | -1px |
| **Display 2** | 40px | 800 | 1.2 | -0.5px |
| **Heading 1** | 32px | 700 | 1.25 | 0 |
| **Heading 2** | 28px | 700 | 1.3 | 0 |
| **Heading 3** | 24px | 600 | 1.35 | 0 |
| **Body Large** | 16px | 400 | 1.5 | 0 |
| **Body** | 14px | 400 | 1.5 | 0 |
| **Body Small** | 12px | 400 | 1.5 | 0 |
| **Label** | 12px | 600 | 1.4 | 0.5px |
| **Caption** | 11px | 400 | 1.4 | 0 |

---

## 5. Spacing, Radius, Shadow, Motion

### Spacing Scale (Base 4px)

```
--space-1: 0.25rem;    /* 4px */
--space-2: 0.5rem;     /* 8px */
--space-3: 0.75rem;    /* 12px */
--space-4: 1rem;       /* 16px */
--space-5: 1.25rem;    /* 20px */
--space-6: 1.5rem;     /* 24px */
--space-8: 2rem;       /* 32px */
--space-10: 2.5rem;    /* 40px */
--space-12: 3rem;      /* 48px */
```

### Radius

```
--radius-sm: 8px;      /* Subtle */
--radius-md: 12px;     /* Components (default) */
--radius-lg: 16px;     /* Cards, large elements */
--radius-xl: 20px;     /* Sections, blocks */
--radius-full: 999px;  /* Pills, badges, fully rounded */
```

### Elevation (Shadow)

```
--shadow-xs: 0 1px 2px rgba(0 0 0 / 0.05);
--shadow-sm: 0 2px 8px rgba(0 0 0 / 0.08);
--shadow-md: 0 4px 12px rgba(0 0 0 / 0.1);
--shadow-lg: 0 8px 20px rgba(0 0 0 / 0.12);
--shadow-xl: 0 12px 32px rgba(0 0 0 / 0.15);
--shadow-interactive-hover: 0 8px 16px rgba(212 255 63 / 0.2);  /* Lime glow on hover */
```

### Motion

```
--duration-fast: 150ms;
--duration-normal: 250ms;
--duration-slow: 400ms;

--ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
--ease-out: cubic-bezier(0, 0, 0.2, 1);
--ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);

/* Keyframes */
@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slide-up {
  from { transform: translateY(8px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes pulse-lime {
  0%, 100% { box-shadow: 0 0 0 0 rgba(212, 255, 63, 0.7); }
  50% { box-shadow: 0 0 0 8px rgba(212, 255, 63, 0); }
}
```

**Respects `prefers-reduced-motion`:**
```css
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## 6. Status Design System

### Status → Color + Icon + Label

| Status | Color | Hex | Icon | Label ID | Description |
|---|---|---|---|---|---|
| `pending` | Amber | `#FFB020` | ⏳ | Menunggu Pembayaran | Order created, awaiting payment |
| `paid` | Blue | `#3B82F6` | ✓ | Sudah Dibayar | Payment confirmed, ready for prep |
| `ready` | Violet | `#7C4DFF` | 📦 | Siap Diambil | Food is ready, awaiting pickup |
| `completed` | Green | `#10B981` | ✓ | Selesai | Order completed and picked up |
| `cancelled` | Red | `#EF4444` | ✗ | Dibatalkan | Order cancelled by user or admin |

### Status Timeline (Order Tracking)

```
┌─────────────────────────────────────────────────┐
│ Order Status: Siap Diambil                      │
├─────────────────────────────────────────────────┤
│ ✓  Sudah Dibayar                    13:45      │
│    Rp 45.000                                    │
│                                                 │
│ ●  Siap Diambil                     14:05      │ ← Current
│    Ambil di counter kantin                      │
│                                                 │
│    Selesai                          --:--      │  Inactive
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 7. Component Specifications

### 7.1 Button

**Variants:** `primary`, `accent`, `secondary`, `ghost`, `danger` (kelas `btn--*`)

**Sizes:** default (min-height 44px), `sm` (44px), `lg` (52px), plus `block` (lebar penuh)

**States:** default, hover, active, focus, disabled

#### Default Variant

| State | Background | Text | Shadow | Border |
|---|---|---|---|---|
| default | `var(--button-primary-bg)` | `var(--button-primary-fg)` | none | none |
| hover | `var(--button-primary-hover)` | same | `var(--shadow-interactive-hover)` | none |
| active | `#8FCB1A` | same | none | none |
| focus | same + ring | same | `0 0 0 3px rgba(212, 255, 63, 0.3)` | none |
| disabled | `var(--color-ink-100)` | `var(--color-ink-300)` | none | none |

#### Accent Variant

| State | Background | Text | Shadow |
|---|---|---|---|
| default | `var(--button-accent-bg)` | `white` | none |
| hover | `var(--button-accent-hover)` | white | `0 8px 16px rgba(255, 90, 60, 0.2)` |
| active | `#E64829` | white | none |
| focus | same + ring | white | `0 0 0 3px rgba(255, 90, 60, 0.3)` |
| disabled | `var(--color-ink-100)` | `var(--color-ink-300)` | none |

#### Secondary Variant

| State | Background | Text | Border |
|---|---|---|---|
| default | `var(--button-subtle-bg)` | `var(--button-subtle-fg)` | `var(--border)` |
| hover | `var(--button-subtle-hover)` | same | `var(--border-dark)` |
| active | `#DCCCC3` | same | `var(--border-dark)` |
| disabled | `var(--color-ink-100)` | `var(--color-ink-300)` | `var(--border)` |

**Padding:**
- sm: 12px 16px
- md: 16px 20px
- lg: 20px 28px

**Min tap target:** 44×44px (all sizes include internal spacing)

---

### 7.2 Input

**Variants:** `text`, `email`, `number`, `tel`, `search`, `password`, `textarea`, `select`

**Sizes:** `sm` (32px), `md` (40px), `lg` (48px)

**States:** default, hover, focus, error, disabled, placeholder

#### States

| State | Border | Background | Ring |
|---|---|---|---|
| default | `var(--input-border)` | `var(--input-bg)` | none |
| hover | `var(--border-dark)` | same | none |
| focus | `var(--input-border-focus)` | same | `0 0 0 3px rgba(212, 255, 63, 0.2)` |
| error | `var(--color-red-600)` | same | `0 0 0 3px rgba(239, 68, 68, 0.2)` |
| disabled | `var(--border)` | `var(--color-ink-100)` | none |

**Padding:**
- sm: 8px 12px (font 14px)
- md: 10px 14px (font 14px)
- lg: 12px 16px (font 16px)

**Placeholder color:** `var(--foreground-muted)`

**Label → Caption (12px, 600 weight), margin-bottom 8px**

---

### 7.3 Card

**Variants:** `default`, `interactive`, `elevated`, `section`

#### Default

```css
background: var(--card-bg);
border: 1px solid var(--card-border);
border-radius: var(--radius-lg);
box-shadow: var(--card-shadow);
padding: 24px;
```

#### Interactive (Hover effect)

```css
same as default, but:
transition: box-shadow var(--duration-normal) var(--ease-in-out),
            transform var(--duration-normal) var(--ease-in-out);

&:hover {
  box-shadow: var(--card-shadow-hover);
  transform: translateY(-2px);
}
```

#### Elevated

```css
box-shadow: var(--shadow-lg);
border: none;
```

#### Section

```css
background: var(--background-section);
border: none;
border-radius: var(--radius-xl);
padding: 32px;
```

---

### 7.4 Badge

**Variants:** `default`, `secondary`, `outline`, `destructive`, `success`

**Sizes:** `sm` (20px), `md` (24px), `lg` (28px)

#### Default

| State | Background | Text | Border |
|---|---|---|---|
| default | `var(--primary)` | `var(--primary-foreground)` | none |
| secondary | `var(--background-secondary)` | `var(--foreground)` | `var(--border)` |
| destructive | `var(--status-cancelled)` | white | none |
| success | `var(--status-completed)` | white | none |

**Padding:** sm (4px 8px), md (6px 10px), lg (8px 12px)

**Border-radius:** `var(--radius-full)` (pill)

---

### 7.5 Menu Card

Used in menu browse & admin menu management.

```
┌──────────────────────────────┐
│  [Image 160px × 120px]       │
├──────────────────────────────┤
│ Nasi Kuning Ayam Goreng   20 │
│ Ayam goreng, nasi kuning,... │
│ Stok: 12                      │
│                               │
│ Rp 35.000   [Tambah ke Cart]  │
└──────────────────────────────┘
```

**Layout:** 
- Image: 160px × 120px, `border-radius: var(--radius-md)`, object-fit cover
- Title: 16px, 600 weight
- Desc: 12px, muted color, 2 lines max
- Stock badge: 12px label, positioned top-right
- Price + Button: flex space-between, gap 12px

**States:**
- default: shadow-sm
- hover: shadow-md, scale 1.02

---

### 7.6 Pickup Time Selector

Used in cart checkout to choose order time.

```
┌─────────────────────────────────┐
│ Kapan mau ambil?                │
├─────────────────────────────────┤
│ ⊙ 12:00  (15 menit)             │
│ ◯ 13:00  (60 menit)             │
│ ◯ 14:00  (120 menit)            │
│ ◯ 15:00  (Stok terbatas)        │
└─────────────────────────────────┘
```

**Radio buttons:** 48px tap target, label clickable.

**Disabled state:** if time < current time or estimated wait > 2 hours.

---

### 7.7 Order Status Timeline

Used in order tracking page.

```
┌──────────────────────────────────┐
│   Menunggu Pembayaran            │
│   ⏳ 13:45                        │
│   Rp 45.000                      │
│                                  │
│   ↓                              │
│                                  │
│   Sudah Dibayar                  │
│   ✓ 14:05                        │
│   Pembayaran diterima            │
│                                  │
│   ↓                              │
│                                  │
│   Siap Diambil                   │
│   📦 14:15 (sekarang)            │
│   Ambil di counter kantin        │
│   [Mulai ke Kantin] [Selesai]    │
│                                  │
│   ↓                              │
│                                  │
│   Selesai                        │
│   (belum)                        │
└──────────────────────────────────┘
```

**Active step:** Lime accent, bold label, full icon.
**Inactive steps:** Muted color, smaller icon.
**Connector line:** Lime if completed, muted if pending.

**Status update (v1):** halaman tracking memuat ulang `GET /api/orders/:id` setiap 30 detik
selama terbuka, dan sekali lagi saat tab kembali aktif. Karena v1 tidak memakai push
notification, halaman tracking adalah satu-satunya cara user tahu status sudah `Siap Diambil` —
sertakan indikator visual kecil di header ("Diperbarui otomatis") supaya user tidak mengira
halaman tersebut statis. Status final (`completed`/`cancelled`) tidak berubah lagi, tetapi
refresh tetap berjalan selama halaman terbuka.

---

### 7.8 Quantity Stepper

Used in cart to adjust item qty.

```
[−]  3  [+]
```

**Buttons:** 36px × 36px, icon centered.
**Input:** 44px width, text-align center, readonly (user harus use +/− buttons).
**States:**
- default: primary buttons
- min qty (1): − button disabled
- max qty (10): + button disabled
- hover: shadow on buttons

---

### 7.9 Order Board Column (Admin)

Admin panel layout — orders grouped by pickup time slot.

```
┌─────────────────────────────┐
│ 12:00 | 4 orders ready      │
├─────────────────────────────┤
│ □ Budi | Nasi Kuning × 2    │
│   Ayam Goreng × 1           │
│   [Siap] [Selesai]          │
│                             │
│ □ Siti | Soto Ayam × 1      │
│   [Siap] [Selesai]          │
│                             │
│ □ Andi | Mie Goreng × 3     │
│   [Siap] [Selesai]          │
└─────────────────────────────┘
```

**Card per order:** 
- Name + items (item name × qty)
- Status badge
- [Siap] [Selesai] buttons below

**Drag-drop to reorder:** Optional nice-to-have (v2).

---

## 8. Screen Inventory & Wireframes

### 8.1 Mahasiswa Screens

#### Screen 1: Login

```
┌────────────────────────────┐
│                            │
│      [LOGO]                │ ← 64px lime
│                            │
│   Pre-Order Kantin Kampus  │ ← Display 2
│                            │
│   ┌──────────────────────┐ │
│   │ Email atau NIM       │ │
│   │ [________________]   │ │
│   └──────────────────────┘ │
│                            │
│   ┌──────────────────────┐ │
│   │ Password             │ │
│   │ [________________]   │ │
│   └──────────────────────┘ │
│                            │
│   [MASUK] ← accent button  │
│                            │
│   Belum punya akun?        │
│   [Daftar di sini]         │
│                            │
└────────────────────────────┘
```

**Screens:** Login, Register (similar), Forgot Password

---

#### Screen 2: Menu Browse

```
┌────────────────────────────┐
│ ← Menu                 ⋮   │ ← Header
├────────────────────────────┤
│ [Pencarian...]             │
│                            │
│ ┌────────────┐ ┌─────────┐│
│ │ [IMG]  20  │ │[IMG] 15 ││  ← Menu cards,
│ │ Nasi Kuning│ │Soto Ayam││     2 column
│ │ Stok: 12   │ │Stok: 8  ││
│ │ Rp 35.000  │ │Rp 25k   ││
│ │[+]         │ │[+]      ││
│ └────────────┘ └─────────┘│
│                            │
│ ┌────────────┐ ┌─────────┐│
│ │ [IMG]  5   │ │[IMG]  10││
│ │ Mie Goreng │ │Lumpia   ││
│ │ Stok: 0    │ │Stok: 5  ││
│ │ Rp 20.000  │ │Rp 15k   ││
│ │[+] disabled│ │[+]      ││
│ └────────────┘ └─────────┘│
│                            │
│ ┌──────────────────────┐   │
│ │ Total Keranjang: 0   │   │
│ │ [Lanjut ke Checkout] │   │
│ └──────────────────────┘   │
└────────────────────────────┘
```

---

#### Screen 3: Cart + Pickup Time

```
┌────────────────────────────┐
│ ← Keranjang              ⋮ │
├────────────────────────────┤
│ Item:                      │
│ □ Nasi Kuning × 2          │
│   Rp 70.000                │
│   [−] 2 [+]                │ ← Stepper
│                            │
│ □ Ayam Goreng × 1          │
│   Rp 25.000                │
│   [−] 1 [+]                │
│                            │
│ ────────────────────────── │
│ Total: Rp 95.000           │
│                            │
│ Kapan mau ambil?           │
│ ⊙ 12:00 (15 menit)         │ ← Radio selector
│ ◯ 13:00 (60 menit)         │
│ ◯ 14:00 (120 menit)        │
│                            │
│ [LANJUT KE PEMBAYARAN]     │
│                            │
└────────────────────────────┘
```

---

#### Screen 4: Payment / Success

```
┌────────────────────────────┐
│ ← Pembayaran             ⋮ │
├────────────────────────────┤
│ Total Bayar: Rp 95.000     │
│                            │
│ Metode Pembayaran:         │
│ ○ E-Wallet / QRIS          │
│ ○ Transfer Bank            │
│ ○ QRIS                     │
│                            │
│ [LANJUT KE MIDTRANS]       │ ← Redirect
│                            │
│                            │
│ [PEMBAYARAN BERHASIL ✓]    │ ← After redirect back
│                            │
│ Order ID: #ORD-241011-001  │
│ Total: Rp 95.000           │
│ Pickup: 12:00 (menit ini)  │
│                            │
│ [Lihat Status] [Kembali]   │
│                            │
└────────────────────────────┘
```

---

#### Screen 5: Order Tracking

```
┌────────────────────────────┐
│ ← Pesanan Saya           ⋮ │
├────────────────────────────┤
│                            │
│ Order #ORD-241011-001      │
│                            │
│ ●─ Sudah Dibayar          │ ← Timeline
│   ✓ 13:50                 │
│   Rp 95.000               │
│                            │
│ ●─ Siap Diambil           │ ← CURRENT (active)
│   📦 14:05                 │
│   Ambil di counter kantin  │
│   [LIHAT DETAIL]           │
│                            │
│ ○─ Selesai                │ ← Inactive
│                            │
│ ────────────────────────── │
│ Status terupdate otomatis  │
│                            │
│ [Sudah Ambil] [Batalkan]   │
│                            │
└────────────────────────────┘
```

---

### 8.2 Admin Screens

#### Screen A: Admin Login

```
┌────────────────────────────┐
│                            │
│    [ADMIN PANEL]           │
│                            │
│   ┌──────────────────────┐ │
│   │ Email admin          │ │
│   │ [________________]   │ │
│   └──────────────────────┘ │
│                            │
│   ┌──────────────────────┐ │
│   │ Password             │ │
│   │ [________________]   │ │
│   └──────────────────────┘ │
│                            │
│   [LOGIN ADMIN]            │
│                            │
└────────────────────────────┘
```

---

#### Screen B: Admin Order Board (Main)

```
┌──────────────────────────────────────────┐
│ Admin | Pesanan      ← 14:15 (10 pending)│ ⋮
├──────────────────────────────────────────┤
│ Filter: [12:00 v] [13:00] [14:00] [15:00]│
│                                          │
│ 12:00 SLOT (4 orders)                    │
│ ┌────────────────────────────────────┐  │
│ │ ⊞ Budi                        13:50│  │ ← Order card
│ │   Nasi Kuning × 2                  │  │
│ │   Ayam Goreng × 1                  │  │
│ │   [Siap] [Selesai]                 │  │
│ └────────────────────────────────────┘  │
│                                          │
│ ┌────────────────────────────────────┐  │
│ │ ⊞ Siti                        13:51│  │
│ │   Soto Ayam × 1                    │  │
│ │   [Siap] [Selesai]                 │  │
│ └────────────────────────────────────┘  │
│                                          │
│ 13:00 SLOT (3 orders)                    │
│ ┌────────────────────────────────────┐  │
│ │ ⊞ Andi                        13:52│  │
│ │   Mie Goreng × 3                   │  │
│ │   [Siap] [Selesai]                 │  │
│ └────────────────────────────────────┘  │
│                                          │
└──────────────────────────────────────────┘
```

---

#### Screen C: Admin Menu Management

```
┌──────────────────────────────────────────┐
│ Admin | Menu & Stok                    ⋮ │
├──────────────────────────────────────────┤
│ [+ Tambah Menu]                          │
│                                          │
│ ┌────────────────────────────────────┐  │
│ │ Nasi Kuning Ayam Goreng       20   │  │
│ │ Rp 35.000                          │  │
│ │ Stok: 12 [edit]                    │  │
│ │ Status: Aktif [edit]               │  │
│ │ [Edit] [Hapus]                     │  │
│ └────────────────────────────────────┘  │
│                                          │
│ ┌────────────────────────────────────┐  │
│ │ Mie Goreng                    5    │  │
│ │ Rp 20.000                          │  │
│ │ Stok: 0 [update]                   │  │
│ │ Status: Aktif [edit]               │  │
│ │ ⚠ Out of stock — restock soon      │  │
│ │ [Edit] [Hapus]                     │  │
│ └────────────────────────────────────┘  │
│                                          │
└──────────────────────────────────────────┘
```

---

## 9. Implementasi CSS Aktual

> Section ini menggantikan konfigurasi Tailwind lama. Implementasi memakai CSS polos tanpa build step.

- **File utama:** `frontend/css/styles.css`.
- **Token:** primitives, semantic, dan component token dari section 3–5 didefinisikan sebagai CSS
  variables di `:root`.
- **Layout primitives:** kelas `od-*` (`od-screen`, `od-stack`, `od-row`, `od-grid`, `od-rail`,
  `od-scroll`, `od-touch`) dipakai untuk struktur halaman.
- **Kelas komponen aktual:**
  - Tombol: `.btn` + modifier `.btn--primary`, `.btn--accent`, `.btn--secondary`, `.btn--ghost`,
    `.btn--danger`, `.btn--sm`, `.btn--lg`, `.btn--block`.
  - Form: `.field`, `.field__label`, `.input`, `.input--error`, `.field__error`.
  - Permukaan: `.card`, `.card--section`, `.badge`, `.chip`.
  - Menu: `.menu-grid`, `.dish`, `.dish__name`, `.dish__price`.
  - Keranjang/checkout: `.cartbar`, `.summary-row`, `.pick__time`.
  - Tracking: `.tl`, `.tl__node`, `.tl__conn`, `.tl__title`.
  - Navigasi: `.appbar`, `.tabbar`.
- **Font:** Space Grotesk (display) dan Inter (body), dimuat dari Google Fonts di setiap HTML;
  variabel `--font-display` dan `--font-body`.
- **Ikon:** SVG inline dari `frontend/js/app.js` melalui `icon(name, size)`, bukan Lucide React.
- **Tidak ada:** `tailwind.config.ts`, `globals.css`, atau proses build.
- **Dark mode:** belum diimplementasikan di v1; token dark pada section 3 disimpan sebagai rencana v2.

---

## 10. Accessibility Guidelines

### Color & Contrast
- **Normal text:** min 4.5:1 ratio (WCAG AA). All body text on background = 12:1.
- **UI components:** min 3:1 ratio (WCAG AA).
- **Status colors:** Always pair with icon or text label, never rely on color alone.

### Touch & Mobile
- **Tap targets:** min 44×44px (44 dp, ~11mm).
- **Spacing:** min 8px gap between interactive elements.
- **Thumb zone:** Place critical actions (confirm, submit) in bottom 50% on phone.

### Keyboard Navigation
- **Focus indicator:** Visible ring, min 2px, contrast 3:1.
- **Tab order:** Follow logical reading order, left-to-right, top-to-bottom.
- **Escape key:** Close modals, dialogs.
- **Enter key:** Submit forms, confirm actions.

### Screen Reader Support
- **ARIA labels:** Button & icon buttons must have `aria-label`.
- **Form labels:** `<label>` or `aria-label` for all inputs.
- **Status messages:** Use `aria-live="polite"` for dynamic updates (order status).
- **Semantic HTML:** `<button>`, `<input>`, `<nav>`, `<main>`, `<aside>`.

### Motion & Animations
- **Respect `prefers-reduced-motion`:** sudah diterapkan di `frontend/css/styles.css`.
- **Duration:** min 150ms, max 400ms. No flashing (>3 per second).
- **Easing:** Use ease-out or ease-in-out, avoid linear.

### Dark Mode
- **Status v1:** belum diimplementasikan di CSS aktual.
- **Default:** Light theme (canvas white, ink dark).
- **Rencana v2:** menambahkan override `@media (prefers-color-scheme: dark)` memakai token yang
  sudah disiapkan di section 3, lalu audit kontras.
- **Toggle manual:** fitur v2, bukan v1.

---

## 11. Implementation Checklist

### Phase 1: Setup & Tokens — selesai
- [x] Token CSS variables (primitives, semantic, component) di `frontend/css/styles.css`.
- [x] Layout primitives `od-*`.
- [x] Font Space Grotesk + Inter dari Google Fonts.
- [ ] Dark mode (v2).

### Phase 2: Components — selesai
- [x] Button (primary, accent, secondary, ghost, danger; ukuran sm/lg/block).
- [x] Input, field, dan pesan error.
- [x] Card, badge, chip.
- [x] Menu card (gambar + judul + harga + aksi).
- [x] Pickup time selector (radio group).
- [x] Quantity stepper (−/nilai/+).

### Phase 3: Screens — selesai
- [x] Login & register.
- [x] Menu browse (grid 2 kolom, pencarian, filter kategori).
- [x] Cart + checkout + pembayaran.
- [x] Order tracking (timeline).
- [x] Admin order board.
- [x] Admin menu & stock management.

### Phase 4: Polish — sebagian
- [ ] Accessibility audit formal (kontras, tap target, keyboard nav, ARIA).
- [ ] Mobile responsive testing (320px, 768px, 1024px).
- [x] Reduced-motion support.
- [ ] Dark mode.
- [ ] Performance: Lighthouse score >90.

---

## 12. Asset References

### Fonts
- Display: **Space Grotesk** (600, 700, 800) — dimuat dari Google Fonts di setiap HTML.
- Body: **Inter** (400, 500, 600) — dimuat dari Google Fonts.

### Icons
- Source: SVG inline di `frontend/js/app.js` (`icon(name, size)`), gaya Lucide 24×24.
- Color: Inherit from text color or status token.
- Strokes: 2px default.

### Images
- Menu photos: object-fit cover, border-radius mengikuti token radius.
- Placeholder: SVG fallback di `frontend/js/api.js` bila gambar tidak ditemukan.
- Foto seed yang belum tersedia dipetakan ke aset terdekat lewat alias di `api.js`.

---

## Questions & Clarifications

**For developers:**
- All color values use CSS variables—no hardcoding hex.
- Tidak ada Tailwind; gunakan kelas komponen yang sudah ada di `frontend/css/styles.css`.
- Component specs are *reference*—nama kelas aktual ada di section 9.
- Wireframes are ASCII mockups for layout intent; actual UX may vary per screen.

**For designers:**
- Token architecture enables theme switching (light/dark, brand variants).
- Status system is semantic: color + icon + label always together.
- Motion defaults to 250ms ease-out; override for special cases.

---

End of DESIGN.md
