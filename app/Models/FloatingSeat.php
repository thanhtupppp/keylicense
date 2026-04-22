<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FloatingSeat extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'license_id',
        'activation_id',
        'device_fp_hash',
        'last_heartbeat_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_heartbeat_at' => 'datetime',
    ];

    /**
     * Get the license that owns the floating seat.
     */
    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    /**
     * Get the activation that owns the floating seat.
     */
    public function activation(): BelongsTo
    {
        return $this->belongsTo(Activation::class);
    }
}
