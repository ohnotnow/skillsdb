<x-mail::message>
# Pending Skills Awaiting Approval

The following {{ $skills->count() === 1 ? 'skill has' : 'skills have' }} been suggested and {{ $skills->count() === 1 ? 'is' : 'are' }} awaiting your approval:

@foreach($skills as $skill)
- **{{ $skill->name }}** - suggested by {{ $skill->users->first()?->short_name ?? 'Unknown' }}
@endforeach

<x-mail::button :url="route('admin.skills')">
Review Skills
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
