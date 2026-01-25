<?php

namespace App\Livewire;

use App\Enums\EnrollmentStatus;
use App\Enums\TrainingRating;
use App\Mail\TrainingApprovalRequested;
use App\Models\TrainingCourse;
use Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class TrainingBrowser extends Component
{
    #[Url]
    public $search = '';

    #[Url]
    public $showMyEnrollments = false;

    #[Computed]
    public function courses()
    {
        $userId = Auth::id();

        return TrainingCourse::query()
            ->active()
            ->with(['supplier', 'skills'])
            ->withRatingCounts()
            ->withCount([
                'users as total_enrollments',
            ])
            ->with(['users' => fn ($q) => $q->where('user_id', $userId)])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->showMyEnrollments, function ($query) use ($userId) {
                $query->whereHas('users', fn ($q) => $q->where('user_id', $userId));
            })
            ->orderBy('name')
            ->get();
    }

    public function enroll(int $courseId): void
    {
        $course = TrainingCourse::active()->findOrFail($courseId);
        $user = Auth::user();

        if ($user->trainingCourses()->where('training_course_id', $courseId)->exists()) {
            return;
        }

        if ($course->isFree()) {
            $user->trainingCourses()->attach($courseId, [
                'status' => EnrollmentStatus::Booked,
            ]);

            Flux::toast(
                variant: 'success',
                heading: 'Enrolled!',
                text: "You've booked a place on {$course->name}.",
            );

            return;
        }

        // Paid course - requires manager approval
        $user->trainingCourses()->attach($courseId, [
            'status' => EnrollmentStatus::PendingApproval,
            'requested_at' => now(),
        ]);

        foreach ($user->getManagers() as $manager) {
            Mail::to($manager)->send(new TrainingApprovalRequested($user, $course));
        }

        Flux::toast(
            variant: 'info',
            heading: 'Request submitted',
            text: "Your request to enroll on {$course->name} has been sent to your manager for approval.",
        );
    }

    public function markCompleted(int $courseId): void
    {
        $course = TrainingCourse::active()->findOrFail($courseId);
        $user = Auth::user();

        $user->trainingCourses()->updateExistingPivot($courseId, [
            'status' => EnrollmentStatus::Completed,
        ]);

        Flux::toast(
            variant: 'success',
            heading: 'Marked complete!',
            text: "You've completed {$course->name}.",
        );
    }

    public function unenroll(int $courseId): void
    {
        $course = TrainingCourse::active()->findOrFail($courseId);
        $user = Auth::user();

        $enrollment = $user->trainingCourses()
            ->where('training_course_id', $courseId)
            ->first();

        if (! $enrollment || $enrollment->pivot->status !== EnrollmentStatus::Booked) {
            return;
        }

        $user->trainingCourses()->detach($courseId);

        Flux::toast(
            variant: 'success',
            heading: 'Cancelled',
            text: "You've cancelled your booking for {$course->name}.",
        );
    }

    public function setRating(int $courseId, int $rating): void
    {
        $user = Auth::user();

        $enrollment = $user->trainingCourses()
            ->where('training_course_id', $courseId)
            ->first();

        if (! $enrollment || $enrollment->pivot->status !== EnrollmentStatus::Completed) {
            return;
        }

        $user->trainingCourses()->updateExistingPivot($courseId, [
            'rating' => TrainingRating::from($rating),
        ]);

        Flux::toast(
            variant: 'success',
            heading: 'Thanks!',
            text: 'Your rating has been saved.',
        );
    }

    public function render()
    {
        return view('livewire.training-browser');
    }
}
