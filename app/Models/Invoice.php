<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'order_id',
        'customer_id',
        'org_id',
        'invoice_number',
        'status',
        'subtotal_cents',
        'tax_cents',
        'discount_cents',
        'total_cents',
        'currency',
        'tax_rate',
        'billing_address',
        'pdf_url',
        'issued_at',
        'due_at',
        'paid_at',
    ];

    protected $casts = [
        'subtotal_cents' => 'integer',
        'tax_cents' => 'integer',
        'discount_cents' => 'integer',
        'total_cents' => 'integer',
        'tax_rate' => 'decimal:2',
        'billing_address' => 'array',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
}
