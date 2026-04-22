<?php

namespace Database\Factories;

use App\Models\Activation;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivationFactory extends Factory
{
    protected $model = Activation::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'device_fp_hash' => hash('sha256', $this->faker->uuid()),
            'user_identifier' => $this->faker->optional()->email(),
            'type' => $this->faker->randomElement(['per-device', 'per-user', 'floating']),
            'activated_at' => $this->faker->dateTime(),
            'last_verified_at' => $this->faker->optional()->dateTime(),
            'is_active' => true,
        ];
    }
}
