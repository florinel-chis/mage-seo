<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeoJob>
 */
class SeoJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'magento_store_view' => $this->faker->word(),
            'status' => $this->faker->randomElement(['PENDING', 'RUNNING', 'COMPLETED', 'FAILED']),
            'total_products' => $this->faker->numberBetween(1, 100),
            'processed_products' => $this->faker->numberBetween(0, 99),
        ];
    }
}
