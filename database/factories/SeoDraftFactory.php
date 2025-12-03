<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SeoJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeoDraft>
 */
class SeoDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seo_job_id' => SeoJob::factory(),
            'product_id' => Product::factory(),
            'original_data' => [
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->paragraph(),
            ],
            'generated_draft' => [
                'meta_title' => $this->faker->sentence(5),
                'meta_description' => $this->faker->sentence(15),
                'meta_keywords' => $this->faker->words(5, true),
            ],
            'audit_flags' => [],
            'confidence_score' => $this->faker->randomFloat(4, 0.5, 0.9999),
            'status' => $this->faker->randomElement(['PENDING_REVIEW', 'APPROVED', 'REJECTED']),
        ];
    }
}
