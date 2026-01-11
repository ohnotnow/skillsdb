<?php

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillHistory;
use App\Models\User;

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
