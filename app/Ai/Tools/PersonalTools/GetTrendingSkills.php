<?php

namespace App\Ai\Tools\PersonalTools;

use App\Models\Skill;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetTrendingSkills implements Tool
{
    public function description(): string
    {
        return 'Get skills that are trending - being added or levelled up recently by the team';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->min(1)->max(50),
        ];
    }

    public function handle(Request $request): string
    {
        $limit = $request['limit'] ?? 10;

        $trending = Skill::getTrendingSkills(limit: $limit);

        $results = $trending->map(fn ($s) => [
            'name' => $s->name,
            'category' => $s->category?->name ?? 'Uncategorised',
            'recent_additions' => $s->recent_count ?? 0,
        ])->toArray();

        return json_encode([
            'trending' => $results,
            'count' => count($results),
            'period' => 'last 30 days',
        ], JSON_PRETTY_PRINT);
    }
}
