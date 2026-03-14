<?php

namespace App\Ai\Agents;

use App\Ai\Tools\FindTrainingCourses;
use App\Ai\Tools\TeamTools\FindBackupFor;
use App\Ai\Tools\TeamTools\FindMentoringPairs;
use App\Ai\Tools\TeamTools\GetMemberSkills;
use App\Ai\Tools\TeamTools\GetRecentActivity;
use App\Ai\Tools\TeamTools\GetTeamOverview;
use App\Ai\Tools\TeamTools\SuggestTraining;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\TeamSystemPrompt;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[MaxSteps(5)]
#[MaxTokens(4096)]
class TeamCoachAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        protected TeamSystemPrompt $teamSystemPrompt,
        protected CoachContext $context,
    ) {}

    public function instructions(): string
    {
        $user = $this->context->getUserOrFail();
        $team = $this->context->getTeam();

        return $this->teamSystemPrompt->build($user, $team);
    }

    public function provider(): array|Lab|string
    {
        return config('services.skills_coach.provider', Lab::OpenAI);
    }

    public function model(): string
    {
        return config('services.skills_coach.model', 'gpt-5.4');
    }

    public function tools(): iterable
    {
        return [
            app(GetTeamOverview::class),
            app(FindBackupFor::class),
            app(FindMentoringPairs::class),
            app(GetMemberSkills::class),
            app(GetRecentActivity::class),
            app(SuggestTraining::class),
            app(FindTrainingCourses::class),
        ];
    }
}
