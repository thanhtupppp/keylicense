<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlatformConfig extends Model
{
    use HasFactory;
    use HasUuids;

    public const VALUE_TYPE_STRING = 'string';

    public const VALUE_TYPE_INTEGER = 'integer';

    public const VALUE_TYPE_BOOLEAN = 'boolean';

    public const VALUE_TYPE_JSON = 'json';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'value_type',
        'description',
        'is_sensitive',
        'updated_by',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_sensitive' => 'boolean',
            'updated_at' => 'datetime',
        ];
    }

    public function castValue(): mixed
    {
        return $this->getTypedValueAttribute();
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->value_type) {
            self::VALUE_TYPE_INTEGER => (int) $this->value,
            self::VALUE_TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            self::VALUE_TYPE_JSON => $this->decodeJsonValue(),
            default => $this->value,
        };
    }

    private function decodeJsonValue(): array
    {
        $decoded = json_decode($this->value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
