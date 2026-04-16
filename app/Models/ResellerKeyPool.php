<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerKeyPool extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'reseller_id',
        'plan_id',
        'total_keys',
        'used_keys',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'total_keys' => 'integer',
        'used_keys' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ResellerKeyAssignment::class, 'pool_id');
    }
}
