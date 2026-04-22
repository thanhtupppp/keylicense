<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'license_id',
        'device_fp_hash',
        'user_identifier',
        'type',
        'activated_at',
        'last_verified_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activated_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the license that owns the activation.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Get the floating seats for the activation.
     */
    public function floatingSeats(): HasMany
    {
        return $this->hasMany(FloatingSeat::class);
    }
}
