<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'entitlement_id',
        'customer_id',
        'org_id',
        'external_id',
        'source',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(Entitlement::class);
    }

    public function dunningLogs(): HasMany
    {
        return $this->hasMany(DunningLog::class);
    }

    public function entitlementWithPlanProduct(): BelongsTo
    {
        return $this->belongsTo(Entitlement::class, 'entitlement_id')->with('plan.product');
    }
}
