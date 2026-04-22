@php
    $flashMessages = collect([
        'success' => session('success') ?? session('flash.success'),
        'error' => session('error') ?? session('flash.error'),
        'warning' => session('warning') ?? session('flash.warning'),
        'info' => session('info') ?? session('flash.info'),
    ])->filter(function ($message) {
        return filled($message);
    })->sortKeysUsing(function (string $a, string $b) {
        $priority = ['success' => 0, 'warning' => 1, 'info' => 2, 'error' => 3];

        return ($priority[$a] ?? 99) <=> ($priority[$b] ?? 99);
    });
@endphp

@if($flashMessages->isNotEmpty())
    <div class="pointer-events-none fixed right-4 top-4 z-70 flex w-[min(92vw,26rem)] flex-col gap-3 sm:right-6 sm:top-6">
        @foreach($flashMessages as $type => $message)
            @php
                $baseDelays = [
                    'success' => 80,
                    'warning' => 110,
                    'info' => 130,
                    'error' => 160,
                ];

                $enterDelay = ($baseDelays[$type] ?? 120) + ($loop->index * 70);
                $leaveDelay = ($baseDelays[$type] ?? 120) + ($loop->index * 35);
            @endphp
            <div
                x-data="{ open: true, enterDelay: {{ $enterDelay }}, leaveDelay: {{ $leaveDelay }} }"
                x-init="setTimeout(() => open = false, 4500)"
                x-show="open"
                x-transition:enter="transform-gpu transition-all ease-out duration-300"
                x-transition:enter-start="translate-x-8 opacity-0 scale-95 blur-sm"
                x-transition:enter-end="translate-x-0 opacity-100 scale-100 blur-0"
                x-transition:leave="transform-gpu transition-all ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100 scale-100 blur-0"
                x-transition:leave-end="translate-x-8 opacity-0 scale-95 blur-sm"
                :style="`transition-delay: ${open ? enterDelay : leaveDelay}ms`"
                class="pointer-events-auto"
            >
                <x-ui.alert :type="$type" :message="$message" class="shadow-2xl shadow-black/40" />
            </div>
        @endforeach
    </div>
@endif
