<?php

namespace Database\Factories;

use App\Enums\FluxColour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SkillCategory>
 */
class SkillCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'colour' => fake()->randomElement(FluxColour::cases()),
        ];
    }
}
