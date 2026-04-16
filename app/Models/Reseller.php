<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reseller extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'commission_type',
        'commission_value',
        'status',
        'metadata',
    ];

    protected $casts = [
        'commission_value' => 'integer',
        'metadata' => 'array',
    ];

    public function keyPools(): HasMany
    {
        return $this->hasMany(ResellerKeyPool::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(ResellerUser::class);
    }
}
