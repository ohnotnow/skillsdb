<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'skill_category_id' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    public function approved(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => $user?->id ?? User::factory()->admin(),
            'approved_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }
}
