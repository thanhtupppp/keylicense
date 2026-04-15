<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'email',
        'full_name',
        'phone',
        'metadata',
        'email_verified_at',
        'verification_token',
        'verification_expires_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'email_verified_at' => 'datetime',
        'verification_expires_at' => 'datetime',
    ];
}
