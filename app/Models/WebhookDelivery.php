<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'webhook_config_id',
        'event',
        'payload',
        'status_code',
        'response_body',
        'attempt_count',
        'last_attempt_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt_count' => 'integer',
        'last_attempt_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
