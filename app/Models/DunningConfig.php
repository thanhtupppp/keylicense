<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DunningConfig extends Model
{
    use HasFactory;
    use HasUuids;

    public const ACTION_EMAIL = 'email';

    public const ACTION_SUSPEND = 'suspend';

    public const ACTION_CANCEL = 'cancel';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'step',
        'days_after_due',
        'action',
        'email_template_code',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'days_after_due' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
