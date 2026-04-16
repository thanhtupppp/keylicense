<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanUsageLimit extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'metric',
        'limit_value',
        'reset_period',
        'is_soft_limit',
    ];

    protected $casts = [
        'limit_value' => 'integer',
        'is_soft_limit' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
