<?php

namespace App\Models;

use App\Enums\TrainingRating;
use Database\Factories\TrainingCourseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrainingCourse extends Model
{
    /** @use HasFactory<TrainingCourseFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'prerequisites',
        'cost',
        'offers_certification',
        'training_supplier_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'offers_certification' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected function trainingSupplierId(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ?: null,
        );
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
            ->withPivot(['status', 'rating', 'requested_at', 'approved_by', 'approved_at', 'rejection_reason'])
            ->withTimestamps();
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithRatingCounts($query)
    {
        return $query->withCount([
            'users as good_count' => fn ($q) => $q->where('rating', TrainingRating::Good),
            'users as indifferent_count' => fn ($q) => $q->where('rating', TrainingRating::Indifferent),
            'users as bad_count' => fn ($q) => $q->where('rating', TrainingRating::Bad),
        ]);
    }

    // Custom methods

    public function hasEnrollments(): bool
    {
        return $this->users()->exists();
    }

    public function isFree(): bool
    {
        return ! (bool) $this->cost;
    }
}
