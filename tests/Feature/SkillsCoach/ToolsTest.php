<?php

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\SkillHistory;
use App\Models\SkillUser;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\Tools\FindExperts;
use App\Services\SkillsCoach\Tools\FindSkillSharers;
use App\Services\SkillsCoach\Tools\GetSkillJourney;
use App\Services\SkillsCoach\Tools\GetTeamGaps;
use App\Services\SkillsCoach\Tools\GetTrendingSkills;
use App\Services\SkillsCoach\Tools\GetUserProfile;
use App\Services\SkillsCoach\Tools\GetUserProgress;
use App\Services\SkillsCoach\Tools\SearchByCategory;

beforeEach(function () {
    $this->context = app(CoachContext::class);
});

describe('FindExperts', function () {
    it('finds users with High proficiency in a skill', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $expert = User::factory()->create(['coach_contactable' => true]);
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);

        $expert->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('Docker'), true);

        expect($result['skill'])->toBe('Docker');
        expect($result['count'])->toBe(1);
        expect($result['experts'][0]['name'])->toBe($expert->full_name);
    });

    it('excludes users who have opted out of coach recommendations', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $expertOptedIn = User::factory()->create(['coach_contactable' => true]);
        $expertOptedOut = User::factory()->create(['coach_contactable' => false]);
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Kubernetes']);

        $expertOptedIn->skills()->attach($skill, ['level' => SkillLevel::High->value]);
        $expertOptedOut->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('Kubernetes'), true);

        expect($result['count'])->toBe(1);
        expect($result['experts'][0]['name'])->toBe($expertOptedIn->full_name);
    });

    it('excludes the current user from results', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create(['coach_contactable' => true]);
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Python']);

        $currentUser->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('Python'), true);

        expect($result['count'])->toBe(0);
    });

    it('returns helpful message when skill not found', function () {
        $currentUser = User::factory()->create();

        $this->context->setUser($currentUser);
        $tool = app(FindExperts::class);
        $result = json_decode($tool('NonexistentSkill'), true);

        expect($result['found'])->toBeFalse();
        expect($result['message'])->toContain('NonexistentSkill');
    });
});

describe('FindSkillSharers', function () {
    it('finds users at any proficiency level', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Laravel']);

        $lowUser = User::factory()->create(['coach_contactable' => true]);
        $mediumUser = User::factory()->create(['coach_contactable' => true]);
        $highUser = User::factory()->create(['coach_contactable' => true]);

        $lowUser->skills()->attach($skill, ['level' => SkillLevel::Low->value]);
        $mediumUser->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        $highUser->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindSkillSharers::class);
        $result = json_decode($tool('Laravel'), true);

        expect($result['count'])->toBe(3);
        // Should be sorted by level (High first)
        expect($result['sharers'][0]['level'])->toBe('High');
    });

    it('respects opt-out settings', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Vue']);

        $contactable = User::factory()->create(['coach_contactable' => true]);
        $notContactable = User::factory()->create(['coach_contactable' => false]);

        $contactable->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        $notContactable->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);

        $this->context->setUser($currentUser);
        $tool = app(FindSkillSharers::class);
        $result = json_decode($tool('Vue'), true);

        expect($result['count'])->toBe(1);
    });
});

describe('GetUserProfile', function () {
    it('returns current user skill distribution', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $skills = Skill::factory()->approved($admin)->count(3)->create();
        $user->skills()->attach($skills[0], ['level' => SkillLevel::Low->value]);
        $user->skills()->attach($skills[1], ['level' => SkillLevel::Medium->value]);
        $user->skills()->attach($skills[2], ['level' => SkillLevel::High->value]);

        $this->context->setUser($user);
        $tool = app(GetUserProfile::class);
        $result = json_decode($tool(), true);

        expect($result['name'])->toBe($user->full_name);
        expect($result['total_skills'])->toBe(3);
        expect($result['distribution']['low'])->toBe(1);
        expect($result['distribution']['medium'])->toBe(1);
        expect($result['distribution']['high'])->toBe(1);
    });
});

describe('GetTrendingSkills', function () {
    it('returns skills that have been recently added by users', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $trendingSkill = Skill::factory()->approved($admin)->create(['name' => 'Kubernetes']);
        $oldSkill = Skill::factory()->approved($admin)->create(['name' => 'COBOL']);

        // Attach trending skill recently
        $user->skills()->attach($trendingSkill, [
            'level' => SkillLevel::Low->value,
            'created_at' => now()->subDays(5),
        ]);

        // Attach old skill long ago (outside trending window)
        $user->skills()->attach($oldSkill, [
            'level' => SkillLevel::Low->value,
            'created_at' => now()->subDays(60),
        ]);

        $this->context->setUser($user);
        $tool = app(GetTrendingSkills::class);
        $result = json_decode($tool(), true);

        expect($result['count'])->toBe(1);
        expect($result['trending'][0]['name'])->toBe('Kubernetes');
    });

    it('respects the limit parameter', function () {
        $admin = User::factory()->admin()->create();
        $users = User::factory()->count(3)->create();

        $skills = Skill::factory()->approved($admin)->count(5)->create();

        // Attach all skills to users recently
        foreach ($skills as $skill) {
            foreach ($users as $user) {
                $user->skills()->attach($skill, [
                    'level' => SkillLevel::Low->value,
                    'created_at' => now()->subDays(5),
                ]);
            }
        }

        $this->context->setUser($users->first());
        $tool = app(GetTrendingSkills::class);
        $result = json_decode($tool(limit: 2), true);

        expect($result['count'])->toBe(2);
    });
});

describe('GetTeamGaps', function () {
    it('finds skills with thin coverage', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $gapSkill = Skill::factory()->approved($admin)->create(['name' => 'Terraform']);
        $popularSkill = Skill::factory()->approved($admin)->create(['name' => 'Git']);

        // Gap skill - only 1 person knows it
        $user->skills()->attach($gapSkill, ['level' => SkillLevel::High->value]);

        // Popular skill - many people know it
        $manyUsers = User::factory()->count(5)->create();
        foreach ($manyUsers as $u) {
            $u->skills()->attach($popularSkill, ['level' => SkillLevel::Medium->value]);
        }

        $this->context->setUser($admin);
        $tool = app(GetTeamGaps::class);
        $result = json_decode($tool(), true);

        $gapNames = collect($result['thin_coverage'])->pluck('name')->toArray();
        expect($gapNames)->toContain('Terraform');
        expect($gapNames)->not->toContain('Git');
    });

    it('identifies skills with no coverage', function () {
        $admin = User::factory()->admin()->create();

        Skill::factory()->approved($admin)->create(['name' => 'Obscure Language']);

        $this->context->setUser($admin);
        $tool = app(GetTeamGaps::class);
        $result = json_decode($tool(), true);

        $noCoverageNames = collect($result['no_coverage'])->pluck('name')->toArray();
        expect($noCoverageNames)->toContain('Obscure Language');
    });
});

describe('GetSkillJourney', function () {
    it('returns the history of a user with a skill', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $skill = Skill::factory()->approved($admin)->create(['name' => 'Docker']);

        // Attach at Low level - observer creates "Added" history
        $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);

        // Update to Medium via model to trigger observer "LevelledUp" history
        SkillUser::where('user_id', $user->id)
            ->where('skill_id', $skill->id)
            ->first()
            ->update(['level' => SkillLevel::Medium]);

        $this->context->setUser($user);
        $tool = app(GetSkillJourney::class);
        $result = json_decode($tool('Docker'), true);

        expect($result['skill'])->toBe('Docker');
        expect($result['current_level'])->toBe('Medium');
        expect($result['total_events'])->toBe(2);
        expect($result['journey'][0]['event'])->toBe('Added');
        expect($result['journey'][1]['event'])->toBe('Levelled Up');
    });

    it('returns helpful message when skill not found', function () {
        $user = User::factory()->create();

        $this->context->setUser($user);
        $tool = app(GetSkillJourney::class);
        $result = json_decode($tool('NonexistentSkill'), true);

        expect($result['found'])->toBeFalse();
        expect($result['message'])->toContain('NonexistentSkill');
    });
});

describe('GetUserProgress', function () {
    it('returns skill points over time with momentum', function () {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $skill = Skill::factory()->approved($admin)->create();

        // Create history showing growth
        SkillHistory::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'event_type' => SkillHistoryEvent::Added,
            'old_level' => null,
            'new_level' => SkillLevel::Low->value,
            'created_at' => now()->subMonths(3),
        ]);
        SkillHistory::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'event_type' => SkillHistoryEvent::LevelledUp,
            'old_level' => SkillLevel::Low->value,
            'new_level' => SkillLevel::High->value,
            'created_at' => now()->subWeek(),
        ]);

        $this->context->setUser($user);
        $tool = app(GetUserProgress::class);
        $result = json_decode($tool(months: 6), true);

        expect($result['user'])->toBe($user->full_name);
        expect($result['months_analysed'])->toBe(6);
        expect($result['summary']['momentum'])->toBe('growing');
    });
});

describe('SearchByCategory', function () {
    it('finds colleagues strong in a category', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();

        $category = SkillCategory::factory()->create(['name' => 'Infrastructure']);
        $skill = Skill::factory()->approved($admin)->create([
            'name' => 'AWS',
            'skill_category_id' => $category->id,
        ]);

        $colleague = User::factory()->create(['coach_contactable' => true]);
        $colleague->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(SearchByCategory::class);
        $result = json_decode($tool('Infrastructure'), true);

        expect($result['category'])->toBe('Infrastructure');
        expect($result['count'])->toBe(1);
        expect($result['people'][0]['name'])->toBe($colleague->full_name);
    });

    it('respects opt-out settings', function () {
        $admin = User::factory()->admin()->create();
        $currentUser = User::factory()->create();

        $category = SkillCategory::factory()->create(['name' => 'Development']);
        $skill = Skill::factory()->approved($admin)->create([
            'name' => 'PHP',
            'skill_category_id' => $category->id,
        ]);

        $contactable = User::factory()->create(['coach_contactable' => true]);
        $notContactable = User::factory()->create(['coach_contactable' => false]);

        $contactable->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
        $notContactable->skills()->attach($skill, ['level' => SkillLevel::High->value]);

        $this->context->setUser($currentUser);
        $tool = app(SearchByCategory::class);
        $result = json_decode($tool('Development'), true);

        expect($result['count'])->toBe(1);
        expect($result['people'][0]['name'])->toBe($contactable->full_name);
    });

    it('returns available categories when category not found', function () {
        $currentUser = User::factory()->create();
        SkillCategory::factory()->create(['name' => 'Infrastructure']);
        SkillCategory::factory()->create(['name' => 'Development']);

        $this->context->setUser($currentUser);
        $tool = app(SearchByCategory::class);
        $result = json_decode($tool('NonexistentCategory'), true);

        expect($result['found'])->toBeFalse();
        expect($result['available_categories'])->toContain('Infrastructure');
        expect($result['available_categories'])->toContain('Development');
    });
});
