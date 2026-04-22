# KeyLicense Design Tokens

Bộ design tokens này dành cho **KeyLicense Admin** với định hướng **premium dark admin UI**, lấy cảm hứng từ tinh thần màu tối + accent vàng ấm của minepi.com nhưng tránh sao chép trực tiếp brand palette của Pi Network.[cite:80][cite:81]

## Design direction

- Tone: dark, secure, premium, enterprise
- Visual mood: công nghệ, đáng tin cậy, tập trung vào dashboard/admin workflows
- Accent strategy: một màu vàng ấm cho CTA/focus, một tông plum tím đậm cho chiều sâu background và selected states, còn lại là neutral surfaces để UI không bị loè loẹt.[cite:49][cite:58]

## Core palette

| Token                 | Value                    | Mục đích                     |
| --------------------- | ------------------------ | ---------------------------- |
| `--kl-bg`             | `#0B1020`                | Nền ứng dụng chính           |
| `--kl-surface`        | `#12182B`                | Card, sidebar, topbar        |
| `--kl-surface-2`      | `#1A2238`                | Input, dropdown, panel lồng  |
| `--kl-surface-3`      | `#222C47`                | Hover surface, nested area   |
| `--kl-border`         | `rgba(255,255,255,0.08)` | Border chính                 |
| `--kl-border-strong`  | `rgba(255,255,255,0.14)` | Border active/focus nhẹ      |
| `--kl-primary`        | `#E8B04B`                | CTA chính, focus, highlight  |
| `--kl-primary-hover`  | `#F2C469`                | Hover cho primary            |
| `--kl-primary-active` | `#D39A32`                | Active/pressed state         |
| `--kl-primary-soft`   | `rgba(232,176,75,0.14)`  | Background badge/active tint |
| `--kl-plum`           | `#5C3B8A`                | Chiều sâu brand phụ          |
| `--kl-plum-soft`      | `#7A55AA`                | Hover/gradient phụ           |
| `--kl-plum-tint`      | `rgba(92,59,138,0.18)`   | Selected nav/card tint       |
| `--kl-text`           | `#F8FAFC`                | Text chính                   |
| `--kl-text-soft`      | `#B6C0D4`                | Text phụ                     |
| `--kl-text-muted`     | `#7C879D`                | Hint, metadata               |
| `--kl-text-faint`     | `#5D6780`                | Placeholder, footer phụ      |
| `--kl-success`        | `#2FBF71`                | Success state                |
| `--kl-warning`        | `#F59E0B`                | Warning state                |
| `--kl-danger`         | `#F87171`                | Error/danger state           |
| `--kl-info`           | `#60A5FA`                | Info state                   |

## Semantic usage

### Background & surfaces

- `--kl-bg`: body, app shell, canvas chính
- `--kl-surface`: login card, sidebar, topbar, modal nền chính
- `--kl-surface-2`: input field, search box, table row expanded, select menu
- `--kl-surface-3`: hover row, active filter container, secondary highlighted area

### Text

- `--kl-text`: heading, label chính, số liệu KPI
- `--kl-text-soft`: paragraph, nav link bình thường, table body
- `--kl-text-muted`: helper text, timestamps, captions, footer
- `--kl-text-faint`: placeholder, disabled description, separator text

### Brand colors

- `--kl-primary`: nút chính, link hover, checkbox/radio selected, focus border
- `--kl-primary-soft`: badge nền vàng nhẹ, active tab nền nhẹ, selected pill
- `--kl-plum`: radial glow nền, chart accent phụ, selected nav mềm
- `--kl-plum-tint`: active background cho nav hoặc card nhấn mạnh

## CSS variables

```css
:root {
    --kl-bg: #0b1020;
    --kl-surface: #12182b;
    --kl-surface-2: #1a2238;
    --kl-surface-3: #222c47;

    --kl-border: rgba(255, 255, 255, 0.08);
    --kl-border-strong: rgba(255, 255, 255, 0.14);

    --kl-primary: #e8b04b;
    --kl-primary-hover: #f2c469;
    --kl-primary-active: #d39a32;
    --kl-primary-soft: rgba(232, 176, 75, 0.14);

    --kl-plum: #5c3b8a;
    --kl-plum-soft: #7a55aa;
    --kl-plum-tint: rgba(92, 59, 138, 0.18);

    --kl-text: #f8fafc;
    --kl-text-soft: #b6c0d4;
    --kl-text-muted: #7c879d;
    --kl-text-faint: #5d6780;

    --kl-success: #2fbf71;
    --kl-warning: #f59e0b;
    --kl-danger: #f87171;
    --kl-info: #60a5fa;

    --kl-radius-sm: 0.5rem;
    --kl-radius-md: 0.875rem;
    --kl-radius-lg: 1.25rem;
    --kl-radius-xl: 1.75rem;
    --kl-radius-pill: 9999px;

    --kl-shadow-sm: 0 8px 24px rgba(0, 0, 0, 0.18);
    --kl-shadow-md: 0 18px 45px rgba(0, 0, 0, 0.32);
    --kl-shadow-lg: 0 24px 80px rgba(0, 0, 0, 0.45);

    --kl-ring-primary: 0 0 0 3px rgba(232, 176, 75, 0.18);
    --kl-ring-danger: 0 0 0 3px rgba(248, 113, 113, 0.18);
}
```

## Tailwind config

```js
// tailwind.config.js
export default {
    theme: {
        extend: {
            colors: {
                kl: {
                    bg: "#0B1020",
                    surface: "#12182B",
                    surface2: "#1A2238",
                    surface3: "#222C47",
                    border: "rgba(255,255,255,0.08)",
                    borderStrong: "rgba(255,255,255,0.14)",
                    primary: "#E8B04B",
                    primaryHover: "#F2C469",
                    primaryActive: "#D39A32",
                    primarySoft: "rgba(232,176,75,0.14)",
                    plum: "#5C3B8A",
                    plumSoft: "#7A55AA",
                    plumTint: "rgba(92,59,138,0.18)",
                    text: "#F8FAFC",
                    textSoft: "#B6C0D4",
                    muted: "#7C879D",
                    faint: "#5D6780",
                    success: "#2FBF71",
                    warning: "#F59E0B",
                    danger: "#F87171",
                    info: "#60A5FA",
                },
            },
            borderRadius: {
                "kl-sm": "0.5rem",
                "kl-md": "0.875rem",
                "kl-lg": "1.25rem",
                "kl-xl": "1.75rem",
                "kl-pill": "9999px",
            },
            boxShadow: {
                "kl-sm": "0 8px 24px rgba(0, 0, 0, 0.18)",
                "kl-md": "0 18px 45px rgba(0, 0, 0, 0.32)",
                "kl-lg": "0 24px 80px rgba(0, 0, 0, 0.45)",
            },
        },
    },
};
```

## Component tokens

### Login page

```css
--login-bg: var(--kl-bg);
--login-card-bg: rgba(18, 24, 43, 0.88);
--login-card-border: var(--kl-border);
--login-logo-from: var(--kl-primary);
--login-logo-to: var(--kl-primary-hover);
--login-glow-left: rgba(232, 176, 75, 0.1);
--login-glow-right: rgba(92, 59, 138, 0.12);
```

### Sidebar

```css
--sidebar-bg: #0e1424;
--sidebar-border: var(--kl-border);
--sidebar-text: var(--kl-text-soft);
--sidebar-text-hover: var(--kl-text);
--sidebar-item-hover: rgba(255, 255, 255, 0.04);
--sidebar-item-active-bg: linear-gradient(
    90deg,
    rgba(232, 176, 75, 0.12),
    rgba(92, 59, 138, 0.12)
);
--sidebar-item-active-border: rgba(232, 176, 75, 0.18);
--sidebar-item-active-text: var(--kl-primary);
```

### Cards

```css
--card-bg: rgba(18, 24, 43, 0.92);
--card-border: var(--kl-border);
--card-title: var(--kl-text);
--card-body: var(--kl-text-soft);
--card-muted: var(--kl-text-muted);
--card-hover-bg: rgba(26, 34, 56, 0.96);
--card-shadow: var(--kl-shadow-md);
```

### Inputs

```css
--input-bg: var(--kl-surface-2);
--input-border: var(--kl-border);
--input-border-hover: var(--kl-border-strong);
--input-border-focus: var(--kl-primary);
--input-text: var(--kl-text);
--input-placeholder: var(--kl-text-faint);
--input-ring-focus: var(--kl-ring-primary);
--input-ring-danger: var(--kl-ring-danger);
```

### Buttons

```css
--btn-primary-bg: linear-gradient(
    135deg,
    var(--kl-primary),
    var(--kl-primary-hover)
);
--btn-primary-text: #0b1020;
--btn-primary-shadow: 0 12px 24px rgba(232, 176, 75, 0.18);
--btn-primary-hover: brightness(1.04);

--btn-secondary-bg: rgba(255, 255, 255, 0.04);
--btn-secondary-border: var(--kl-border);
--btn-secondary-text: var(--kl-text);

--btn-ghost-bg-hover: rgba(255, 255, 255, 0.05);
--btn-ghost-text: var(--kl-text-soft);

--btn-danger-bg: rgba(248, 113, 113, 0.12);
--btn-danger-border: rgba(248, 113, 113, 0.24);
--btn-danger-text: #fca5a5;
```

### Badges & status

```css
--badge-warning-bg: rgba(232, 176, 75, 0.14);
--badge-warning-text: #f8d48a;

--badge-success-bg: rgba(47, 191, 113, 0.14);
--badge-success-text: #86efac;

--badge-danger-bg: rgba(248, 113, 113, 0.14);
--badge-danger-text: #fca5a5;

--badge-info-bg: rgba(96, 165, 250, 0.14);
--badge-info-text: #93c5fd;
```

## Typography tokens

```css
:root {
    --kl-font-sans: "Figtree", "Inter", sans-serif;

    --kl-text-xs: 0.75rem;
    --kl-text-sm: 0.875rem;
    --kl-text-base: 1rem;
    --kl-text-lg: 1.125rem;
    --kl-text-xl: 1.25rem;
    --kl-text-2xl: 1.5rem;

    --kl-leading-tight: 1.2;
    --kl-leading-normal: 1.5;
    --kl-leading-relaxed: 1.7;
}
```

## Spacing tokens

```css
:root {
    --kl-space-1: 0.25rem;
    --kl-space-2: 0.5rem;
    --kl-space-3: 0.75rem;
    --kl-space-4: 1rem;
    --kl-space-5: 1.25rem;
    --kl-space-6: 1.5rem;
    --kl-space-8: 2rem;
    --kl-space-10: 2.5rem;
    --kl-space-12: 3rem;
}
```

## Ready-to-use utility mapping

### Page shell

```blade
<body class="min-h-screen bg-kl-bg text-kl-text font-sans antialiased">
```

### Standard card

```blade
<div class="rounded-kl-xl border border-kl-border bg-kl-surface/90 shadow-kl-lg backdrop-blur-xl">
```

### Standard input

```blade
<input class="w-full rounded-kl-lg border border-kl-border bg-kl-surface2 px-4 py-3 text-sm text-kl-text placeholder:text-kl-faint focus:border-kl-primary focus:ring-2 focus:ring-kl-primary/20">
```

### Primary button

```blade
<button class="inline-flex items-center justify-center rounded-kl-pill bg-linear-to-r from-kl-primary to-kl-primaryHover px-4 py-3 text-sm font-semibold text-kl-bg shadow-[0_12px_24px_rgba(232,176,75,0.18)] transition hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-kl-primary/30">
```

### Active nav item

```blade
<a class="border border-[rgba(232,176,75,0.18)] bg-[linear-gradient(90deg,rgba(232,176,75,0.12),rgba(92,59,138,0.12))] text-kl-primary">
```

## Usage rules

- Chỉ dùng **1 accent chính** là gold trong CTA, focus, selected state quan trọng; không để vàng xuất hiện mọi nơi vì UI sẽ mất sang trọng.[cite:58][cite:54]
- Plum chỉ dùng như màu phụ cho chiều sâu nền, selected nav nhẹ, chart phụ hoặc glow background, không dùng thay cho primary CTA.[cite:49]
- Mọi card, menu, modal và dropdown phải bám cùng một hệ `surface -> surface-2 -> surface-3` để giữ cảm giác thống nhất.[cite:56][cite:52]
- Border nên mảnh và alpha thấp; nếu cần nhấn mạnh, ưu tiên tăng contrast nền hoặc shadow thay vì border dày.[cite:54]
- Text hierarchy chỉ nên có 4 cấp: primary, soft, muted, faint; tránh sinh thêm nhiều sắc xám khác nhau.

## Recommendation for next implementation

Ưu tiên áp dụng bộ token này theo thứ tự:

1. `tailwind.config.js`
2. `x-ui.card`, `x-ui.input`, `x-ui.button`, `x-admin.nav-item`
3. `layouts/admin.blade.php`
4. `auth/login.blade.php`
5. Dashboard cards, tables, badges

Sau khi áp dụng xong, toàn bộ hệ thống sẽ có một visual language đồng nhất: dark enterprise, có brand gold rõ ràng, và vẫn đủ mềm mại để nhìn hiện đại thay vì khô cứng.[cite:49][cite:58]
