<x-mail::message>
# Training Enrollment

{{ $enrolledBy->full_name }} has enrolled you on the following training:

@foreach($courses as $course)
- **{{ $course->name }}**
@endforeach

<x-mail::button :url="route('home', ['tab' => 'training'])">
View My Training
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
