<?php

namespace Database\Factories;

use App\Enums\CoachMode;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoachConversation>
 */
class CoachConversationFactory extends Factory
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
            'mode' => CoachMode::Personal,
        ];
    }

    public function teamMode(?Team $team = null): static
    {
        return $this->state(fn (array $attributes) => [
            'mode' => CoachMode::Team,
            'team_id' => $team?->id ?? Team::factory(),
        ]);
    }
}
