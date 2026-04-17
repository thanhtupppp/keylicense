<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVersion extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'product_id',
        'version',
        'build_number',
        'release_notes',
        'is_latest',
        'is_required',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
