@props(['logs'])

@if(method_exists($logs, 'count') && $logs->count() === 0)
    <x-ui.empty-state
        title="Không có bản ghi nào"
        description="Không tìm thấy audit log phù hợp với bộ lọc hiện tại."
    />
@else
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-white/10 text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <th class="px-4 py-4">Thời gian</th>
                    <th class="px-4 py-4">Sự kiện</th>
                    <th class="px-4 py-4">Người thực hiện</th>
                    <th class="px-4 py-4">Đối tượng</th>
                    <th class="px-4 py-4">IP</th>
                    <th class="px-4 py-4">Kết quả</th>
                    <th class="px-4 py-4">Mức độ</th>
                    <th class="px-4 py-4">Chi tiết</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach($logs as $log)
                    <tr class="align-top hover:bg-white/5">
                        <td class="px-4 py-5 text-slate-200">
                            <div>{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                            <div class="text-xs text-slate-500">{{ $log->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-5 text-slate-200">
                            <div class="font-medium text-white">{{ $log->event_label }}</div>
                            <div class="text-xs text-slate-500">{{ $log->event_type }}</div>
                        </td>
                        <td class="px-4 py-5 text-slate-200">
                            <div>{{ $log->actor_label }}</div>
                        </td>
                        <td class="px-4 py-5 text-slate-200">
                            @if($log->subject_type && $log->subject_id)
                                @php($subjectUrl = \App\Support\AuditLinkResolver::url($log->subject_type, $log->subject_id))
                                @php($subjectLabel = \App\Support\AuditLinkResolver::label($log->subject_type, $log->subject_id))
                                @if($subjectUrl)
                                    <a href="{{ $subjectUrl }}" class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200 transition hover:border-white/25 hover:bg-white/10">
                                        {{ ucfirst($log->subject_type) }} #{{ $log->subject_id }}
                                    </a>
                                @else
                                    <div>{{ ucfirst($log->subject_type) }} #{{ $log->subject_id }}</div>
                                @endif
                                <div class="mt-1 text-[11px] text-[#F8B803]">{{ $subjectLabel }}</div>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-slate-200">{{ $log->ip_address ?? '-' }}</td>
                        <td class="px-4 py-5">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $log->result === 'success' ? 'bg-emerald-400/15 text-emerald-300' : 'bg-rose-400/15 text-rose-300' }}">
                                {{ $log->result === 'success' ? 'Thành công' : 'Thất bại' }}
                            </span>
                        </td>
                        <td class="px-4 py-5">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $log->severity === 'info' ? 'bg-blue-400/15 text-blue-300' : ($log->severity === 'warning' ? 'bg-amber-400/15 text-amber-300' : 'bg-rose-400/15 text-rose-300') }}">
                                {{ $this->getSeverityDisplayName($log->severity) }}
                            </span>
                        </td>
                        <td class="px-4 py-5 text-slate-200">
                            @if($log->payload)
                                <details class="group rounded-2xl border border-white/10 bg-white/5 p-3">
                                    <summary class="cursor-pointer list-none text-xs font-semibold text-[#F8B803]">Xem chi tiết</summary>
                                    <pre class="mt-3 max-w-2xl overflow-auto rounded-2xl border border-white/10 bg-[#07111f] p-3 text-xs text-slate-300">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="border-t border-white/10 px-6 py-4">{{ $logs->links() }}</div>
    @endif
@endif
