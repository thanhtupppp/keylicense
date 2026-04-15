<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'role',
        'is_active',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'locked_until' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];
}
