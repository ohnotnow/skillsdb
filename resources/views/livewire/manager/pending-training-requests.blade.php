<div>
    <flux:heading size="xl" level="1">Team Training</flux:heading>

    <flux:text class="mt-2 mb-6">
        Manage training course enrollments for your team members.
    </flux:text>

    <flux:tab.group>
        <flux:tabs wire:model="tab">
            <flux:tab name="approvals" icon="clipboard-document-check">Pending Approvals</flux:tab>
            <flux:tab name="enroll" icon="user-plus">Enroll Team</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="approvals">
            @if($this->pendingRequests->isEmpty())
                <flux:callout icon="check-circle" variant="success">
                    No pending training requests.
                </flux:callout>
            @else
                <div class="space-y-4">
                    @foreach($this->pendingRequests as $enrollment)
                        <flux:card wire:key="enrollment-{{ $enrollment->id }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <flux:heading size="lg">{{ $enrollment->trainingCourse->name }}</flux:heading>
                                    <flux:text class="mt-1">
                                        Requested by <strong>{{ $enrollment->user->full_name }}</strong>
                                        @if($enrollment->requested_at)
                                            <span class="text-zinc-500">{{ $enrollment->requested_at->diffForHumans() }}</span>
                                        @endif
                                    </flux:text>
                                    @if($enrollment->trainingCourse->cost)
                                        <flux:badge variant="outline" class="mt-2">
                                            Cost: {{ $enrollment->trainingCourse->cost }}
                                        </flux:badge>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <flux:button variant="primary" wire:click="approve({{ $enrollment->id }})">
                                        Approve
                                    </flux:button>
                                    <flux:modal.trigger name="reject-{{ $enrollment->id }}">
                                        <flux:button variant="danger">Reject</flux:button>
                                    </flux:modal.trigger>
                                </div>
                            </div>
                        </flux:card>

                        <flux:modal name="reject-{{ $enrollment->id }}" class="md:w-96">
                            <div class="space-y-4">
                                <flux:heading size="lg">Reject Request</flux:heading>
                                <flux:text>
                                    Reject {{ $enrollment->user->full_name }}'s request for {{ $enrollment->trainingCourse->name }}?
                                </flux:text>
                                <flux:textarea
                                    wire:model="rejectionReason"
                                    label="Reason (optional)"
                                    placeholder="Explain why this request was rejected..."
                                />
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Cancel</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                        variant="danger"
                                        wire:click="reject({{ $enrollment->id }}, $rejectionReason)"
                                    >
                                        Reject Request
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    @endforeach
                </div>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="enroll">
            @if($this->teamMembers->isEmpty())
                <flux:callout icon="user-group">
                    No team members found.
                </flux:callout>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column class="hidden sm:table-cell">Enrolled Courses</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->teamMembers as $member)
                            <flux:table.row wire:key="member-{{ $member->id }}">
                                <flux:table.cell class="font-medium">
                                    {{ $member->full_name }}
                                </flux:table.cell>
                                <flux:table.cell class="hidden sm:table-cell">
                                    {{ $member->trainingCourses()->count() }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:button
                                        size="sm"
                                        icon="plus"
                                        wire:click="openEnrollModal({{ $member->id }})"
                                    >
                                        Enroll
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:tab.panel>
    </flux:tab.group>

    {{-- Enroll Team Member Modal --}}
    <flux:modal name="enroll-modal" variant="flyout">
        @if($enrollingUser)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Enroll {{ $enrollingUser->full_name }}</flux:heading>
                    <flux:text class="mt-2">
                        Select training courses to enroll this team member on.
                    </flux:text>
                </div>

                @if($this->availableCoursesForEnrolling->isEmpty())
                    <flux:callout icon="information-circle">
                        No available courses. This team member is already enrolled on all active courses.
                    </flux:callout>
                @else
                    <flux:checkbox.group wire:model.live="coursesToEnroll" label="Select courses" variant="cards" class="flex-col">
                        @foreach($this->availableCoursesForEnrolling as $course)
                            <flux:checkbox
                                wire:key="enroll-course-{{ $course->id }}"
                                value="{{ $course->id }}"
                                label="{{ $course->name }}"
                                description="{{ $course->isFree() ? 'Free' : $course->cost }}"
                            />
                        @endforeach
                    </flux:checkbox.group>
                @endif

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeEnrollModal">Cancel</flux:button>
                    <flux:button
                        variant="primary"
                        wire:click="enrollTeamMember"
                        :disabled="empty($coursesToEnroll)"
                    >
                        Enroll
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
