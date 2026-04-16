<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerKeyAssignment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'pool_id',
        'license_key_id',
        'assigned_to_email',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function pool(): BelongsTo
    {
        return $this->belongsTo(ResellerKeyPool::class, 'pool_id');
    }
}
