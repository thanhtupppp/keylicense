<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\OfflineTokenJti;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OfflineTokenJtiFactory extends Factory
{
    protected $model = OfflineTokenJti::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'jti' => Str::uuid()->toString(),
            'expires_at' => $this->faker->dateTimeBetween('+1 hour', '+7 days'),
            'is_revoked' => false,
        ];
    }
}
