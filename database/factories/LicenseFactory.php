<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\Product;
use App\Services\LicenseKeyGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        $generator = new LicenseKeyGenerator();
        $plaintext = $generator->generate();

        return [
            'product_id' => Product::factory(),
            'key_hash' => hash('sha256', $plaintext),
            'key_last4' => substr($plaintext, -4),
            'license_model' => $this->faker->randomElement(['per-device', 'per-user', 'floating']),
            'status' => 'inactive', // Use string instead of state object
            'max_seats' => $this->faker->numberBetween(1, 10),
            'expiry_date' => $this->faker->optional()->dateTimeBetween('+1 day', '+1 year'),
            'customer_name' => $this->faker->optional()->name(),
            'customer_email' => $this->faker->optional()->email(),
            'notes' => $this->faker->optional()->text(100),
        ];
    }
}
