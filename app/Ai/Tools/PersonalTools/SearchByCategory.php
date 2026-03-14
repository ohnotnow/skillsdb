<?php

namespace App\Ai\Tools\PersonalTools;

use App\Enums\SkillLevel;
use App\Models\SkillCategory;
use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchByCategory implements Tool
{
    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return 'Find colleagues who are strong in a particular skill category';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'category_name' => $schema->string()->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $category_name = $request['category_name'];
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
