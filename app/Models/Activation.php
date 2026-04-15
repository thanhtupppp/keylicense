<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'activation_code',
        'license_id',
        'product_code',
        'domain',
        'environment',
        'status',
        'activated_at',
        'last_validated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_id');
    }
}
