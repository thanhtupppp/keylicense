<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageSummary extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'license_id',
        'plan_id',
        'metric',
        'period_start',
        'period_end',
        'total_usage',
        'limit_value',
        'usage_percent',
        'is_over_limit',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_usage' => 'integer',
        'limit_value' => 'integer',
        'usage_percent' => 'integer',
        'is_over_limit' => 'boolean',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
