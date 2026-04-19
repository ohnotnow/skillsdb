<?php

namespace App\Ai\Tools\PersonalTools;

use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FindByInterest implements Tool
{
    public function __construct(
        protected CoachContext $context
    ) {}

    public function description(): string
    {
        return "Find colleagues who mention an interest, hobby, side project, past experience or technology in their personal bio. Use this for informal topics that may not be a formal Skill (e.g. 'Raspberry Pi', 'ESP32', 'home automation', 'Solaris', 'allotment', 'retro computing').";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'interest' => $schema->string()
                ->description("A word or short phrase to look for in team members' bios")
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        $interest = trim((string) $request['interest']);
        $currentUser = $this->context->getUserOrFail();

        if ($interest === '') {
            return json_encode(['count' => 0, 'matches' => []]);
        }

        $matches = User::query()
            ->where('id', '!=', $currentUser->id)
            ->where('is_staff', true)
            ->where('coach_contactable', true)
            ->whereNotNull('bio')
            ->where('bio', 'like', "%{$interest}%")
            ->get()
            ->map(fn ($u) => [
                'name' => $u->full_name,
                'bio' => $u->bio,
            ])
            ->values()
            ->toArray();

        return json_encode([
            'interest' => $interest,
            'count' => count($matches),
            'matches' => $matches,
        ], JSON_PRETTY_PRINT);
    }
}
