<?php

namespace App\Livewire\Manager;

use App\Enums\EnrollmentStatus;
use App\Mail\TeamMemberEnrolled;
use App\Mail\TrainingRequestApproved;
use App\Mail\TrainingRequestRejected;
use App\Models\TrainingCourse;
use App\Models\TrainingCourseUser;
use App\Models\User;
use Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PendingTrainingRequests extends Component
{
    #[Url]
    public $tab = 'approvals';

    public ?string $rejectionReason = null;

    public ?User $enrollingUser = null;

    public array $coursesToEnroll = [];

    public function mount(): void
    {
        if (! Auth::user()->isTeamManager() && ! Auth::user()->isAdmin()) {
            abort(403);
        }
    }

    #[Computed]
    public function pendingRequests()
    {
        return TrainingCourseUser::query()
            ->where('status', EnrollmentStatus::PendingApproval)
            ->whereHas('user', function ($q) {
                $q->whereHas('teams', function ($q) {
                    $q->where('manager_id', Auth::id());
                });
            })
            ->with(['user', 'trainingCourse'])
            ->orderBy('requested_at')
            ->get();
    }

    #[Computed]
    public function teamMembers()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return User::query()
                ->where('is_staff', true)
                ->orderBy('surname')
                ->orderBy('forenames')
                ->get();
        }

        return User::query()
            ->whereHas('teams', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            })
            ->orderBy('surname')
            ->orderBy('forenames')
            ->get();
    }

    #[Computed]
    public function availableCoursesForEnrolling()
    {
        if (! $this->enrollingUser) {
            return collect();
        }

        $alreadyEnrolledIds = $this->enrollingUser->trainingCourses()->pluck('training_course_id');

        return TrainingCourse::active()
            ->whereNotIn('id', $alreadyEnrolledIds)
            ->orderBy('name')
            ->get();
    }

    public function approve(int $enrollmentId): void
    {
        $enrollment = TrainingCourseUser::with(['user', 'trainingCourse'])
            ->where('status', EnrollmentStatus::PendingApproval)
            ->findOrFail($enrollmentId);

        $enrollment->update([
            'status' => EnrollmentStatus::Booked,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        Mail::to($enrollment->user)->send(
            new TrainingRequestApproved($enrollment->trainingCourse, Auth::user())
        );

        Flux::toast(
            variant: 'success',
            heading: 'Approved',
            text: "{$enrollment->user->full_name}'s request has been approved.",
        );
    }

    public function reject(int $enrollmentId, ?string $reason = null): void
    {
        $enrollment = TrainingCourseUser::with(['user', 'trainingCourse'])
            ->where('status', EnrollmentStatus::PendingApproval)
            ->findOrFail($enrollmentId);

        $enrollment->update([
            'status' => EnrollmentStatus::Rejected,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        Mail::to($enrollment->user)->send(
            new TrainingRequestRejected($enrollment->trainingCourse, Auth::user(), $reason)
        );

        Flux::toast(
            variant: 'success',
            heading: 'Rejected',
            text: "{$enrollment->user->full_name}'s request has been rejected.",
        );

        $this->rejectionReason = null;
    }

    public function openEnrollModal(int $userId): void
    {
        $this->enrollingUser = $this->teamMembers->firstWhere('id', $userId);
        $this->coursesToEnroll = [];
    }

    public function closeEnrollModal(): void
    {
        $this->enrollingUser = null;
        $this->coursesToEnroll = [];
    }

    public function enrollTeamMember(): void
    {
        $courses = TrainingCourse::active()
            ->whereIn('id', $this->coursesToEnroll)
            ->get();

        foreach ($courses as $course) {
            $this->enrollingUser->trainingCourses()->attach($course->id, [
                'status' => EnrollmentStatus::Booked,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        }

        Mail::to($this->enrollingUser)->send(
            new TeamMemberEnrolled($courses, Auth::user())
        );

        $name = $this->enrollingUser->full_name;

        $this->closeEnrollModal();

        Flux::toast(
            variant: 'success',
            heading: 'Enrolled',
            text: "{$name} has been enrolled.",
        );
    }

    public function render()
    {
        return view('livewire.manager.pending-training-requests');
    }
}
