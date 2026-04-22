@props(['eventTypes', 'subjectTypes', 'severityLevels'])

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <x-ui.select name="eventType" label="Loại sự kiện" :options="collect($eventTypes)->mapWithKeys(fn ($type) => [$type => app(\App\Livewire\AdminAuditLog::class)->getEventTypeDisplayName($type)])->all()" placeholder="Tất cả" wire:model="eventType" />

    <x-ui.select name="subjectType" label="Loại đối tượng" :options="collect($subjectTypes)->mapWithKeys(fn ($type) => [$type => ucfirst($type)])->all()" placeholder="Tất cả" wire:model="subjectType" />

    <x-ui.select name="severity" label="Mức độ" :options="collect($severityLevels)->mapWithKeys(fn ($level) => [$level => app(\App\Livewire\AdminAuditLog::class)->getSeverityDisplayName($level)])->all()" placeholder="Tất cả" wire:model="severity" />

    <x-ui.select name="perPage" label="Số bản ghi/trang" :options="[25 => '25', 50 => '50', 100 => '100']" wire:model="perPage" />

    <x-ui.input name="dateFrom" type="date" label="Từ ngày" wire:model="dateFrom" />
    <x-ui.input name="dateTo" type="date" label="Đến ngày" wire:model="dateTo" />
    <x-ui.input name="ipAddress" label="Địa chỉ IP" placeholder="192.168.1.1" wire:model="ipAddress" />
    <x-ui.input name="subjectId" type="number" label="ID đối tượng" placeholder="123" wire:model="subjectId" />
</div>
