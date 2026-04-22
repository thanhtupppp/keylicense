<div class="space-y-6">
    <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-5 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="product-filter" class="mb-2 block text-sm font-medium text-slate-200">Lọc theo sản phẩm</label>
                    <select wire:model="selectedProduct" id="product-filter" class="w-full rounded-2xl border border-white/10 bg-[#07111f] px-4 py-3 text-sm text-white outline-none transition focus:border-[#F8B803]/50 focus:ring-2 focus:ring-[#F8B803]/20">
                        <option value="all">Tất cả sản phẩm</option>
                        @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="chart-period" class="mb-2 block text-sm font-medium text-slate-200">Khoảng thời gian biểu đồ</label>
                    <select wire:model="chartPeriod" id="chart-period" class="w-full rounded-2xl border border-white/10 bg-[#07111f] px-4 py-3 text-sm text-white outline-none transition focus:border-[#F8B803]/50 focus:ring-2 focus:ring-[#F8B803]/20">
                        <option value="daily">Theo ngày (30 ngày)</option>
                        <option value="weekly">Theo tuần (12 tuần)</option>
                        <option value="monthly">Theo tháng (12 tháng)</option>
                    </select>
                </div>
            </div>

            <div class="text-sm text-slate-400">
                Cập nhật theo bộ lọc hiện tại
            </div>
        </div>
    </div>

    @php
        $statusLabels = [
            'inactive' => ['label' => 'Chưa kích hoạt', 'color' => 'slate'],
            'active' => ['label' => 'Đang hoạt động', 'color' => 'emerald'],
            'expired' => ['label' => 'Hết hạn', 'color' => 'amber'],
            'suspended' => ['label' => 'Tạm khóa', 'color' => 'orange'],
            'revoked' => ['label' => 'Thu hồi', 'color' => 'rose'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-[1.75rem] border border-white/10 bg-white/8 p-5 shadow-lg shadow-black/15 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F8B803]/15 text-[#F8B803]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div>
                    <div class="text-sm text-slate-400">Tổng sản phẩm</div>
                    <div class="text-2xl font-bold text-white">{{ $metrics['total_products'] }}</div>
                </div>
            </div>
        </div>

        @foreach(['active', 'inactive', 'expired'] as $status)
        <div class="rounded-[1.75rem] border border-white/10 bg-white/8 p-5 shadow-lg shadow-black/15 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-{{ $statusLabels[$status]['color'] }}-500/15 text-{{ $statusLabels[$status]['color'] }}-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
                <div>
                    <div class="text-sm text-slate-400">{{ $statusLabels[$status]['label'] }}</div>
                    <div class="text-2xl font-bold text-white">{{ $metrics['licenses_by_status'][$status] ?? 0 }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
        <div class="rounded-[1.75rem] border border-white/10 bg-white/8 p-5 shadow-lg shadow-black/15 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F0ACB8]/15 text-[#F0ACB8]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <div>
                    <div class="text-sm text-slate-400">Kích hoạt trong 24h</div>
                    <div class="text-2xl font-bold text-white">{{ $metrics['activations_last_24h'] }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-white/10 bg-white/8 p-5 shadow-lg shadow-black/15 backdrop-blur-xl">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/15 text-rose-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <div class="text-sm text-slate-400">Xác thực thất bại trong 24h</div>
                    <div class="text-2xl font-bold text-white">{{ $metrics['validation_failures_last_24h'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-5 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
            <h3 class="mb-4 text-lg font-semibold text-white">
                Biểu đồ kích hoạt
                @if($chartPeriod === 'daily') theo ngày
                @elseif($chartPeriod === 'weekly') theo tuần
                @else theo tháng
                @endif
            </h3>
            <div class="h-72">
                <canvas id="activationsChart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="rounded-4xl border border-white/10 bg-[rgba(18,24,43,0.88)] p-5 shadow-[0_24px_80px_rgba(0,0,0,0.45)] backdrop-blur-2xl">
            <h3 class="mb-4 text-lg font-semibold text-white">Top 5 sản phẩm (30 ngày qua)</h3>
            <div class="space-y-3">
                @forelse($topProducts as $product)
                <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-[#07111f] px-4 py-3">
                    <div>
                        <div class="font-medium text-white">{{ $product['name'] }}</div>
                        <div class="text-sm text-slate-400">{{ $product['slug'] }}</div>
                    </div>
                    <div class="text-xl font-bold text-[#F8B803]">{{ $product['activations_count'] }}</div>
                </div>
                @empty
                <div class="rounded-2xl border border-dashed border-white/10 bg-[#07111f] px-4 py-8 text-center text-sm text-slate-400">
                    Chưa có dữ liệu kích hoạt trong 30 ngày qua
                </div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let activationsChart;

            function updateChart() {
                const chartData = @json($chartData);
                const labels = chartData.map(item => item.period);
                const data = chartData.map(item => item.count);

                const canvas = document.getElementById('activationsChart');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                if (activationsChart) {
                    activationsChart.destroy();
                }

                activationsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Số lượng kích hoạt',
                            data: data,
                            borderColor: '#F8B803',
                            backgroundColor: 'rgba(248, 184, 3, 0.12)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 2,
                            pointHoverRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                ticks: { color: '#94a3b8' },
                                grid: { color: 'rgba(255,255,255,0.06)' }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: '#94a3b8',
                                    stepSize: 1
                                },
                                grid: { color: 'rgba(255,255,255,0.06)' }
                            }
                        }
                    }
                });
            }

            updateChart();

            Livewire.hook('message.processed', (message, component) => {
                if (component.fingerprint.name === 'admin-dashboard') {
                    setTimeout(updateChart, 100);
                }
            });
        });
    </script>
    @endpush
</div>
