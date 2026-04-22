<?php

namespace App\Livewire;

use App\Models\License;
use App\Models\Product;
use App\Models\Activation;
use App\Models\AuditLog;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboard extends Component
{
    public $selectedProduct = 'all';
    public $chartPeriod = 'daily'; // daily, weekly, monthly
    public $products;

    public function mount()
    {
        $this->products = Product::select('id', 'name', 'slug')->get();
    }

    public function render()
    {
        $metrics = $this->getMetrics();
        $chartData = $this->getChartData();
        $topProducts = $this->getTopProducts();

        return view('livewire.admin-dashboard', [
            'metrics' => $metrics,
            'chartData' => $chartData,
            'topProducts' => $topProducts,
        ]);
    }

    private function getMetrics()
    {
        $query = License::query();

        if ($this->selectedProduct !== 'all') {
            $query->where('product_id', $this->selectedProduct);
        }

        // License counts by status
        $licensesByStatus = $query->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Total products
        $totalProducts = Product::count();

        // Activations in last 24 hours
        $activationsLast24h = Activation::where('activated_at', '>=', Carbon::now()->subDay())
            ->when($this->selectedProduct !== 'all', function ($query) {
                $query->whereHas('license', function ($q) {
                    $q->where('product_id', $this->selectedProduct);
                });
            })
            ->count();

        // Validation failures in last 24 hours
        $validationFailuresLast24h = AuditLog::where('event_type', 'VALIDATION_FAILED')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->when($this->selectedProduct !== 'all', function ($query) {
                $query->where('subject_type', 'license')
                    ->whereIn('subject_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('licenses')
                            ->where('product_id', $this->selectedProduct);
                    });
            })
            ->count();

        return [
            'licenses_by_status' => $licensesByStatus,
            'total_products' => $totalProducts,
            'activations_last_24h' => $activationsLast24h,
            'validation_failures_last_24h' => $validationFailuresLast24h,
        ];
    }

    private function getChartData()
    {
        $query = Activation::query();

        if ($this->selectedProduct !== 'all') {
            $query->whereHas('license', function ($q) {
                $q->where('product_id', $this->selectedProduct);
            });
        }

        switch ($this->chartPeriod) {
            case 'weekly':
                $startDate = Carbon::now()->subWeeks(12);
                $dateFormat = '%Y-%u'; // Year-Week
                break;
            case 'monthly':
                $startDate = Carbon::now()->subMonths(12);
                $dateFormat = '%Y-%m'; // Year-Month
                break;
            default: // daily
                $startDate = Carbon::now()->subDays(30);
                $dateFormat = '%Y-%m-%d'; // Year-Month-Day
                break;
        }

        $activations = $query->where('activated_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(activated_at, '$dateFormat') as period, count(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return $activations->map(fn ($item) => [
            'period' => $item->period,
            'count' => $item->count,
        ]);
    }

    private function getTopProducts()
    {
        return Product::select('products.id', 'products.name', 'products.slug')
            ->leftJoin('licenses', 'products.id', '=', 'licenses.product_id')
            ->leftJoin('activations', 'licenses.id', '=', 'activations.license_id')
            ->where('activations.activated_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('products.id', 'products.name', 'products.slug')
            ->orderByDesc(DB::raw('count(activations.id)'))
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $activationCount = Activation::whereHas('license', fn ($q) => $q->where('product_id', $product->id))
                    ->where('activated_at', '>=', Carbon::now()->subDays(30))
                    ->count();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'activations_count' => $activationCount,
                ];
            });
    }

    public function updatedSelectedProduct()
    {
        // Trigger re-render when product filter changes
    }

    public function updatedChartPeriod()
    {
        // Trigger re-render when chart period changes
    }
}
