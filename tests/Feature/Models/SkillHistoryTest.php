<?php

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillHistory;
use App\Models\User;
use Illuminate\Support\Carbon;

it('records history when skill is added', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);

    expect(SkillHistory::count())->toBe(1);
    expect(SkillHistory::first())
        ->user_id->toBe($user->id)
        ->skill_id->toBe($skill->id)
        ->event_type->toBe(SkillHistoryEvent::Added)
        ->old_level->toBeNull()
        ->new_level->toBe(SkillLevel::Low->value);
});

it('records history when skill level increases', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);
    $user->skills()->updateExistingPivot($skill->id, ['level' => SkillLevel::High->value]);

    expect(SkillHistory::count())->toBe(2);

    $levelUpEvent = SkillHistory::latest('id')->first();
    expect($levelUpEvent)
        ->event_type->toBe(SkillHistoryEvent::LevelledUp)
        ->old_level->toBe(SkillLevel::Low->value)
        ->new_level->toBe(SkillLevel::High->value);
});

it('records history when skill level decreases', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::High->value]);
    $user->skills()->updateExistingPivot($skill->id, ['level' => SkillLevel::Low->value]);

    expect(SkillHistory::count())->toBe(2);

    $levelDownEvent = SkillHistory::latest('id')->first();
    expect($levelDownEvent)
        ->event_type->toBe(SkillHistoryEvent::LevelledDown)
        ->old_level->toBe(SkillLevel::High->value)
        ->new_level->toBe(SkillLevel::Low->value);
});

it('records history when skill is removed', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
    $user->skills()->detach($skill->id);

    expect(SkillHistory::count())->toBe(2);

    $removeEvent = SkillHistory::latest('id')->first();
    expect($removeEvent)
        ->event_type->toBe(SkillHistoryEvent::Removed)
        ->old_level->toBe(SkillLevel::Medium->value)
        ->new_level->toBeNull();
});

it('does not record history when level stays the same', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);
    $user->skills()->updateExistingPivot($skill->id, ['level' => SkillLevel::Medium->value]);

    expect(SkillHistory::count())->toBe(1);
});

it('records history for each skill in bulk operations', function () {
    $user = User::factory()->create();
    $skills = Skill::factory()->approved()->count(3)->create();

    $attachData = $skills->mapWithKeys(fn ($skill) => [
        $skill->id => ['level' => SkillLevel::Low->value],
    ])->toArray();

    $user->skills()->attach($attachData);

    expect(SkillHistory::count())->toBe(3);
    expect(SkillHistory::where('event_type', SkillHistoryEvent::Added)->count())->toBe(3);
});

it('has relationships to user and skill', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);

    $history = SkillHistory::first();
    expect($history->user->id)->toBe($user->id);
    expect($history->skill->id)->toBe($skill->id);
});

it('user has skillHistory relationship', function () {
    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);
    $user->skills()->updateExistingPivot($skill->id, ['level' => SkillLevel::High->value]);

    expect($user->skillHistory)->toHaveCount(2);
    expect($user->skillHistory->first()->event_type)->toBe(SkillHistoryEvent::LevelledUp);
});

// Tests for getSkillsOverTimeFromHistory()

it('calculates accurate points from history for a single skill', function () {
    // Fix "now" to a known date for predictable testing
    $realNow = Carbon::parse('2024-06-15 12:00:00');
    Carbon::setTestNow($realNow);

    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    // Add skill at Low, 3 months ago (March 2024)
    Carbon::setTestNow($realNow->copy()->subMonths(3));
    $user->skills()->attach($skill, ['level' => SkillLevel::Low->value]);

    // Level up to High, 1 month ago (May 2024)
    Carbon::setTestNow($realNow->copy()->subMonth());
    $user->skills()->updateExistingPivot($skill->id, ['level' => SkillLevel::High->value]);

    // Reset to "now" (June 2024)
    Carbon::setTestNow($realNow);

    $data = $user->getSkillsOverTimeFromHistory(5);

    // Month 0 (Feb): 0 points (no skill yet)
    // Month 1 (Mar): 1 point (Low added)
    // Month 2 (Apr): 1 point (still Low)
    // Month 3 (May): 3 points (High)
    // Month 4 (Jun): 3 points (still High)

    expect($data[0]['points'])->toBe(0);
    expect($data[1]['points'])->toBe(1);
    expect($data[2]['points'])->toBe(1);
    expect($data[3]['points'])->toBe(3);
    expect($data[4]['points'])->toBe(3);

    Carbon::setTestNow(); // Clean up
});

it('handles removed skills correctly in history calculation', function () {
    $realNow = Carbon::parse('2024-06-15 12:00:00');
    Carbon::setTestNow($realNow);

    $user = User::factory()->create();
    $skill = Skill::factory()->approved()->create();

    // Add skill 4 months ago (Feb 2024)
    Carbon::setTestNow($realNow->copy()->subMonths(4));
    $user->skills()->attach($skill, ['level' => SkillLevel::Medium->value]);

    // Remove skill 2 months ago (Apr 2024)
    Carbon::setTestNow($realNow->copy()->subMonths(2));
    $user->skills()->detach($skill->id);

    Carbon::setTestNow($realNow);

    $data = $user->getSkillsOverTimeFromHistory(5);

    // Month 0 (Feb): 2 points (Medium added)
    // Month 1 (Mar): 2 points (still Medium)
    // Month 2 (Apr): 0 points (removed)
    // Month 3 (May): 0 points (still removed)
    // Month 4 (Jun): 0 points (still removed)

    expect($data[0]['points'])->toBe(2);
    expect($data[1]['points'])->toBe(2);
    expect($data[2]['points'])->toBe(0);
    expect($data[3]['points'])->toBe(0);
    expect($data[4]['points'])->toBe(0);

    Carbon::setTestNow();
});

it('handles multiple skills with different timelines', function () {
    $realNow = Carbon::parse('2024-06-15 12:00:00');
    Carbon::setTestNow($realNow);

    $user = User::factory()->create();
    $skillA = Skill::factory()->approved()->create();
    $skillB = Skill::factory()->approved()->create();

    // Add skill A at Low, 4 months ago (Feb 2024)
    Carbon::setTestNow($realNow->copy()->subMonths(4));
    $user->skills()->attach($skillA, ['level' => SkillLevel::Low->value]);

    // Add skill B at Medium, 2 months ago (Apr 2024)
    Carbon::setTestNow($realNow->copy()->subMonths(2));
    $user->skills()->attach($skillB, ['level' => SkillLevel::Medium->value]);

    // Level up skill A to High, 1 month ago (May 2024)
    Carbon::setTestNow($realNow->copy()->subMonth());
    $user->skills()->updateExistingPivot($skillA->id, ['level' => SkillLevel::High->value]);

    Carbon::setTestNow($realNow);

    $data = $user->getSkillsOverTimeFromHistory(5);

    // Month 0 (Feb): 1 point (A=Low)
    // Month 1 (Mar): 1 point (A=Low)
    // Month 2 (Apr): 3 points (A=Low + B=Medium)
    // Month 3 (May): 5 points (A=High + B=Medium)
    // Month 4 (Jun): 5 points (still A=High + B=Medium)

    expect($data[0]['points'])->toBe(1);
    expect($data[1]['points'])->toBe(1);
    expect($data[2]['points'])->toBe(3);
    expect($data[3]['points'])->toBe(5);
    expect($data[4]['points'])->toBe(5);

    Carbon::setTestNow();
});

it('returns zeros for user with no skill history', function () {
    $user = User::factory()->create();

    $data = $user->getSkillsOverTimeFromHistory(3);

    expect($data)->toHaveCount(3);
    expect($data[0]['points'])->toBe(0);
    expect($data[1]['points'])->toBe(0);
    expect($data[2]['points'])->toBe(0);
});

it('returns correct month labels', function () {
    $user = User::factory()->create();

    Carbon::setTestNow(Carbon::parse('2024-06-15'));

    $data = $user->getSkillsOverTimeFromHistory(3);

    expect($data[0]['month'])->toBe('Apr');
    expect($data[1]['month'])->toBe('May');
    expect($data[2]['month'])->toBe('Jun');

    Carbon::setTestNow();
});
