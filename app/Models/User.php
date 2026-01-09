<?php

namespace App\Models;

use App\Enums\SkillLevel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
            ->withPivot('level')
            ->withTimestamps();
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

        return $pivot ? SkillLevel::from($pivot->level) : null;
    }
}
