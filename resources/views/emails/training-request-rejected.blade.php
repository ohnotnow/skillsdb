<x-mail::message>
# Training Request Declined

Your request to enroll on **{{ $course->name }}** has been declined by {{ $rejector->full_name }}.

@if($reason)
**Reason:** {{ $reason }}
@endif

If you have questions about this decision, please speak with your manager directly.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
