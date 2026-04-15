<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLoginHistory extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $table = 'admin_login_history';

    protected $fillable = [
        'admin_id',
        'ip_address',
        'user_agent',
        'location',
        'success',
        'failure_reason',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'occurred_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}
