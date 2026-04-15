<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'customer_id',
        'notification_code',
        'channel',
        'enabled',
        'unsubscribe_token',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
