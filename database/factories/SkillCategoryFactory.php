<?php

namespace Database\Factories;

use App\Enums\FluxColour;
use App\Models\SkillCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillCategory>
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
