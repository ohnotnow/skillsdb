<?php

namespace App\Services\SkillsCoach\Tools;

use App\Enums\SkillLevel;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use Prism\Prism\Tool;

class SearchByCategory extends Tool
{
    public function __construct(
        protected CoachContext $context
    ) {
        $this
            ->as('search_by_category')
            ->for('Find colleagues who are strong in a particular skill category')
            ->withStringParameter('category_name', 'The name of the category to search in')
            ->using($this);
    }

    public function __invoke(string $category_name): string
    {
        $currentUser = $this->context->getUserOrFail();

        $category = SkillCategory::where('name', 'like', "%{$category_name}%")->first();

        if (! $category) {
            $categories = SkillCategory::pluck('name')->toArray();

            return json_encode([
                'found' => false,
                'message' => "No category matching '{$category_name}' found.",
                'available_categories' => $categories,
            ]);
        }

        $skillIds = $category->skills()->pluck('id');

        $users = User::where('id', '!=', $currentUser->id)
            ->where('coach_contactable', true)
            ->where('is_staff', true)
            ->whereHas('skills', fn ($q) => $q->whereIn('skill_id', $skillIds))
            ->with(['skills' => fn ($q) => $q->whereIn('skill_id', $skillIds)])
            ->get()
            ->map(function ($u) {
                $skillCount = $u->skills->count();
                $highCount = $u->skills->filter(fn ($s) => $s->pivot->level === SkillLevel::High)->count();

                return [
                    'name' => $u->full_name,
                    'skills_in_category' => $skillCount,
                    'high_level_count' => $highCount,
                    'skills' => $u->skills->map(fn ($s) => [
                        'name' => $s->name,
                        'level' => $s->pivot->level->label(),
                    ])->toArray(),
                ];
            })
            ->sortByDesc('high_level_count')
            ->values()
            ->toArray();

        return json_encode([
            'category' => $category->name,
            'people' => $users,
            'count' => count($users),
        ], JSON_PRETTY_PRINT);
    }
}
