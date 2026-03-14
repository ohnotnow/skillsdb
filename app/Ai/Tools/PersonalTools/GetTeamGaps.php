<?php

namespace App\Ai\Tools\PersonalTools;

use App\Models\Skill;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetTeamGaps implements Tool
{
    public function description(): string
    {
        return 'Find skills where the team has thin coverage - opportunities for someone to become valuable';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'max_users' => $schema->integer()->min(1)->max(10),
        ];
    }

    public function handle(Request $request): string
    {
        $max_users = $request['max_users'] ?? 2;

        $allSkills = Skill::approved()->withCount('users')->get();

        $gaps = $allSkills
            ->filter(fn ($s) => $s->users_count >= 1 && $s->users_count <= $max_users)
            ->sortBy('users_count')
            ->take(15)
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name ?? 'Uncategorised',
                'users_count' => $s->users_count,
            ])
            ->values()
            ->toArray();

        $noOneSkills = $allSkills
            ->filter(fn ($s) => $s->users_count === 0)
            ->take(10)
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->category?->name ?? 'Uncategorised',
            ])
            ->values()
            ->toArray();

        return json_encode([
            'thin_coverage' => $gaps,
            'no_coverage' => $noOneSkills,
            'message' => 'Skills with thin coverage are opportunities - becoming proficient makes you valuable.',
        ], JSON_PRETTY_PRINT);
    }
}
