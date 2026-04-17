<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookConfig extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'name',
        'event',
        'target_url',
        'secret',
        'is_active',
        'retry_count',
        'timeout_seconds',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'retry_count' => 'integer',
        'timeout_seconds' => 'integer',
        'metadata' => 'array',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'webhook_config_id');
    }
}
