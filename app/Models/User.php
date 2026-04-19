<?php

namespace App\Models;

use App\Enums\SkillHistoryEvent;
use App\Enums\SkillLevel;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'forenames',
        'surname',
        'is_staff',
        'is_admin',
        'email',
        'password',
        'last_updated_skills_at',
        'coach_contactable',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
            'is_admin' => 'boolean',
            'last_updated_skills_at' => 'datetime',
            'coach_contactable' => 'boolean',
        ];
    }

    // Relationships

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)
            ->using(SkillUser::class)
            ->withPivot('level')
            ->withTimestamps();
    }

    public function skillHistory(): HasMany
    {
        return $this->hasMany(SkillHistory::class)->latest('id');
    }

    public function agentConversations(): HasMany
    {
        return $this->hasMany(AgentConversation::class)->latest('updated_at');
    }

    public function trainingCourses(): BelongsToMany
    {
        return $this->belongsToMany(TrainingCourse::class, 'training_course_user')
            ->using(TrainingCourseUser::class)
            ->withPivot(['status', 'rating', 'requested_at', 'approved_by', 'approved_at', 'rejection_reason'])
            ->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withTimestamps();
    }

    public function managedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'manager_id');
    }

    // Accessors

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->forenames} {$this->surname}");
    }

    protected function shortName(): Attribute
    {
        return Attribute::get(fn () => substr($this->forenames, 0, 1).'. '.$this->surname);
    }

    // Custom methods

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    public function isTeamManager(): bool
    {
        return $this->managedTeams()->exists();
    }

    /**
     * Get all managers of teams this user belongs to.
     *
     * @return Collection<int, User>
     */
    public function getManagers(): Collection
    {
        $managerIds = $this->teams()->pluck('manager_id')->filter()->unique();

        return User::whereIn('id', $managerIds)->get();
    }

    public function touchSkillsUpdatedAt(): void
    {
        $this->update(['last_updated_skills_at' => now()]);
    }

    public function getSkillLevel(Skill $skill): ?SkillLevel
    {
        $pivot = $this->skills->find($skill->id)?->pivot;

        return $pivot?->level;
    }

    /**
     * Get a user's skill level at a specific point in time by replaying history.
     */
    public function getSkillLevelAt(Skill $skill, Carbon $date): ?SkillLevel
    {
        $latestEvent = SkillHistory::where('user_id', $this->id)
            ->where('skill_id', $skill->id)
            ->where('created_at', '<=', $date)
            ->orderByDesc('created_at')
            ->first();

        if (! $latestEvent || $latestEvent->event_type === SkillHistoryEvent::Removed) { /** @phpstan-ignore identical.alwaysFalse (event_type is cast to enum but PHPStan sees string from PHPDoc) */
            return null;
        }

        return $latestEvent->new_level ? SkillLevel::from($latestEvent->new_level) : null;
    }

    /**
     * Get counts of skills at each level.
     *
     * @return array{low: int, medium: int, high: int, total: int}
     */
    public function getSkillDistribution(): array
    {
        $counts = $this->skills()
            ->pluck('skill_user.level')
            ->countBy()
            ->toArray();

        return [
            'low' => $counts[SkillLevel::Low->value] ?? 0,
            'medium' => $counts[SkillLevel::Medium->value] ?? 0,
            'high' => $counts[SkillLevel::High->value] ?? 0,
            'total' => array_sum($counts),
        ];
    }

    /**
     * Check if skills haven't been updated in over 4 weeks.
     */
    public function hasStaleSkills(): bool
    {
        if (! $this->last_updated_skills_at) {
            return true;
        }

        return $this->last_updated_skills_at->lt(now()->subWeeks(4));
    }

    /**
     * Get human-readable text for when skills were last updated.
     * Returns italic "ages ago" if stale (>4 weeks).
     */
    public function getLastUpdatedText(): string
    {
        if (! $this->last_updated_skills_at) {
            return '<em>never</em>';
        }

        if ($this->hasStaleSkills()) {
            return '<em>ages ago</em>';
        }

        return $this->last_updated_skills_at->diffForHumans();
    }

    /**
     * Get cumulative skill points for each of the last N months.
     * Returns an array of associative arrays with month label and points.
     * Points are calculated as: Low=1, Medium=2, High=3.
     *
     * NOTE: This method is inaccurate - it projects current levels backwards.
     * Use getSkillsOverTimeFromHistory() for accurate historical data.
     *
     * @return array<int, array{month: string, points: int}>
     */
    public function getSkillsOverTime(int $months = 6): array
    {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $endOfMonth = now()->subMonths($i)->endOfMonth();

            $data[] = [
                'month' => $endOfMonth->format('M'),
                'points' => (int) $this->skills()
                    ->wherePivot('created_at', '<=', $endOfMonth)
                    ->sum('skill_user.level'),
            ];
        }

        return $data;
    }

    /**
     * Get accurate cumulative skill points for each of the last N months.
     * Uses SkillHistory to replay events and calculate actual points at each point in time.
     * Points are calculated as: Low=1, Medium=2, High=3.
     *
     * @return array<int, array{month: string, points: int, events: array<string>, eventText: string}>
     */
    public function getSkillsOverTimeFromHistory(int $months = 6): array
    {
        $data = [];
        $history = $this->skillHistory()
            ->reorder()
            ->orderBy('created_at')
            ->with('skill')
            ->get();

        $historyBySkill = $history->groupBy('skill_id');

        for ($i = $months - 1; $i >= 0; $i--) {
            $endOfMonth = now()->subMonths($i)->endOfMonth();
            $startOfMonth = now()->subMonths($i)->startOfMonth();
            $points = 0;
            $eventDescriptions = [];

            foreach ($historyBySkill as $events) {
                // Calculate points: find latest event before end of month
                $latestEvent = $events
                    ->where('created_at', '<=', $endOfMonth)
                    ->last();

                if ($latestEvent && $latestEvent->event_type !== SkillHistoryEvent::Removed) {
                    $points += $latestEvent->new_level ?? 0;
                }

                // Collect event descriptions for THIS month
                $monthEvents = $events->filter(
                    fn ($e) => $e->created_at >= $startOfMonth && $e->created_at <= $endOfMonth
                );

                foreach ($monthEvents as $event) {
                    $eventDescriptions[] = $event->skill->name.' - '.$event->event_type->label();
                }
            }

            $data[] = [
                'month' => $endOfMonth->format('M'),
                'points' => $points,
                'events' => $eventDescriptions,
                'eventText' => empty($eventDescriptions) ? 'No changes' : implode(', ', $eventDescriptions),
            ];
        }

        return $data;
    }
}
