<?php

namespace Database\Factories;

use App\Models\TrainingSupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSupplier>
 */
class TrainingSupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'website' => fake()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
