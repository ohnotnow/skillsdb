<?php

namespace App\Services\SkillsCoach\Providers;

use App\Models\User;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\Contracts\LlmProvider;
use App\Services\SkillsCoach\SystemPrompt;
use App\Services\SkillsCoach\TeamSystemPrompt;
use App\Services\SkillsCoach\TeamTools\FindBackupFor;
use App\Services\SkillsCoach\TeamTools\FindMentoringPairs;
use App\Services\SkillsCoach\TeamTools\GetMemberSkills;
use App\Services\SkillsCoach\TeamTools\GetRecentActivity;
use App\Services\SkillsCoach\TeamTools\GetTeamOverview;
use App\Services\SkillsCoach\TeamTools\SuggestTraining;
use App\Services\SkillsCoach\Tools\FindExperts;
use App\Services\SkillsCoach\Tools\FindSkillSharers;
use App\Services\SkillsCoach\Tools\GetSkillJourney;
use App\Services\SkillsCoach\Tools\GetTeamGaps;
use App\Services\SkillsCoach\Tools\GetTrendingSkills;
use App\Services\SkillsCoach\Tools\GetUserProfile;
use App\Services\SkillsCoach\Tools\GetUserProgress;
use App\Services\SkillsCoach\Tools\SearchByCategory;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Facades\Tool;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class PrismProvider implements LlmProvider
{
    public function __construct(
        protected SystemPrompt $systemPrompt,
        protected TeamSystemPrompt $teamSystemPrompt,
        protected CoachContext $context
    ) {}

    public function chat(string $userMessage, array $conversationHistory, User $user): string
    {
        // Set the user context for tools
        $this->context->setUser($user);

        $messages = $this->buildMessages($conversationHistory);

        // Add the new user message
        $messages[] = new UserMessage($userMessage);

        // Select system prompt and tools based on mode
        $systemPrompt = $this->buildSystemPrompt($user);
        $tools = $this->context->isTeamMode()
            ? $this->buildTeamTools()
            : $this->buildPersonalTools();

        $response = Prism::text()
            ->using(Provider::Anthropic, config('services.skills_coach.model', 'claude-sonnet-4-20250514'))
            ->withSystemPrompt($systemPrompt)
            ->withMessages($messages)
            ->withMaxTokens(4096)
            ->withMaxSteps(5)
            ->withTools($tools)
            ->asText();

        return $response->text;
    }

    /**
     * Build the appropriate system prompt based on mode.
     */
    protected function buildSystemPrompt(User $user): string
    {
        if ($this->context->isTeamMode()) {
            $team = $this->context->getTeam();

            return $this->teamSystemPrompt->build($user, $team);
        }

        return $this->systemPrompt->build($user);
    }

    /**
     * Convert conversation history array to Prism message objects.
     *
     * @return array<UserMessage|AssistantMessage>
     */
    protected function buildMessages(array $history): array
    {
        $messages = [];

        foreach ($history as $msg) {
            /** @phpstan-ignore-next-line match.unhandled */
            $messages[] = match ($msg['role']) {
                'user' => new UserMessage($msg['content']),
                'assistant' => new AssistantMessage($msg['content']),
            };
        }

        return $messages;
    }

    /**
     * Build the tools available for personal coaching.
     *
     * @return array<\Prism\Prism\Tool>
     */
    protected function buildPersonalTools(): array
    {
        return [
            Tool::make(GetUserProfile::class),
            Tool::make(FindExperts::class),
            Tool::make(FindSkillSharers::class),
            Tool::make(GetTrendingSkills::class),
            Tool::make(GetTeamGaps::class),
            Tool::make(GetSkillJourney::class),
            Tool::make(SearchByCategory::class),
            Tool::make(GetUserProgress::class),
        ];
    }

    /**
     * Build the tools available for team coaching.
     *
     * @return array<\Prism\Prism\Tool>
     */
    protected function buildTeamTools(): array
    {
        return [
            Tool::make(GetTeamOverview::class),
            Tool::make(FindBackupFor::class),
            Tool::make(FindMentoringPairs::class),
            Tool::make(GetMemberSkills::class),
            Tool::make(GetRecentActivity::class),
            Tool::make(SuggestTraining::class),
        ];
    }
}
