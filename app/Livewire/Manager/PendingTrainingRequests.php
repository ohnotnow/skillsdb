<?php

namespace App\Livewire\Manager;

use App\Enums\EnrollmentStatus;
use App\Mail\TrainingRequestApproved;
use App\Mail\TrainingRequestRejected;
use App\Models\TrainingCourseUser;
use Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PendingTrainingRequests extends Component
{
    public ?string $rejectionReason = null;

    public function mount(): void
    {
        if (! Auth::user()->isTeamManager()) {
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

    public function approve(int $enrollmentId): void
    {
        $enrollment = $this->getAuthorisedEnrollment($enrollmentId);

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
        $enrollment = $this->getAuthorisedEnrollment($enrollmentId);

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

    private function getAuthorisedEnrollment(int $enrollmentId): TrainingCourseUser
    {
        $enrollment = TrainingCourseUser::with(['user.teams', 'trainingCourse'])
            ->where('status', EnrollmentStatus::PendingApproval)
            ->findOrFail($enrollmentId);

        $managedTeamIds = Auth::user()->managedTeams()->pluck('id');
        $userTeamIds = $enrollment->user->teams->pluck('id');

        if ($managedTeamIds->intersect($userTeamIds)->isEmpty()) {
            abort(403);
        }

        return $enrollment;
    }

    public function render()
    {
        return view('livewire.manager.pending-training-requests');
    }
}
