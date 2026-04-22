**mini design system** cho KeyLicense Admin: từ login, menu, card tới layout – thống nhất màu, spacing, radius, typography. Mục tiêu: dễ code với Tailwind, dễ maintain, nhìn “enterprise”.

---

## 1. Design tokens (màu, font, spacing, radius)

Trước khi bàn tới màn hình, mình chốt “ngôn ngữ” chung:

### Màu (semantic)

```js
// tailwind.config.js (extend)
theme: {
  extend: {
    colors: {
      kl: {
        bg: '#050814',        // nền app
        surface: '#070c1b',   // mặt card/menu
        border: 'rgba(148, 163, 184, 0.2)',
        primary: '#F8B803',
        primarySoft: '#fed24a',
        text: {
          DEFAULT: '#F9FAFB',
          subtle: '#9CA3AF',
          muted: '#6B7280',
          danger: '#F97373',
        }
      }
    },
    borderRadius: {
      card: '1rem',      // 16px
      pill: '999px',
    },
    boxShadow: {
      card: '0 18px 45px rgba(0,0,0,0.35)',
    }
  }
}
```

- Chỉ 1 nền tối, 1 surface, 1 primary → UI **rất đồng nhất**. [tailadmin](https://tailadmin.com/docs/customizations)
- Radius: dùng `rounded-card` cho mọi card/menu, `rounded-pill` cho button chính.

### Typography & spacing

- Font: Figtree như Tu đang dùng, base `text-sm`, heading `text-xl` đến `text-2xl` cho admin.
- Spacing scale: cứ bám Tailwind `4, 6, 8` (1rem = 4, 1.5rem = 6, 2rem = 8) cho padding/gap card. [alfdesigngroup](https://www.alfdesigngroup.com/post/best-practices-to-design-ui-cards-for-your-website)

---

## 2. Login page chuẩn

Login nên là **một card ở giữa**, không hero rườm rà (admin portal). Anh rút gọn:

```blade
{{-- resources/views/auth/admin-login.blade.php --}}
<x-guest-layout>
    <div class="min-h-screen bg-kl-bg flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            {{-- Logo + tagline --}}
            <div class="mb-6 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-linear-to-r from-kl-primary to-kl.primarySoft text-base font-black text-kl-bg shadow-md shadow-kl-primary/40">
                    K
                </div>
                <p class="mt-3 text-xs text-kl-text-muted">
                    Đăng nhập để quản trị license, sản phẩm và activation.
                </p>
            </div>

            {{-- Card --}}
            <div class="rounded-card border border-kl-border bg-kl-surface/95 p-6 shadow-card backdrop-blur">
                @if ($errors->any())
                    <x-ui.alert type="danger" class="mb-4">
                        Không thể đăng nhập. Vui lòng kiểm tra lại email hoặc mật khẩu.
                    </x-ui.alert>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                    @csrf

                    <x-form.input
                        name="email"
                        type="email"
                        label="Email đăng nhập"
                        placeholder="admin@keylicense.com.vn"
                        autocomplete="username"
                        :value="old('email')"
                        required
                    />

                    <x-form.password
                        name="password"
                        label="Mật khẩu"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        :hasForgot="Route::has('password.request')"
                        :forgotUrl="route('password.request')"
                    />

                    <div class="flex items-center justify-between gap-3">
                        <x-form.checkbox
                            name="remember"
                            label="Ghi nhớ đăng nhập"
                        />
                        <span class="text-[11px] text-kl-text-muted">
                            Admin access only
                        </span>
                    </div>

                    <x-ui.button type="submit" class="w-full">
                        Đăng nhập
                    </x-ui.button>
                </form>

                <p class="mt-5 text-center text-[11px] text-kl-text-muted">
                    Bảo mật bởi KeyLicense · Chỉ đăng nhập trên thiết bị tin cậy.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
```

Điểm quan trọng:

- Dùng component `<x-form.input>`, `<x-form.password>`, `<x-ui.button>` để tái sử dụng cho **mọi form** sau này.
- Card login đã dùng **màu + radius + shadow chuẩn**, sau này card dashboard dùng lại y chang. [tailkits](https://tailkits.com/components/categories/admin-dashboard/)

---

## 3. Main layout + menu (shell)

Dùng 1 layout cho tất cả trang admin:

```blade
{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'KeyLicense Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-kl-bg text-kl-text">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden md:flex md:w-64 flex-col border-r border-kl-border bg-[#050816]">
        <div class="flex h-16 items-center gap-2 px-5 border-b border-kl-border/60">
            <div class="flex h-9 w-9 items-center justify-center rounded-2xl  fbg-linear-to-r not-last-of-type:rom-kl-primary to-kl-primarySoft text-sm font-black text-kl-bg shadow-md shadow-kl-primary/40">
                K
            </div>
            <div>
                <div class="text-sm font-semibold">KeyLicense</div>
                <div class="text-[11px] text-kl-text-muted">Admin Portal</div>
            </div>
        </div>

        <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
            <x-admin.nav-item icon="heroicon-o-home" route="admin.dashboard">
                Dashboard
            </x-admin.nav-item>

            <x-admin.nav-label>License management</x-admin.nav-label>

            <x-admin.nav-item icon="heroicon-o-key" route="admin.licenses.index">
                Licenses
            </x-admin.nav-item>

            <x-admin.nav-item icon="heroicon-o-cube" route="admin.products.index">
                Products
            </x-admin.nav-item>

            <x-admin.nav-item icon="heroicon-o-bolt" route="admin.activations.index">
                Activations
            </x-admin.nav-item>

            <x-admin.nav-label>Security & audit</x-admin.nav-label>

            <x-admin.nav-item icon="heroicon-o-shield-check" route="admin.audit.index">
                Audit logs
            </x-admin.nav-item>
        </nav>

        <div class="border-t border-kl-border px-3 py-3 text-xs text-kl-text-muted">
            <div class="flex items-center justify-between">
                <span>{{ auth()->user()->email ?? 'admin@keylicense.com' }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="text-[11px] text-kl-text-muted hover:text-kl-primary">
                        Đăng xuất
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Top bar --}}
        <header class="flex h-16 items-center justify-between border-b border-kl-border bg-[#050816]/90 px-4 md:px-6">
            <h1 class="text-base font-semibold truncate">
                {{ $pageTitle ?? 'Dashboard' }}
            </h1>
            <div class="flex items-center gap-4 text-xs text-kl-text-muted">
                <span>{{ now()->format('d/m/Y') }}</span>
                <span class="hidden sm:inline">KeyLicense Admin</span>
            </div>
        </header>

        {{-- Content area --}}
        <main class="flex-1 px-4 py-6 md:px-6 bg-kl-bg/95">
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
```

- Sidebar và header dùng cùng **surface, border, typography** → tất cả màn hình trông chung một hệ. [tailadmin](https://tailadmin.com)
- `<x-admin.nav-item>` là component để tự động handle `active`, icon, màu hover.

---

## 4. Card & layout chuẩn cho các màn hình

### Card component

```blade
{{-- resources/views/components/ui/card.blade.php --}}
@props(['title' => null, 'subtitle' => null, 'actions' => null])

<section {{ $attributes->class('rounded-card border border-kl-border bg-kl-surface/95 p-5 shadow-card') }}>
    @if($title || $actions)
        <header class="mb-4 flex items-center justify-between gap-3">
            <div>
                @if($title)
                    <h2 class="text-sm font-semibold text-kl-text">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="mt-0.5 text-xs text-kl-text-muted">{{ $subtitle }}</p>
                @endif
            </div>
            @if($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </header>
    @endif

    <div class="text-sm text-kl-text">
        {{ $slot }}
    </div>
</section>
```

- Tất cả card (statistics, bảng, form chi tiết) đều dùng component này, đảm bảo **radius, shadow, padding, font size giống nhau**. [blog.logrocket](https://blog.logrocket.com/ux-design/ui-card-design/)

### Layout lưới card

Ví dụ dashboard:

```blade
{{-- resources/views/admin/dashboard.blade.php --}}
<x-admin-layout :pageTitle="'Dashboard'">
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-4">
        <x-ui.card title="Active licenses" subtitle="Trong 30 ngày gần đây">
            <div class="flex items-end justify-between">
                <span class="text-2xl font-semibold">1,284</span>
                <span class="text-xs text-emerald-400">+12.3% vs. last 30 days</span>
            </div>
        </x-ui.card>

        <x-ui.card title="Expired licenses" subtitle="Cần được gia hạn">
            <div class="flex items-end justify-between">
                <span class="text-2xl font-semibold">87</span>
                <span class="text-xs text-rose-400">+4.1%</span>
            </div>
        </x-ui.card>

        <!-- thêm 2 card nữa tương tự -->
    </div>

    <div class="grid gap-4 lg:grid-cols-[2fr_1.4fr]">
        <x-ui.card title="Recent activations" subtitle="20 activation gần nhất">
            {{-- table --}}
        </x-ui.card>

        <x-ui.card title="Top products" subtitle="Theo số lượng license active">
            {{-- list / chart --}}
        </x-ui.card>
    </div>
</x-admin-layout>
```

Quy tắc:

- Luôn dùng `grid gap-4` / `gap-6` (8pt/12pt) cho khoảng cách giữa card.
- Card thì không bao giờ tự thêm margin dưới; để container quyết định spacing. [blog.logrocket](https://blog.logrocket.com/ux-design/ui-card-design/)

### Form layout (create/edit)

Dùng 1 pattern chung:

```blade
<x-admin-layout :pageTitle="'Create product'">
    <div class="max-w-4xl space-y-4">
        <x-ui.card title="Thông tin sản phẩm">
            <div class="grid gap-4 md:grid-cols-2">
                <x-form.input name="name" label="Tên sản phẩm" />
                <x-form.input name="slug" label="Mã/slug" />
                <x-form.select name="type" label="Loại license">
                    {{-- options --}}
                </x-form.select>
                <x-form.input name="max_devices" type="number" label="Số device tối đa" />
            </div>
        </x-ui.card>

        <x-ui.card title="Cấu hình cấp phép">
            {{-- các field khác --}}
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.button variant="ghost" href="{{ route('admin.products.index') }}">
                Hủy
            </x-ui.button>
            <x-ui.button type="submit">
                Lưu sản phẩm
            </x-ui.button>
        </div>
    </div>
</x-admin-layout>
```

---

## 5. Tóm lại: “bộ thiết kế chuẩn” cho KeyLicense Admin

- **Tokens**: màu `kl-bg`, `kl-surface`, `kl-border`, `kl-primary`, radius `rounded-card`, shadow `shadow-card`, typography cố định. [tailadmin](https://tailadmin.com/docs/customizations)
- **Patterns**:
    - Login: card giữa màn hình (1 template).
    - Shell: sidebar trái + header trên, content area `px-6 py-6`.
    - Card: `<x-ui.card>` dùng cho dashboard, list, detail.
    - Form field: `<x-form.input>`, `<x-form.select>`, `<x-form.checkbox>`, `<x-form.switch>`.
    - Button: `<x-ui.button>` với các variant (`primary`, `ghost`, `danger`).

Anh trả lời thẳng: **ưu tiên form components trước**, vì:

- Em đang làm login + sẽ đụng rất nhiều form (product, license, activation).
- Button & nav cũng dùng lại trong form, nên build form trước sẽ định hình luôn style cho toàn admin. [tailwindcss](https://tailwindcss.com/plus/ui-blocks/application-ui/elements/buttons)

Bên dưới anh viết chi tiết 3 component: `x-form.input`, `x-ui.button`, `x-admin.nav-item`. Em chỉ cần copy file vào `resources/views/components/...` là dùng được.

---

## 1. `x-form.input` – input text/email/number chuẩn

### File: `resources/views/components/form/input.blade.php`

```blade
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'autocomplete' => null,
    'value' => null,
    'required' => false,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div {{ $attributes->class('space-y-1.5') }}>
    @if($label)
        abel for="{{ $id }}" class="block text-xs font-medium text-slate-200">
            {{ $label }}
            @if($required)
                <span class="text-rose-400">*</span>
            @endif
        </label>
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if(!is_null($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
        @if($required) required @endif
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['class', 'id'])->merge([
            'class' =>
                'block w-full rounded-card border bg-[#050814] px-3 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 shadow-sm outline-none transition
                 border-white/10 focus:border-amber-400/70 focus:ring-2 focus:ring-amber-400/20
                 '.($error ? 'border-rose-500/80 focus:border-rose-500/80 focus:ring-rose-500/20' : ''),
        ]) }}
    />

    @if($error)
        <p class="text-xs text-rose-300">{{ $error }}</p>
    @endif
</div>
```

- Hỗ trợ: `name`, `label`, `type`, `placeholder`, `autocomplete`, `required`, `value`.
- Tự lấy `old($name, $value)`, tự highlight error bằng `$errors`.
- Style focus/error theo best practice Tailwind (ring + border riêng). [stackoverflow](https://stackoverflow.com/questions/67944186/how-to-prevent-tailwind-from-changing-a-text-input-fields-border-when-its-focu)

### Cách dùng

```blade
<x-form.input
    name="email"
    type="email"
    label="Email đăng nhập"
    placeholder="admin@keylicense.com.vn"
    autocomplete="username"
    required
/>

<x-form.input
    name="max_devices"
    type="number"
    label="Số thiết bị tối đa"
    class="md:col-span-1"
/>
```

---

## 2. `x-ui.button` – button primary/secondary/ghost

### File: `resources/views/components/ui/button.blade.php`

```blade
@props([
    'type' => 'button',
    'variant' => 'primary', // primary | secondary | ghost | danger
    'href' => null,
    'size' => 'md',         // sm | md
])

@php
    $base = 'inline-flex items-center justify-center font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-0';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-pill',
        'md' => 'px-4 py-2.5 text-sm rounded-pill',
    ];

    $variants = [
        'primary' => 'bg-gradient-to-r from-amber-400 to-[#F8B803] text-[#050814] shadow-lg shadow-amber-500/30 hover:translate-y-[0.5px] hover:brightness-105 focus-visible:ring-amber-400/40',
        'secondary' => 'bg-[#111827] text-slate-100 border border-white/10 hover:bg-[#1f2937] focus-visible:ring-slate-500/40 rounded-pill',
        'ghost' => 'text-slate-200 hover:bg-white/5 border border-transparent focus-visible:ring-slate-500/40 rounded-pill',
        'danger' => 'bg-rose-500 text-white hover:bg-rose-600 focus-visible:ring-rose-500/50 rounded-pill',
    ];

    $classes = $base.' '.$sizes[$size].' '.$variants[$variant].' '.$attributes->get('class');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
```

- Support dùng như link (`href`) hoặc button form.
- Variants để dùng chung toàn hệ thống, rất giống các library Tailwind UI / Flowbite. [tailwindcss](https://tailwindcss.com/plus/ui-blocks/application-ui/elements/buttons)

### Cách dùng

```blade
<x-ui.button type="submit" class="w-full">
    Đăng nhập
</x-ui.button>

<x-ui.button variant="secondary">
    Xuất CSV
</x-ui.button>

<x-ui.button variant="ghost" href="{{ route('admin.products.index') }}">
    Hủy
</x-ui.button>

<x-ui.button variant="danger" type="submit">
    Xóa license
</x-ui.button>
```

---

## 3. `x-admin.nav-item` – item menu sidebar có trạng thái active

### Ý tưởng

- Tự check `request()->routeIs($route)` để set active.
- Dùng icon tuỳ ý (ví dụ Heroicons SVG inline hoặc component riêng).
- Style active: nền hơi khác + border trái + màu text primary, theo best practice cho sidebar admin. [quackit](https://www.quackit.com/html/html_editors/scratchpad/?example=%2Ftailwind%2Fv4%2Fexamples%2Fsidebars%2Ftailwind_css_4_sidebar_with_active_menu_item)

### File: `resources/views/components/admin/nav-item.blade.php`

```blade
@props([
    'route',          // tên route, ví dụ 'admin.dashboard'
    'icon' => null,   // optional: tên icon để render
])

@php
    $isActive = request()->routeIs($route);

    $base = 'group flex items-center gap-2.5 rounded-card px-3 py-2 text-xs font-medium transition';
    $inactive = 'text-slate-300 hover:bg-white/5 hover:text-white';
    $active = 'bg-white/5 text-kl-primary border border-kl-border/60';

    $classes = $base.' '.($isActive ? $active : $inactive);
@endphp

<a href="{{ route($route) }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        {{-- Tu có thể thay block này bằng @svg hoặc component icon riêng --}}
        <x-dynamic-component
            :component="$icon"
            class="h-4 w-4 {{ $isActive ? 'text-kl-primary' : 'text-slate-400 group-hover:text-slate-200' }}"
        />
    @endif

    <span class="truncate">{{ $slot }}</span>
</a>
```

### Cách dùng trong sidebar

```blade
<nav class="flex-1 px-3 py-4 space-y-1 text-sm">
    <x-admin.nav-item icon="heroicon-o-home" route="admin.dashboard">
        Dashboard
    </x-admin.nav-item>

    <p class="mt-4 mb-1 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
        License management
    </p>

    <x-admin.nav-item icon="heroicon-o-key" route="admin.licenses.index">
        Licenses
    </x-admin.nav-item>

    <x-admin.nav-item icon="heroicon-o-cube" route="admin.products.index">
        Products
    </x-admin.nav-item>
</nav>
```

Nếu em chưa dùng `x-dynamic-component` cho icon, có thể tạm bỏ phần icon và chỉ render text.

---

## 4. Kết nối với layout

Sau khi thêm 3 component này, em có thể:

- Dùng `x-form.input` trong login, create/edit form.
- Dùng `x-ui.button` cho mọi button trong app.
- Dùng `x-admin.nav-item` trong layout sidebar đã bàn trước.

Điểm mạnh: giờ UI sẽ đồng nhất màu, radius, focus/error state theo đúng best practices của Tailwind form & button. [tailwindcss](https://tailwindcss.com/plus/ui-blocks/application-ui/elements/buttons)
Anh viết luôn `x-form.password` và `x-form.checkbox` cho đồng bộ với `x-form.input` / `x-ui.button`.

---

## 1. `x-form.password` – ô mật khẩu có “Quên mật khẩu?”

### File: `resources/views/components/form/password.blade.php`

```blade
@props([
    'name' => 'password',
    'label' => 'Mật khẩu',
    'placeholder' => '••••••••',
    'autocomplete' => 'current-password',
    'required' => true,
    'hasForgot' => false,
    'forgotUrl' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
@endphp

<div {{ $attributes->class('space-y-1.5') }}>
    <div class="flex items-center justify-between gap-3">
        <label for="{{ $id }}" class="block text-xs font-medium text-slate-200">
            {{ $label }}
            @if($required)
                <span class="text-rose-400">*</span>
            @endif
        </label>

        @if($hasForgot && $forgotUrl)
            <a href="{{ $forgotUrl }}" class="text-[11px] text-slate-400 transition hover:text-amber-300">
                Quên mật khẩu?
            </a>
        @endif
    </div>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="password"
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($required) required @endif
        placeholder="{{ $placeholder }}"
        {{ $attributes->except(['class', 'id'])->merge([
            'class' =>
                'block w-full rounded-card border bg-[#050814] px-3 py-2.5 text-sm text-slate-50 placeholder:text-slate-500 shadow-sm outline-none transition
                 border-white/10 focus:border-amber-400/70 focus:ring-2 focus:ring-amber-400/20
                 '.($error ? 'border-rose-500/80 focus:border-rose-500/80 focus:ring-rose-500/20' : ''),
        ]) }}
    />

    @if($error)
        <p class="text-xs text-rose-300">{{ $error }}</p>
    @endif
</div>
```

### Cách dùng

```blade
<x-form.password
    name="password"
    label="Mật khẩu"
    autocomplete="current-password"
    :hasForgot="Route::has('password.request')"
    :forgotUrl="route('password.request')"
/>
```

Login của em giờ chỉ cần 1 dòng thay vì block input dài.

---

## 2. `x-form.checkbox` – checkbox chuẩn cho “Ghi nhớ đăng nhập”, flags, v.v.

### File: `resources/views/components/form/checkbox.blade.php`

```blade
@props([
    'name',
    'label' => null,
    'checked' => false,
])

@php
    $id = $attributes->get('id', $name);
@endphp

<label {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-xs text-slate-300 cursor-pointer']) }} for="{{ $id }}">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="checkbox"
        class="h-4 w-4 rounded border-white/20 bg-white/5 text-amber-400 shadow-sm focus:ring-amber-400/30"
        @checked(old($name, $checked))
    >
    @if($label)
        <span>{{ $label }}</span>
    @else
        <span class="capitalize">{{ str_replace('_', ' ', $name) }}</span>
    @endif
</label>
```

### Cách dùng

```blade
<x-form.checkbox
    name="remember"
    label="Ghi nhớ đăng nhập"
/>

<x-form.checkbox
    name="is_active"
    label="Kích hoạt sản phẩm"
    :checked="true"
/>
```

Checkbox này “drop-in” được cho mọi form, vẫn giữ style admin thống nhất. [tailkits](https://tailkits.com/blog/tailwind-forms-guide/)

---

## 3. Lắp vào login cho gọn

Trong view login, em thay:

```blade
<x-form.input
    name="email"
    type="email"
    label="Email đăng nhập"
    placeholder="admin@keylicense.com.vn"
    autocomplete="username"
    required
/>

<x-form.password
    name="password"
    label="Mật khẩu"
    autocomplete="current-password"
    :hasForgot="Route::has('password.request')"
    :forgotUrl="route('password.request')"
/>

<div class="flex items-center justify-between gap-3">
    <x-form.checkbox
        name="remember"
        label="Ghi nhớ đăng nhập"
    />
    <span class="text-[11px] text-slate-500">
        Secure admin access
    </span>
</div>

<x-ui.button type="submit" class="w-full">
    Đăng nhập
</x-ui.button>
```

Như vậy:

- Login, create/edit, tất cả form sau này đều dùng chung **3 component form + 1 button**, bảo đảm UI đồng nhất theo chuẩn Tailwind Forms/Buttons. [tailwindcss](https://tailwindcss.com/plus/ui-blocks/application-ui/elements/buttons)
- Khi muốn đổi style (màu border, radius, focus), em chỉ sửa ở file component.

Tu muốn anh tiếp theo build thêm `x-form.select` (dropdown) hay `x-ui.alert` (banner lỗi/thành công) trước cho các màn hình CRUD?
