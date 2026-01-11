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
        $realNow = now();

        // Admin user skills - spread over 6 months for realistic sparkline data
        $this->attachSkillAtTime($adminUser, $skills['git'], SkillLevel::Medium, $realNow->copy()->subMonths(5));
        $this->attachSkillAtTime($adminUser, $skills['php'], SkillLevel::Medium, $realNow->copy()->subMonths(4));
        $this->attachSkillAtTime($adminUser, $skills['mysql'], SkillLevel::Low, $realNow->copy()->subMonths(3));
        $this->attachSkillAtTime($adminUser, $skills['javascript'], SkillLevel::Low, $realNow->copy()->subMonths(2));
        $this->attachSkillAtTime($adminUser, $skills['laravel'], SkillLevel::Medium, $realNow->copy()->subMonths(1));
        $this->attachSkillAtTime($adminUser, $skills['docker'], SkillLevel::Medium, $realNow->copy()->subDays(5));

        // Update levels to show progression (simulates levelling up over time)
        $this->updateSkillAtTime($adminUser, $skills['git'], SkillLevel::High, $realNow->copy()->subMonths(3));
        $this->updateSkillAtTime($adminUser, $skills['php'], SkillLevel::High, $realNow->copy()->subMonths(2));
        $this->updateSkillAtTime($adminUser, $skills['laravel'], SkillLevel::High, $realNow->copy()->subWeeks(2));

        $adminUser->touchSkillsUpdatedAt();

        // Standard user skills (including the pending skill they suggested)
        $this->attachSkillAtTime($standardUser, $skills['git'], SkillLevel::Low, $realNow->copy()->subMonths(4));
        $this->attachSkillAtTime($standardUser, $skills['php'], SkillLevel::Low, $realNow->copy()->subMonths(3));
        $this->attachSkillAtTime($standardUser, $skills['python'], SkillLevel::Medium, $realNow->copy()->subMonths(2));
        $this->attachSkillAtTime($standardUser, $skills['laravel'], SkillLevel::Low, $realNow->copy()->subMonths(1));
        $this->attachSkillAtTime($standardUser, $skills['rust'], SkillLevel::Low, $realNow->copy()->subDays(10));

        // Update levels over time
        $this->updateSkillAtTime($standardUser, $skills['git'], SkillLevel::Medium, $realNow->copy()->subMonths(2));
        $this->updateSkillAtTime($standardUser, $skills['python'], SkillLevel::High, $realNow->copy()->subWeeks(3));

        $standardUser->touchSkillsUpdatedAt();
    }

    private function updateSkillAtTime(User $user, Skill $skill, SkillLevel $level, $timestamp): void
    {
        \Illuminate\Support\Carbon::setTestNow($timestamp);
        $user->skills()->updateExistingPivot($skill->id, ['level' => $level->value]);
        \Illuminate\Support\Carbon::setTestNow();
    }

    private function attachSkillAtTime(User $user, Skill $skill, SkillLevel $level, $timestamp): void
    {
        \Illuminate\Support\Carbon::setTestNow($timestamp);
        $user->skills()->attach($skill->id, [
            'level' => $level->value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        \Illuminate\Support\Carbon::setTestNow();
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
