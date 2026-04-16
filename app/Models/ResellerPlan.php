<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ResellerPlan extends Pivot
{
    protected $table = 'reseller_plans';

    public $incrementing = false;

    protected $fillable = [
        'reseller_id',
        'plan_id',
        'custom_price_cents',
        'max_keys',
    ];
}
