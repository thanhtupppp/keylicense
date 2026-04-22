<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo_url',
        'platforms',
        'status',
        'offline_token_ttl_hours',
        'api_key',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'platforms' => 'array',
        'offline_token_ttl_hours' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the licenses for the product.
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
