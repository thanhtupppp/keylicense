<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'product_id',
        'code',
        'name',
        'billing_cycle',
        'price_cents',
        'currency',
        'max_activations',
        'max_sites',
        'trial_days',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'max_activations' => 'integer',
        'max_sites' => 'integer',
        'trial_days' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(PlanPricing::class);
    }

    public function defaultPricing(): HasMany
    {
        return $this->pricing()->where('is_default', true);
    }

    public function pricingForCurrency(string $currency): HasMany
    {
        return $this->pricing()->where('currency', strtoupper($currency));
    }
}
