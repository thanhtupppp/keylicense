<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseTransfer extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'license_key_id',
        'from_customer_id',
        'to_customer_id',
        'status',
        'reason',
        'transferred_at',
        'metadata',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class, 'license_key_id');
    }
}
