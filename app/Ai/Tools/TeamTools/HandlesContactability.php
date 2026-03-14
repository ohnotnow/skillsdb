<?php

namespace App\Ai\Tools\TeamTools;

use App\Models\User;
use App\Services\SkillsCoach\CoachContext;

/**
 * Handles coach_contactable logic for team tools.
 *
 * Logic:
 * - If coach_contactable = true → show person normally
 * - If coach_contactable = false AND they're in a team managed by current user → show normally
 * - If coach_contactable = false AND different manager → add note about contacting their manager
 */
trait HandlesContactability
{
    abstract protected function getContext(): CoachContext;

    /**
     * Check if a person is a direct report of the current manager.
     */
    protected function isDirectReport(User $person, User $manager): bool
    {
        $managedTeamIds = $manager->managedTeams()->pluck('id');

        return $person->teams()->whereIn('teams.id', $managedTeamIds)->exists();
    }

    /**
     * Get contactability note for a person, if applicable.
     * Returns null if person can be contacted directly.
     */
    protected function getContactabilityNote(User $person): ?string
    {
        if ($person->coach_contactable) {
            return null;
        }

        $currentManager = $this->getContext()->getUser();

        if ($currentManager && $this->isDirectReport($person, $currentManager)) {
            return null;
        }

        $theirManager = $person->teams->first()?->manager;

        if ($theirManager) {
            return "Consider reaching out to their manager {$theirManager->full_name} first";
        }

        return null;
    }

    /**
     * Format a person entry with contactability awareness.
     * Adds a 'note' key if the person has opted out of direct contact.
     */
    protected function formatPersonWithContactability(User $person, array $baseEntry = []): array
    {
        $entry = array_merge(['name' => $person->full_name], $baseEntry);

        $note = $this->getContactabilityNote($person);
        if ($note) {
            $entry['contact_note'] = $note;
        }

        return $entry;
    }
}
