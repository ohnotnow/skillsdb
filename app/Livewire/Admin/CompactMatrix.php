<?php

namespace App\Livewire\Admin;

use App\Models\Skill;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CompactMatrix extends Component
{
    #[Computed]
    public function users()
    {
        return User::with('skills')
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'initials' => $this->getInitials($user->forenames, $user->surname),
                'fullName' => $user->full_name,
                'skills' => $user->skills->mapWithKeys(fn ($skill) => [
                    $skill->id => $skill->pivot->level?->value,
                ])->toArray(),
            ]);
    }

    #[Computed]
    public function skills()
    {
        return Skill::approved()
            ->orderBy('name')
            ->get()
            ->map(fn ($skill) => [
                'id' => $skill->id,
                'abbr' => $this->getAbbreviation($skill->name),
                'fullName' => $skill->name,
            ]);
    }

    private function getInitials(string $forenames, string $surname): string
    {
        $firstInitial = mb_substr($forenames, 0, 1);
        $lastInitial = mb_substr($surname, 0, 1);

        return strtoupper($firstInitial.$lastInitial);
    }

    private function getAbbreviation(string $name): string
    {
        // If it's an acronym or short already, use as-is
        if (strlen($name) <= 4) {
            return $name;
        }

        // If it has multiple words, take first letter of each (up to 4)
        $words = preg_split('/[\s\/\-]+/', $name);
        if (count($words) > 1) {
            $abbr = '';
            foreach (array_slice($words, 0, 4) as $word) {
                $abbr .= mb_substr($word, 0, 1);
            }

            return strtoupper($abbr);
        }

        // Single word - take first 3 chars
        return mb_substr($name, 0, 3);
    }

    public function render()
    {
        return view('livewire.admin.compact-matrix');
    }
}
