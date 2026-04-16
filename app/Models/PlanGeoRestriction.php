<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanGeoRestriction extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'restriction_type',
        'country_codes',
    ];

    protected $casts = [
        'country_codes' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
