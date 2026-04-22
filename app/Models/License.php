<?php

namespace App\Models;

use App\States\License\LicenseState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ModelStates\HasStates;

/**
 * @property LicenseState $status
 */
class License extends Model
{
    use HasFactory, SoftDeletes, HasStates;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'key_hash',
        'key_last4',
        'license_model',
        'status',
        'max_seats',
        'expiry_date',
        'customer_name',
        'customer_email',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expiry_date' => 'date',
        'max_seats' => 'integer',
        'deleted_at' => 'datetime',
        'status' => LicenseState::class,
    ];

    /**
     * Get the product that owns the license.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the activations for the license.
     */
    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    /**
     * Get the floating seats for the license.
     */
    public function floatingSeats(): HasMany
    {
        return $this->hasMany(FloatingSeat::class);
    }

    /**
     * Get the offline token JTIs for the license.
     */
    public function offlineTokenJtis(): HasMany
    {
        return $this->hasMany(OfflineTokenJti::class);
    }
}
