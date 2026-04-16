<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use HasFactory;
    use HasUuids;

    protected $guard = 'admin';

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'role',
        'is_active',
        'failed_login_attempts',
        'locked_until',
        'mfa_enabled',
        'mfa_secret',
        'mfa_enabled_at',
        'mfa_failed_attempts',
        'mfa_locked_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'locked_until' => 'datetime',
        'mfa_enabled' => 'boolean',
        'mfa_enabled_at' => 'datetime',
        'mfa_locked_until' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
        'mfa_secret',
    ];
}
