<x-mail::message>
# Training Approval Request

{{ $requester->full_name }} has requested to enroll on:

**{{ $course->name }}**

@if($course->cost)
Cost: {{ $course->cost }}
@endif

@if($course->description)
{{ $course->description }}
@endif

<x-mail::button :url="route('manager.training-requests')">
Review Pending Requests
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
