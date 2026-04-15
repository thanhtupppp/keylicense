<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Environment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'is_production',
        'rate_limit_multiplier',
        'heartbeat_interval_hours',
        'grace_period_days',
    ];

    protected $casts = [
        'is_production' => 'boolean',
        'rate_limit_multiplier' => 'decimal:2',
        'heartbeat_interval_hours' => 'integer',
        'grace_period_days' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
