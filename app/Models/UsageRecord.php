<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageRecord extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'license_id',
        'plan_id',
        'metric',
        'quantity',
        'dimensions',
        'recorded_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'dimensions' => 'array',
        'recorded_at' => 'datetime',
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
