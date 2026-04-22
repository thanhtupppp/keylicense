<?php

namespace Database\Factories;

use App\Models\Activation;
use App\Models\FloatingSeat;
use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

class FloatingSeatFactory extends Factory
{
    protected $model = FloatingSeat::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'activation_id' => Activation::factory(),
            'device_fp_hash' => hash('sha256', $this->faker->uuid()),
            'last_heartbeat_at' => $this->faker->dateTime(),
        ];
    }
}
