<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'order_id',
        'entitlement_id',
        'external_id',
        'refund_type',
        'amount_cents',
        'currency',
        'reason',
        'status',
        'auto_revoke',
        'initiated_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'auto_revoke' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
