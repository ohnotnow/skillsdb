<?php

namespace App\Models;

use App\Enums\FluxColour;
use Database\Factories\SkillCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillCategory extends Model
{
    /** @use HasFactory<SkillCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'colour',
    ];

    protected function casts(): array
    {
        return [
            'colour' => FluxColour::class,
        ];
    }

    // Relationships

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
