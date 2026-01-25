<x-mail::message>
# Training Request Approved

Your request to enroll on **{{ $course->name }}** has been approved by {{ $approver->full_name }}.

You are now booked on this course.

<x-mail::button :url="route('home', ['tab' => 'training'])">
View My Training
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
