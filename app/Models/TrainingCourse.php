<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrainingCourse extends Model
{
    /** @use HasFactory<\Database\Factories\TrainingCourseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'prerequisites',
        'cost',
        'offers_certification',
        'training_supplier_id',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'offers_certification' => 'boolean',
        ];
    }

    // Relationships

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TrainingSupplier::class, 'training_supplier_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'skill_training_course')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'training_course_user')
            ->using(TrainingCourseUser::class)
            ->withPivot(['status', 'rating'])
            ->withTimestamps();
    }

    // Custom methods

    public function isFree(): bool
    {
        return $this->cost === null || (float) $this->cost === 0.0;
    }
}
