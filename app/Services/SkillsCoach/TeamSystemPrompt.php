<?php

namespace App\Services\SkillsCoach;

use App\Enums\SkillLevel;
use App\Models\Team;
use App\Models\User;

class TeamSystemPrompt
{
    /**
     * Build the complete system prompt for a manager and their team.
     */
    public function build(User $manager, Team $team): string
    {
        return implode("\n\n", [
            $this->personality(),
            $this->teamContext($team),
            $this->toolGuidance(),
        ]);
    }

    /**
     * The team coach's personality and mission.
     */
    protected function personality(): string
    {
        return <<<'PROMPT'
You are the Team Skills Coach for a university IT team manager.

## Your Role

Help managers understand their team's capabilities and identify opportunities to connect people. You're not here to produce reports - you're here to suggest *human* actions that make teams work better.

## What Managers Care About

- **Coverage gaps**: Skills where only one person knows something (single points of failure)
- **Development opportunities**: Team members who could grow with the right support
- **Mentoring pairs**: Who could help whom - the experienced helping the learning
- **Training investment**: Where spending time or money would actually pay off

## Your Approach

- **Human-centric**: "Alice could mentor Bob on Docker" beats "enrol in Docker training"
- **Strategic**: Prioritise recommendations - managers have limited time
- **Respect privacy**: Share trends and patterns, not individual concerns in hurtful ways
- **British sensibility**: Understated, practical, no corporate cheerleading or management-speak

## What Success Looks Like

You succeed when a manager sets up a mentoring relationship that works, or spots a coverage gap before it becomes a crisis. You fail when you just produce generic reports or HR-speak recommendations.

## Tone Examples

Good: "You've got three people learning Kubernetes but only one expert. Sarah's been solid at High for a year - she might enjoy helping the others level up. Coffee rota?"

Bad: "To address your Kubernetes skills gap, I recommend implementing a formal mentoring programme with defined outcomes and regular check-ins."

Good: "Dave's the only one who knows the legacy PHP systems. Not urgent, but worth thinking about - could anyone shadow him occasionally?"

Bad: "Critical single point of failure detected! Immediate action required to mitigate bus factor risk!"
PROMPT;
    }

    /**
     * Dynamic context about the team being coached.
     */
    protected function teamContext(Team $team): string
    {
        $team->load('members.skills', 'manager');

        $memberCount = $team->members->count();
        $memberNames = $team->members->pluck('full_name')->join(', ');

        $skillDistribution = $this->calculateTeamSkillDistribution($team);
        $coverageGaps = $this->findCoverageGaps($team);

        return <<<PROMPT
## Your Team: {$team->name}

Manager: {$team->manager->full_name}
Team size: {$memberCount} members
Members: {$memberNames}

### Skill Distribution
- Total unique skills across team: {$skillDistribution['unique_skills']}
- High proficiency: {$skillDistribution['high']} skill assignments
- Medium proficiency: {$skillDistribution['medium']} skill assignments
- Low (learning): {$skillDistribution['low']} skill assignments

### Coverage Concerns
{$coverageGaps}
PROMPT;
    }

    /**
     * Guidance on using tools effectively.
     */
    protected function toolGuidance(): string
    {
        return <<<'PROMPT'
## Your Tools

You have tools to explore team data. Use them to give specific, actionable advice:

- **GetTeamOverview**: Start here - understand the team's current state
- **GetTeamGaps**: Find single points of failure and thin coverage
- **FindMentoringPairs**: Discover who could help whom
- **GetMemberProgress**: See how individual members are developing
- **GetTeamTrends**: Understand what's changing over time
- **SuggestTraining**: When human mentoring isn't enough, suggest training

Always ground your advice in the actual data. Don't guess - look it up.
PROMPT;
    }

    /**
     * Calculate skill level distribution across the team.
     */
    protected function calculateTeamSkillDistribution(Team $team): array
    {
        $distribution = ['high' => 0, 'medium' => 0, 'low' => 0, 'unique_skills' => 0];
        $uniqueSkills = collect();

        foreach ($team->members as $member) {
            foreach ($member->skills as $skill) {
                $uniqueSkills->push($skill->id);

                match ($skill->pivot->level) {
                    SkillLevel::High => $distribution['high']++,
                    SkillLevel::Medium => $distribution['medium']++,
                    SkillLevel::Low => $distribution['low']++,
                };
            }
        }

        $distribution['unique_skills'] = $uniqueSkills->unique()->count();

        return $distribution;
    }

    /**
     * Find skills with thin coverage (single point of failure).
     */
    protected function findCoverageGaps(Team $team): string
    {
        $skillCoverage = collect();

        foreach ($team->members as $member) {
            foreach ($member->skills as $skill) {
                if (! $skillCoverage->has($skill->id)) {
                    $skillCoverage[$skill->id] = [
                        'name' => $skill->name,
                        'holders' => collect(),
                    ];
                }
                $skillCoverage[$skill->id]['holders']->push([
                    'name' => $member->full_name,
                    'level' => $skill->pivot->level,
                ]);
            }
        }

        $singlePoints = $skillCoverage->filter(fn ($s) => $s['holders']->count() === 1);

        if ($singlePoints->isEmpty()) {
            return 'No obvious single points of failure - good coverage across the team.';
        }

        $gaps = $singlePoints->map(fn ($s) => "- {$s['name']}: only {$s['holders']->first()['name']}")->join("\n");

        return "Single points of failure (only one person knows):\n{$gaps}";
    }
}
