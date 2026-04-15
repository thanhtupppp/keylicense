<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'data_type',
        'retention_days',
        'anonymize',
        'description',
        'updated_at',
    ];

    protected $casts = [
        'retention_days' => 'integer',
        'anonymize' => 'boolean',
        'updated_at' => 'datetime',
    ];
}
