<div>
    <flux:heading size="xl" level="1">Pending Training Requests</flux:heading>

    <flux:text class="mt-2 mb-6">
        Review and approve training course enrollments for your team members.
    </flux:text>

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
</div>
