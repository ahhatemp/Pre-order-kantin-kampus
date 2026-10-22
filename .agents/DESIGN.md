# Design System: Pre-Order Kantin Kampus

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

**Variants:** `default` (primary), `accent`, `secondary`, `outline`, `ghost`, `destructive`

**Sizes:** `sm` (32px), `md` (40px), `lg` (48px), `icon` (40px)

**States:** default, hover, active, focus, disabled, loading

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

**Status update (v1):** halaman tracking melakukan polling ke `GET /api/orders/:id` setiap 5 detik. Polling berhenti otomatis saat status `completed` atau `cancelled`. Karena v1 tidak memakai push notification, halaman tracking adalah satu-satunya cara user tahu status sudah `Siap Diambil` — tambahkan indikator visual kecil di header ("Diperbarui otomatis") supaya user tidak mengira halaman tersebut statis.

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
│ ○ E-Wallet (GCash, OVO)    │
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
│   │ Admin ID             │ │
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

## 9. Tailwind Configuration

### tailwind.config.ts

```typescript
import type { Config } from 'tailwindcss'

const config: Config = {
  darkMode: ['class'],
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Space Grotesk', 'sans-serif'],
        sans: ['Inter', 'sans-serif'],
      },
      colors: {
        background: 'hsl(var(--background) / <alpha-value>)',
        foreground: 'hsl(var(--foreground) / <alpha-value>)',
        primary: {
          DEFAULT: 'hsl(var(--primary) / <alpha-value>)',
          foreground: 'hsl(var(--primary-foreground) / <alpha-value>)',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent) / <alpha-value>)',
          foreground: 'hsl(var(--accent-foreground) / <alpha-value>)',
        },
        secondary: {
          DEFAULT: 'hsl(var(--background-secondary) / <alpha-value>)',
          foreground: 'hsl(var(--foreground) / <alpha-value>)',
        },
        muted: {
          DEFAULT: 'hsl(var(--background-secondary) / <alpha-value>)',
          foreground: 'hsl(var(--foreground-muted) / <alpha-value>)',
        },
        card: {
          DEFAULT: 'hsl(var(--card-bg) / <alpha-value>)',
        },
        border: 'hsl(var(--border) / <alpha-value>)',
        input: 'hsl(var(--input) / <alpha-value>)',
        ring: 'hsl(var(--ring) / <alpha-value>)',
        destructive: {
          DEFAULT: 'hsl(var(--status-cancelled) / <alpha-value>)',
          foreground: 'hsl(255 255 255 / <alpha-value>)',
        },
        status: {
          pending: 'hsl(var(--status-pending) / <alpha-value>)',
          paid: 'hsl(var(--status-paid) / <alpha-value>)',
          ready: 'hsl(var(--status-ready) / <alpha-value>)',
          completed: 'hsl(var(--status-completed) / <alpha-value>)',
          cancelled: 'hsl(var(--status-cancelled) / <alpha-value>)',
        },
      },
      borderRadius: {
        lg: 'var(--radius-lg)',
        md: 'var(--radius-md)',
        sm: 'var(--radius-sm)',
        xl: 'var(--radius-xl)',
        full: 'var(--radius-full)',
      },
      boxShadow: {
        xs: 'var(--shadow-xs)',
        sm: 'var(--shadow-sm)',
        md: 'var(--shadow-md)',
        lg: 'var(--shadow-lg)',
        xl: 'var(--shadow-xl)',
        'interactive-hover': 'var(--shadow-interactive-hover)',
      },
      spacing: {
        1: 'var(--space-1)',
        2: 'var(--space-2)',
        3: 'var(--space-3)',
        4: 'var(--space-4)',
        5: 'var(--space-5)',
        6: 'var(--space-6)',
        8: 'var(--space-8)',
        10: 'var(--space-10)',
        12: 'var(--space-12)',
      },
      transitionDuration: {
        fast: 'var(--duration-fast)',
        normal: 'var(--duration-normal)',
        slow: 'var(--duration-slow)',
      },
      keyframes: {
        'fade-in': {
          from: { opacity: '0' },
          to: { opacity: '1' },
        },
        'slide-up': {
          from: { transform: 'translateY(8px)', opacity: '0' },
          to: { transform: 'translateY(0)', opacity: '1' },
        },
        'pulse-lime': {
          '0%, 100%': { boxShadow: '0 0 0 0 rgba(212, 255, 63, 0.7)' },
          '50%': { boxShadow: '0 0 0 8px rgba(212, 255, 63, 0)' },
        },
      },
      animation: {
        'fade-in': 'fade-in 0.25s var(--ease-out)',
        'slide-up': 'slide-up 0.25s var(--ease-out)',
        'pulse-lime': 'pulse-lime 2s infinite',
      },
    },
  },
  plugins: [],
}

export default config
```

### globals.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  :root {
    /* Primitives */
    --color-white: #FFFDF7;
    --color-cream: #FFF9F0;
    --color-ink-900: #1A1614;
    --color-ink-700: #3D3530;
    --color-ink-500: #6B6157;
    --color-ink-300: #B8AFA5;
    --color-ink-200: #D9D3CD;
    --color-ink-100: #EFE9E2;

    --color-lime-600: #A8E01F;
    --color-lime-500: #D4FF3F;
    --color-coral-600: #FF5A3C;
    --color-coral-500: #FF7357;
    --color-violet-600: #7C4DFF;
    --color-violet-500: #9D6DFF;

    --color-amber-600: #FFB020;
    --color-blue-600: #3B82F6;
    --color-green-600: #10B981;
    --color-red-600: #EF4444;

    --color-surface-ink: #171412;
    --color-surface-light: #FAF7F2;
    --color-border: #E5E0DB;
    --color-border-dark: #D4CCC3;

    /* Semantic */
    --background: var(--color-white);
    --foreground: var(--color-ink-900);
    --primary: var(--color-lime-600);
    --primary-foreground: var(--color-ink-900);
    --accent: var(--color-coral-600);
    --accent-foreground: white;
    --background-secondary: var(--color-cream);
    --background-section: var(--color-surface-light);
    --background-dark: var(--color-surface-ink);
    --foreground-secondary: var(--color-ink-700);
    --foreground-muted: var(--color-ink-500);
    --foreground-subtle: var(--color-ink-300);

    --status-pending: var(--color-amber-600);
    --status-paid: var(--color-blue-600);
    --status-ready: var(--color-violet-600);
    --status-completed: var(--color-green-600);
    --status-cancelled: var(--color-red-600);

    --border: var(--color-border);
    --input: var(--color-white);
    --ring: var(--color-lime-600);
    --ring-offset: var(--color-white);

    /* Component */
    --button-primary-bg: var(--primary);
    --button-primary-fg: var(--primary-foreground);
    --button-primary-hover: var(--color-lime-500);
    --button-primary-active: #8FCB1A;

    --button-accent-bg: var(--accent);
    --button-accent-fg: var(--accent-foreground);
    --button-accent-hover: var(--color-coral-500);

    --button-subtle-bg: var(--background-secondary);
    --button-subtle-fg: var(--foreground);
    --button-subtle-hover: var(--color-ink-100);

    --card-bg: var(--background);
    --card-border: var(--border);
    --card-shadow: 0 2px 8px rgba(0 0 0 / 0.08);
    --card-shadow-hover: 0 8px 16px rgba(0 0 0 / 0.12);

    --input-bg: var(--input);
    --input-border: var(--border);
    --input-border-focus: var(--ring);
    --input-placeholder: var(--foreground-muted);

    /* Spacing */
    --space-1: 0.25rem;
    --space-2: 0.5rem;
    --space-3: 0.75rem;
    --space-4: 1rem;
    --space-5: 1.25rem;
    --space-6: 1.5rem;
    --space-8: 2rem;
    --space-10: 2.5rem;
    --space-12: 3rem;

    /* Radius */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --radius-full: 999px;

    /* Shadow */
    --shadow-xs: 0 1px 2px rgba(0 0 0 / 0.05);
    --shadow-sm: 0 2px 8px rgba(0 0 0 / 0.08);
    --shadow-md: 0 4px 12px rgba(0 0 0 / 0.1);
    --shadow-lg: 0 8px 20px rgba(0 0 0 / 0.12);
    --shadow-xl: 0 12px 32px rgba(0 0 0 / 0.15);
    --shadow-interactive-hover: 0 8px 16px rgba(212 255 63 / 0.2);

    /* Motion */
    --duration-fast: 150ms;
    --duration-normal: 250ms;
    --duration-slow: 400ms;
    --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
    --ease-out: cubic-bezier(0, 0, 0.2, 1);
    --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
  }

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

      --accent: var(--color-coral-500);
      --accent-hover: var(--color-coral-600);

      --input-bg: #262219;
      --border: #3D3530;
      --ring: var(--color-lime-500);
      --ring-offset: #1A1614;
    }
  }

  * {
    @apply border-border;
  }

  html {
    @apply scroll-smooth;
  }

  body {
    @apply bg-background text-foreground;
    font-feature-settings: 'rounding' 1;
  }
}

@layer components {
  /* Button Base */
  .btn {
    @apply inline-flex items-center justify-center rounded-md font-medium
           transition-all duration-normal ease-in-out
           focus-visible:outline-none focus-visible:ring-2
           focus-visible:ring-ring focus-visible:ring-offset-2
           disabled:pointer-events-none disabled:opacity-50
           cursor-pointer;
  }

  /* Button Variants */
  .btn-primary {
    @apply bg-primary text-primary-foreground hover:bg-primary/90
           active:bg-primary/80 disabled:bg-ink-100 disabled:text-ink-300;
  }

  .btn-accent {
    @apply bg-accent text-accent-foreground hover:bg-accent/90
           active:bg-accent/80;
  }

  .btn-secondary {
    @apply bg-secondary text-foreground border border-border
           hover:bg-secondary/80 active:bg-secondary/60;
  }

  .btn-outline {
    @apply border border-border text-foreground
           hover:bg-accent/10 active:bg-accent/20;
  }

  .btn-ghost {
    @apply text-foreground hover:bg-accent/10 active:bg-accent/20;
  }

  .btn-destructive {
    @apply bg-destructive text-destructive-foreground
           hover:bg-destructive/90 active:bg-destructive/80;
  }

  /* Button Sizes */
  .btn-sm {
    @apply h-8 px-3 text-xs;
  }

  .btn-md {
    @apply h-10 px-4 text-sm;
  }

  .btn-lg {
    @apply h-12 px-6 text-base;
  }

  .btn-icon {
    @apply h-10 w-10 p-0;
  }

  /* Card */
  .card {
    @apply rounded-lg border border-card bg-card shadow-sm;
  }

  .card-interactive {
    @apply transition-all duration-normal hover:shadow-md hover:-translate-y-0.5;
  }

  /* Input */
  .input {
    @apply flex h-10 w-full rounded-md border border-input bg-input px-3 py-2
           text-sm text-foreground placeholder:text-input-placeholder
           transition-colors duration-normal ease-in-out
           focus-visible:outline-none focus-visible:ring-2
           focus-visible:ring-ring focus-visible:ring-offset-2
           disabled:cursor-not-allowed disabled:opacity-50;
  }

  /* Badge */
  .badge {
    @apply inline-flex items-center rounded-full px-2.5 py-0.5
           text-xs font-medium border;
  }

  .badge-primary {
    @apply border-transparent bg-primary text-primary-foreground;
  }

  .badge-secondary {
    @apply border-border bg-secondary text-foreground;
  }

  .badge-destructive {
    @apply border-transparent bg-destructive text-destructive-foreground;
  }
}

/* Animations */
@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slide-up {
  from {
    transform: translateY(8px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@keyframes pulse-lime {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(212, 255, 63, 0.7);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(212, 255, 63, 0);
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

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
- **Respect `prefers-reduced-motion`:** See globals.css.
- **Duration:** min 150ms, max 400ms. No flashing (>3 per second).
- **Easing:** Use ease-out or ease-in-out, avoid linear.

### Dark Mode
- **Default:** Light theme (canvas white, ink dark).
- **Support:** Respect `prefers-color-scheme: dark`.
- **Toggle:** Optional user control (v2 feature).
- **Contrast maintained:** Dark theme overrides ensure 4.5:1 min.

---

## 11. Implementation Checklist

### Phase 1: Setup & Tokens
- [ ] Create `globals.css` with CSS variables (all primitives, semantic, component).
- [ ] Setup `tailwind.config.ts` with color, spacing, shadow extends.
- [ ] Setup `Space Grotesk` + `Inter` font imports (Google Fonts or Vercel).
- [ ] Test light & dark mode switching.

### Phase 2: Components
- [ ] Button (all 6 variants, 3 sizes, states).
- [ ] Input (text, email, password, textarea, select).
- [ ] Card (default, interactive, elevated, section).
- [ ] Badge (6 variants).
- [ ] Menu Card (image + title + price + action).
- [ ] Pickup Time Selector (radio group).
- [ ] Quantity Stepper (−/input/+).

### Phase 3: Screens
- [ ] Login & Register (mahasiswa + admin).
- [ ] Menu Browse (2-col grid, search).
- [ ] Cart + Checkout.
- [ ] Order Tracking (timeline).
- [ ] Admin Order Board.
- [ ] Admin Menu Management.

### Phase 4: Polish
- [ ] Accessibility audit (contrast, tap targets, keyboard nav, ARIA labels).
- [ ] Mobile responsive testing (320px, 768px, 1024px).
- [ ] Animation & motion testing (reduced-motion respect).
- [ ] Dark mode verification.
- [ ] Performance: Lighthouse score >90.

---

## 12. Asset References

### Fonts
- Display: **Space Grotesk** (700, 800) — [Google Fonts](https://fonts.google.com/specimen/Space+Grotesk)
- Body: **Inter** (400, 500, 600) — [Vercel Fonts](https://vercel.com/font)

### Icons
- Source: Lucide React (20px–24px standard).
- Color: Inherit from text color or status token.
- Strokes: 2px default.

### Images
- Menu photos: 160×120px, object-fit cover, border-radius 12px.
- Placeholder: Solid color band or shimmer skeleton.

---

## Questions & Clarifications

**For developers:**
- All color values use CSS variables—no hardcoding hex.
- Tailwind classes use token-backed colors (`bg-primary`, `text-accent`).
- Component specs are *reference*—shadcn/ui & custom components refine as needed.
- Wireframes are ASCII mockups for layout intent; actual UX may vary per screen.

**For designers:**
- Token architecture enables theme switching (light/dark, brand variants).
- Status system is semantic: color + icon + label always together.
- Motion defaults to 250ms ease-out; override for special cases.

---

End of DESIGN.md
