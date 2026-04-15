<?php

namespace App\Services\Privacy;

use App\Models\DataRetentionPolicy;
use App\Models\DunningLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class DataRetentionService
{
    public function applyRetention(string $dataType, CarbonInterface $cutoff): int
    {
        if (! Schema::hasTable($dataType)) {
            return 0;
        }

        return match ($dataType) {
            'notification_logs' => 0,
            'dunning_logs' => DunningLog::query()->where('created_at', '<', $cutoff)->delete(),
            default => 0,
        };
    }

    public function policyFor(string $dataType): ?DataRetentionPolicy
    {
        return DataRetentionPolicy::query()->where('data_type', $dataType)->first();
    }
}
