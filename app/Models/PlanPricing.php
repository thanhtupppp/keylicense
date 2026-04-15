<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPricing extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'plan_pricing';

    protected $fillable = [
        'plan_id',
        'currency',
        'price_cents',
        'is_default',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'is_default' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
