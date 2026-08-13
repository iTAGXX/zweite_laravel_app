<x-mail::message>
# {{ __('You are invited to :organization', ['organization' => $organizationName]) }}

{{ __('Accept this invitation to join the organization. The link expires on :date.', ['date' => $expiresAt->timezone(config('app.timezone'))->toFormattedDateString()]) }}

<x-mail::button :url="$acceptUrl">
{{ __('Accept invitation') }}
</x-mail::button>

{{ __('If you did not expect this invitation, you can ignore this email.') }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
