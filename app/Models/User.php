<?php

namespace App\Models;

use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
     * Get counts of skills at each level.
     *
     * @return array{low: int, medium: int, high: int, total: int}
     */
    public function getSkillDistribution(): array
    {
        $counts = $this->skills()
            ->selectRaw('level, count(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
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
}
