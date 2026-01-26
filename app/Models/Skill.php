<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    /** @use HasFactory<\Database\Factories\SkillFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'skill_category_id',
        'parent_id',
        'approved_by',
        'approved_at',
        'is_reportable',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'is_reportable' => 'boolean',
        ];
    }

    // Relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'skill_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Skill::class, 'parent_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(SkillUser::class)
            ->withPivot('level')
            ->withTimestamps();
    }

    public function trainingCourses(): BelongsToMany
    {
        return $this->belongsToMany(TrainingCourse::class, 'skill_training_course')
            ->withTimestamps();
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    // Custom methods

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function isPending(): bool
    {
        return $this->approved_at === null;
    }

    /**
     * Get skills that have been added by users recently, ordered by popularity.
     *
     * @param  int  $days  Number of days to look back
     * @param  int  $limit  Maximum number of skills to return
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function getTrendingSkills(int $days = 30, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->approved()
            ->withCount(['users as recent_additions_count' => function ($query) use ($days) {
                $query->where('skill_user.created_at', '>=', now()->subDays($days));
            }])
            ->get()
            ->filter(fn ($skill) => $skill->recent_additions_count > 0)
            ->sortByDesc('recent_additions_count')
            ->take($limit)
            ->values();
    }
}
