<div class="space-y-6">
    <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
        <x-admin.page-header
            overline="Security"
            title="Nhật ký kiểm toán"
            description="Xem lại các sự kiện quan trọng, cảnh báo và hành vi hệ thống."
        >
            <x-admin.audit-logs.header-actions />
        </x-admin.page-header>
    </div>

    @if($subjectType && $subjectId)
        <div class="rounded-3xl border border-[#F8B803]/20 bg-[#F8B803]/10 px-4 py-3 text-sm text-slate-200">
            Đang xem nhật ký cho <span class="font-semibold text-white">{{ $subjectType }} #{{ $subjectId }}</span>.
            <button type="button" wire:click="clearSubject" class="ml-2 text-[#F8B803] underline underline-offset-2">Xóa bộ lọc subject</button>
        </div>
    @endif

    <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white">Bộ lọc</h3>
            <p class="mt-1 text-sm text-slate-400">Lọc theo loại sự kiện, đối tượng, mức độ và khoảng thời gian.</p>
        </div>

        <x-admin.audit-logs.filters
            :event-types="$eventTypes"
            :subject-types="$subjectTypes"
            :severity-levels="$severityLevels"
        />
    </div>

    <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-6 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-white">Bảng nhật ký</h3>
            <p class="mt-1 text-sm text-slate-400">Lịch sử các hành động trong hệ thống.</p>
        </div>

        @if($logs->count())
            <x-admin.audit-logs.table :logs="$logs" />
        @else
            <x-ui.empty-state
                icon="search"
                title="Không có log phù hợp"
                description="Thử thay đổi bộ lọc hoặc mở rộng khoảng thời gian."
            >
                <button type="button" wire:click="clearFilters" class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                    Xóa bộ lọc
                </button>
            </x-ui.empty-state>
        @endif
    </div>
</div>
