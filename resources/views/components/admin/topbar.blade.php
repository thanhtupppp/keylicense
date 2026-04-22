@props(['title' => null])

<header class="sticky top-0 z-20 border-b border-white/10 bg-[#0B1020]/90 backdrop-blur-xl">
    <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-4 px-4 sm:h-16 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-300 transition hover:border-white/25 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[#F8B803]/40 lg:hidden"
                aria-label="Mở menu quản trị"
                @click="openMobileSidebar()"
            >
                <span class="sr-only">Mở menu quản trị</span>
                <span class="flex flex-col gap-1.5">
                    <span class="block h-px w-4 bg-current"></span>
                    <span class="block h-px w-4 bg-current"></span>
                    <span class="block h-px w-4 bg-current"></span>
                </span>
            </button>

            <div class="min-w-0">
                @if ($title)
                    <h1 class="truncate text-sm font-semibold text-slate-100 sm:text-base">{{ $title }}</h1>
                @endif
                <p class="hidden text-[11px] text-slate-500 sm:block">KeyLicense Admin</p>
            </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            @if (trim($slot) !== '')
                <div class="hidden items-center gap-2 md:flex">
                    {{ $slot }}
                </div>
            @endif

            <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-2.5 py-1.5 text-[11px] text-slate-200 transition hover:border-white/20 hover:bg-white/10">
                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-linear-to-br from-[#F8B803] to-[#F0ACB8] text-[10px] font-bold text-[#050814] shadow-md shadow-amber-500/30">
                    {{ strtoupper(mb_substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </div>
                <span class="hidden max-w-[120px] truncate sm:inline">{{ auth()->user()->email ?? 'admin@keylicense.com' }}</span>
            </a>
        </div>
    </div>

    @if (trim($slot) !== '')
        <div class="border-t border-white/10 bg-[#0B1020]/90 px-4 py-3 md:hidden sm:px-6">
            <div class="flex flex-wrap items-center gap-2">
                {{ $slot }}
            </div>
        </div>
    @endif
</header>
