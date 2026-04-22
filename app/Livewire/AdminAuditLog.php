<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AdminAuditLog extends Component
{
    use WithPagination;

    public $search = '';
    public $eventType = '';
    public $subjectType = '';
    public $severity = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $ipAddress = '';
    public $subjectId = '';
    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'eventType' => ['except' => ''],
        'subjectType' => ['except' => ''],
        'severity' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'ipAddress' => ['except' => ''],
        'subjectId' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        if (! $this->dateFrom && ! $this->dateTo) {
            $this->setQuickRange('7d');
        }
    }

    public function render()
    {
        return view('livewire.admin-audit-log', [
            'logs' => $this->getAuditLogs(),
            'eventTypes' => $this->getEventTypes(),
            'subjectTypes' => $this->getSubjectTypes(),
            'severityLevels' => $this->getSeverityLevels(),
        ]);
    }

    private function getAuditLogs()
    {
        $query = AuditLog::query()->orderBy('created_at', 'desc');

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('event_type', 'like', "%{$search}%")
                    ->orWhere('subject_id', 'like', "%{$search}%");
            });
        }

        if (!empty($this->eventType)) {
            $query->where('event_type', $this->eventType);
        }

        if (!empty($this->subjectType)) {
            $query->where('subject_type', $this->subjectType);
        }

        if (!empty($this->severity)) {
            $query->where('severity', $this->severity);
        }

        if (!empty($this->dateFrom)) {
            $query->where('created_at', '>=', Carbon::parse($this->dateFrom)->startOfDay());
        }

        if (!empty($this->dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($this->dateTo)->endOfDay());
        }

        if (!empty($this->ipAddress)) {
            $query->where('ip_address', 'like', "%{$this->ipAddress}%");
        }

        if (!empty($this->subjectId)) {
            $query->where('subject_id', $this->subjectId);
        }

        return $query->paginate($this->perPage);
    }

    private function getEventTypes()
    {
        return AuditLog::select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->toArray();
    }

    private function getSubjectTypes()
    {
        return AuditLog::select('subject_type')
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->toArray();
    }

    private function getSeverityLevels()
    {
        return ['info', 'warning', 'error'];
    }

    public function setQuickRange(string $preset): void
    {
        match ($preset) {
            'today' => [
                $this->dateFrom = Carbon::now()->startOfDay()->format('Y-m-d'),
                $this->dateTo = Carbon::now()->endOfDay()->format('Y-m-d'),
            ],
            '7d' => [
                $this->dateFrom = Carbon::now()->subDays(6)->startOfDay()->format('Y-m-d'),
                $this->dateTo = Carbon::now()->format('Y-m-d'),
            ],
            default => null,
        };

        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->eventType = '';
        $this->subjectType = '';
        $this->severity = '';
        $this->dateFrom = Carbon::now()->subDays(6)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->ipAddress = '';
        $this->subjectId = '';
        $this->resetPage();
    }

    public function clearSubject(): void
    {
        $this->subjectType = '';
        $this->subjectId = '';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingEventType()
    {
        $this->resetPage();
    }

    public function updatingSubjectType()
    {
        $this->resetPage();
    }

    public function updatingSeverity()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingIpAddress()
    {
        $this->resetPage();
    }

    public function updatingSubjectId()
    {
        $this->resetPage();
    }

    public function getEventTypeDisplayName($eventType)
    {
        $displayNames = [
            'product.created' => 'Tạo sản phẩm',
            'product.updated' => 'Cập nhật sản phẩm',
            'product.deleted' => 'Xóa sản phẩm',
            'product.status_changed' => 'Đổi trạng thái sản phẩm',
            'license.created' => 'Tạo license',
            'license.updated' => 'Cập nhật license',
            'license.revoked' => 'Thu hồi license',
            'license.suspended' => 'Tạm khóa license',
            'license.restored' => 'Khôi phục license',
            'license.renewed' => 'Gia hạn license',
            'license.unrevoked' => 'Phục hồi license',
            'activation.success' => 'Kích hoạt thành công',
            'activation.failed' => 'Kích hoạt thất bại',
            'validation.failed' => 'Xác thực thất bại',
            'admin.login' => 'Đăng nhập admin',
            'admin.login_failed' => 'Đăng nhập admin thất bại',
            'admin.locked' => 'Khóa tài khoản admin',
            'admin.logout' => 'Đăng xuất admin',
            'activation.revoked' => 'Thu hồi kích hoạt',
        ];

        return $displayNames[$eventType] ?? $eventType;
    }

    public function getSeverityDisplayName($severity)
    {
        $displayNames = [
            'info' => 'Thông tin',
            'warning' => 'Cảnh báo',
            'error' => 'Lỗi',
        ];

        return $displayNames[$severity] ?? $severity;
    }

    public function getSeverityColor($severity)
    {
        $colors = [
            'info' => 'blue',
            'warning' => 'yellow',
            'error' => 'red',
        ];

        return $colors[$severity] ?? 'gray';
    }

    public function getResultColor($result)
    {
        return $result === 'success' ? 'green' : 'red';
    }
}
