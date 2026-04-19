<?php

namespace App\Ai\Agents;

use App\Ai\Tools\FindTrainingCourses;
use App\Ai\Tools\PersonalTools\FindByInterest;
use App\Ai\Tools\PersonalTools\FindExperts;
use App\Ai\Tools\PersonalTools\FindSkillSharers;
use App\Ai\Tools\PersonalTools\GetSkillJourney;
use App\Ai\Tools\PersonalTools\GetTeamGaps;
use App\Ai\Tools\PersonalTools\GetTrendingSkills;
use App\Ai\Tools\PersonalTools\GetUserProfile;
use App\Ai\Tools\PersonalTools\GetUserProgress;
use App\Ai\Tools\PersonalTools\SearchByCategory;
use App\Services\SkillsCoach\CoachContext;
use App\Services\SkillsCoach\SystemPrompt;
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
class PersonalCoachAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(
        protected SystemPrompt $systemPrompt,
        protected CoachContext $context,
    ) {}

    public function instructions(): string
    {
        $user = $this->context->getUserOrFail();

        return $this->systemPrompt->build($user);
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
            app(GetUserProfile::class),
            app(FindExperts::class),
            app(FindSkillSharers::class),
            app(FindByInterest::class),
            app(GetTrendingSkills::class),
            app(GetTeamGaps::class),
            app(GetSkillJourney::class),
            app(SearchByCategory::class),
            app(GetUserProgress::class),
            app(FindTrainingCourses::class),
        ];
    }
}
