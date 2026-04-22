<div class="flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.audit-logs', array_filter([
        'subject_type' => $subjectType ?? null,
        'subject_id' => $subjectId ?? null,
        'event' => $eventType ?? null,
    ])) }}" class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
        Tải lại
    </a>
    <a href="{{ route('admin.audit-logs.export', request()->query()) }}" class="inline-flex items-center justify-center rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2.5 text-sm font-semibold text-emerald-200 transition hover:bg-emerald-400/15">
        Xuất CSV
    </a>
    <button type="button" wire:click="setQuickRange('today')" class="inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:border-white/25 hover:bg-white/10">
        Hôm nay
    </button>
</div>
