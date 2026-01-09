<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    /** @use HasFactory<\Database\Factories\SkillFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'skill_category_id',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    // Relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class, 'skill_category_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('level')
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
}
