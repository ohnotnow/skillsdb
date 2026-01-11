<?php

namespace Database\Seeders;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        [$adminUser, $standardUser] = $this->createUsers();
        $categories = $this->createSkillCategories();
        $skills = $this->createSkills($adminUser, $categories);
        $this->assignSkillsToUsers($adminUser, $standardUser, $skills);
        $this->createApiToken($adminUser);
    }

    private function createUsers(): array
    {
        $adminUser = User::factory()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.test',
            'password' => 'secret',
            'is_admin' => true,
            'forenames' => 'Jenny',
            'surname' => 'MacAdmin',
        ]);

        $standardUser = User::factory()->create([
            'username' => 'user2x',
            'email' => 'user2x@example.test',
            'password' => 'secret',
            'is_admin' => false,
            'forenames' => 'Olivia',
            'surname' => 'McUser',
        ]);

        return [$adminUser, $standardUser];
    }

    private function createSkillCategories(): array
    {
        return [
            'programming' => SkillCategory::create(['name' => 'Programming Languages']),
            'frameworks' => SkillCategory::create(['name' => 'Frameworks']),
            'devops' => SkillCategory::create(['name' => 'DevOps']),
            'databases' => SkillCategory::create(['name' => 'Databases']),
        ];
    }

    private function createSkills(User $adminUser, array $categories): array
    {
        $skills = [];

        // Programming Languages
        $skills['php'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'PHP',
            'description' => 'Server-side scripting language',
            'skill_category_id' => $categories['programming']->id,
        ]);
        $skills['javascript'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'JavaScript',
            'description' => 'Client-side and server-side scripting language',
            'skill_category_id' => $categories['programming']->id,
        ]);
        $skills['python'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'Python',
            'description' => 'General-purpose programming language',
            'skill_category_id' => $categories['programming']->id,
        ]);

        // Frameworks
        $skills['laravel'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'Laravel',
            'description' => 'PHP web application framework',
            'skill_category_id' => $categories['frameworks']->id,
        ]);
        $skills['vue'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'Vue.js',
            'description' => 'Progressive JavaScript framework',
            'skill_category_id' => $categories['frameworks']->id,
        ]);

        // DevOps
        $skills['docker'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'Docker',
            'description' => 'Container platform',
            'skill_category_id' => $categories['devops']->id,
        ]);
        $skills['git'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'Git',
            'description' => 'Version control system',
            'skill_category_id' => $categories['devops']->id,
        ]);

        // Databases
        $skills['mysql'] = Skill::factory()->approved($adminUser)->create([
            'name' => 'MySQL',
            'description' => 'Relational database management system',
            'skill_category_id' => $categories['databases']->id,
        ]);

        // A pending skill (suggested but not approved)
        $skills['rust'] = Skill::factory()->pending()->create([
            'name' => 'Rust',
            'description' => 'Systems programming language',
            'skill_category_id' => $categories['programming']->id,
        ]);

        return $skills;
    }

    private function assignSkillsToUsers(User $adminUser, User $standardUser, array $skills): void
    {
        // Admin user skills - spread over 6 months for realistic sparkline data
        $this->attachSkillAtTime($adminUser, $skills['git'], SkillLevel::Medium, now()->subMonths(5));
        $this->attachSkillAtTime($adminUser, $skills['php'], SkillLevel::Medium, now()->subMonths(4));
        $this->attachSkillAtTime($adminUser, $skills['mysql'], SkillLevel::Low, now()->subMonths(3));
        $this->attachSkillAtTime($adminUser, $skills['javascript'], SkillLevel::Low, now()->subMonths(2));
        $this->attachSkillAtTime($adminUser, $skills['laravel'], SkillLevel::Medium, now()->subMonths(1));
        $this->attachSkillAtTime($adminUser, $skills['docker'], SkillLevel::Medium, now()->subDays(5));

        // Update levels to show progression (simulates levelling up)
        $adminUser->skills()->updateExistingPivot($skills['git']->id, ['level' => SkillLevel::High->value]);
        $adminUser->skills()->updateExistingPivot($skills['php']->id, ['level' => SkillLevel::High->value]);
        $adminUser->skills()->updateExistingPivot($skills['laravel']->id, ['level' => SkillLevel::High->value]);

        $adminUser->touchSkillsUpdatedAt();

        // Standard user skills (including the pending skill they suggested)
        $this->attachSkillAtTime($standardUser, $skills['git'], SkillLevel::Low, now()->subMonths(4));
        $this->attachSkillAtTime($standardUser, $skills['php'], SkillLevel::Low, now()->subMonths(3));
        $this->attachSkillAtTime($standardUser, $skills['python'], SkillLevel::Medium, now()->subMonths(2));
        $this->attachSkillAtTime($standardUser, $skills['laravel'], SkillLevel::Low, now()->subMonths(1));
        $this->attachSkillAtTime($standardUser, $skills['rust'], SkillLevel::Low, now()->subDays(10));

        // Update levels
        $standardUser->skills()->updateExistingPivot($skills['git']->id, ['level' => SkillLevel::Medium->value]);
        $standardUser->skills()->updateExistingPivot($skills['python']->id, ['level' => SkillLevel::High->value]);

        $standardUser->touchSkillsUpdatedAt();
    }

    private function attachSkillAtTime(User $user, Skill $skill, SkillLevel $level, $timestamp): void
    {
        $user->skills()->attach($skill->id, [
            'level' => $level->value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function createApiToken(User $user): void
    {
        $token = $user->createToken('dev-token');

        $this->command->info('');
        $this->command->info('API Token for '.$user->username.':');
        $this->command->info($token->plainTextToken);
        $this->command->info('');
    }
}
