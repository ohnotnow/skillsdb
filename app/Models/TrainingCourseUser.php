<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\TrainingRating;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TrainingCourseUser extends Pivot
{
    protected $table = 'training_course_user';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'rating' => TrainingRating::class,
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingCourse(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class);
    }
}
