<?php

namespace Database\Factories;

use App\Models\TrainingSupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrainingCourse>
 */
class TrainingCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'prerequisites' => fake()->optional()->sentence(),
            'cost' => (string) fake()->numberBetween(50, 2000),
            'offers_certification' => fake()->boolean(30),
            'training_supplier_id' => null,
        ];
    }

    public function withSupplier(?TrainingSupplier $supplier = null): static
    {
        return $this->state(fn (array $attributes) => [
            'training_supplier_id' => $supplier?->id ?? TrainingSupplier::factory(),
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => null,
        ]);
    }

    public function certified(): static
    {
        return $this->state(fn (array $attributes) => [
            'offers_certification' => true,
        ]);
    }
}
