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
        return view('prompts.skills-coach.team-personality')->render();
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
        return view('prompts.skills-coach.tool-guidance')->render();
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

                /** @phpstan-ignore-next-line match.unhandled */
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
