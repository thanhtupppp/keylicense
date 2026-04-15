<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entitlement extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'plan_id',
        'customer_id',
        'org_id',
        'status',
        'starts_at',
        'expires_at',
        'auto_renew',
        'max_activations',
        'max_sites',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_renew' => 'boolean',
        'max_activations' => 'integer',
        'max_sites' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(LicenseKey::class);
    }
}
