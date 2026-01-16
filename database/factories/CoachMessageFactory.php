<?php

namespace Database\Factories;

use App\Enums\CoachMessageRole;
use App\Models\CoachConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoachMessage>
 */
class CoachMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coach_conversation_id' => CoachConversation::factory(),
            'role' => fake()->randomElement(CoachMessageRole::cases()),
            'content' => fake()->paragraph(),
        ];
    }

    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => CoachMessageRole::User,
        ]);
    }

    public function fromAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => CoachMessageRole::Assistant,
        ]);
    }
}
