<div>
    <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center mb-6">
        <div class="flex-1 w-full">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search courses by name, description, or supplier..."
                icon="magnifying-glass"
            />
        </div>
        <flux:field variant="inline">
            <flux:label>Show my enrollments only</flux:label>
            <flux:switch wire:model.live="showMyEnrollments" />
        </flux:field>
    </div>

    @if ($this->courses->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($this->courses as $course)
                <flux:card wire:key="course-{{ $course->id }}">
                    <div class="flex flex-col h-full">
                        <div class="mb-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    @if ($course->good_count > 0 || $course->indifferent_count > 0 || $course->bad_count > 0)
                                        <flux:dropdown>
                                            <button type="button" class="cursor-pointer text-base" title="View ratings">
                                                @if ($course->good_count - $course->bad_count > 0)
                                                    👍
                                                @elseif ($course->good_count - $course->bad_count < 0)
                                                    👎
                                                @else
                                                    😐
                                                @endif
                                            </button>
                                            <flux:menu>
                                                <flux:menu.item icon="hand-thumb-up">Good: {{ $course->good_count }}</flux:menu.item>
                                                <flux:menu.item icon="minus">Indifferent: {{ $course->indifferent_count }}</flux:menu.item>
                                                <flux:menu.item icon="hand-thumb-down">Bad: {{ $course->bad_count }}</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                    <flux:heading size="sm">{{ $course->name }}</flux:heading>
                                </div>
                                @if ($course->users->isEmpty())
                                    <flux:button wire:click="enroll({{ $course->id }})" size="sm" icon="plus" square title="Book this course" />
                                @endif
                            </div>
                            @if ($course->supplier)
                                <flux:text size="sm" class="text-zinc-500">{{ $course->supplier->name }}</flux:text>
                            @endif
                        </div>

                        @if ($course->description)
                            <flux:text size="sm" class="mb-3 flex-1">{{ $course->description }}</flux:text>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        @if ($course->skills->isNotEmpty())
                            <div class="mb-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($course->skills->take(3) as $skill)
                                        <flux:badge size="sm" color="zinc">{{ $skill->name }}</flux:badge>
                                    @endforeach
                                    @if ($course->skills->count() > 3)
                                        <flux:badge size="sm" color="zinc">+{{ $course->skills->count() - 3 }}</flux:badge>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-3 mt-auto">
                            @if ($course->users->isEmpty())
                                <div class="flex flex-wrap gap-1">
                                    @if ($course->isFree())
                                        <flux:badge size="sm" color="green">Free</flux:badge>
                                    @endif
                                    @if ($course->offers_certification)
                                        <flux:badge size="sm" color="blue">Certified</flux:badge>
                                    @endif
                                </div>
                            @elseif ($course->users->first()->pivot->status === \App\Enums\EnrollmentStatus::Booked)
                                <div class="flex items-center gap-2">
                                    <flux:button wire:click="markCompleted({{ $course->id }})" size="sm" variant="primary" color="sky" class="flex-1">
                                        Mark Complete
                                    </flux:button>
                                    <flux:button wire:click="unenroll({{ $course->id }})" size="sm" variant="ghost">
                                        Cancel
                                    </flux:button>
                                </div>
                            @elseif ($course->users->first()->pivot->status === \App\Enums\EnrollmentStatus::Completed)
                                <div class="space-y-2">
                                    <flux:text size="sm" class="text-green-600 dark:text-green-400">Completed</flux:text>
                                    <div class="flex gap-1">
                                        @foreach (\App\Enums\TrainingRating::cases() as $ratingOption)
                                            <flux:button
                                                wire:click="setRating({{ $course->id }}, {{ $ratingOption->value }})"
                                                size="sm"
                                                :variant="$course->users->first()->pivot->rating === $ratingOption ? 'filled' : 'ghost'"
                                                :color="$ratingOption->colour()"
                                            >
                                                {{ $ratingOption->label() }}
                                            </flux:button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <flux:icon name="academic-cap" class="w-12 h-12 mx-auto text-zinc-400 mb-4" />
            <flux:heading size="lg" class="mb-2">No courses found</flux:heading>
            <flux:text>
                @if ($search || $showMyEnrollments)
                    No courses match your current filters. Try adjusting your search or clearing filters.
                @else
                    No training courses are available at the moment.
                @endif
            </flux:text>
        </div>
    @endif
</div>
