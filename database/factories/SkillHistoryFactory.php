<?php

namespace Database\Factories;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SkillHistory>
 */
class SkillHistoryFactory extends Factory
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
            'skill_id' => Skill::factory()->approved(),
            'event_type' => SkillHistoryEvent::Added,
            'old_level' => null,
            'new_level' => SkillLevel::Low->value,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function added(?int $level = null): static
    {
        return $this->state(fn () => [
            'event_type' => SkillHistoryEvent::Added,
            'old_level' => null,
            'new_level' => $level ?? SkillLevel::Low->value,
        ]);
    }

    public function removed(?int $level = null): static
    {
        return $this->state(fn () => [
            'event_type' => SkillHistoryEvent::Removed,
            'old_level' => $level ?? SkillLevel::Low->value,
            'new_level' => null,
        ]);
    }

    public function levelledUp(?int $oldLevel = null, ?int $newLevel = null): static
    {
        return $this->state(fn () => [
            'event_type' => SkillHistoryEvent::LevelledUp,
            'old_level' => $oldLevel ?? SkillLevel::Low->value,
            'new_level' => $newLevel ?? SkillLevel::Medium->value,
        ]);
    }

    public function levelledDown(?int $oldLevel = null, ?int $newLevel = null): static
    {
        return $this->state(fn () => [
            'event_type' => SkillHistoryEvent::LevelledDown,
            'old_level' => $oldLevel ?? SkillLevel::Medium->value,
            'new_level' => $newLevel ?? SkillLevel::Low->value,
        ]);
    }
}
