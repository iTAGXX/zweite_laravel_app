@php
    $organizationName = once(function (): ?string {
        $organizationId = session(\App\Http\Middleware\EnsureActiveOrganization::SESSION_KEY);

        if (! is_string($organizationId)) {
            return null;
        }

        return \App\Models\Organization::query()->find($organizationId)?->name;
    });
@endphp

<div {{ $attributes->class('min-w-0 max-w-full') }} data-test="organization-switcher">
    <flux:tooltip :content="__('Organization switching arrives in a later ticket.')">
        <div>
            <flux:button
                disabled
                icon="building-office-2"
                icon:trailing="chevron-down"
                class="min-h-11 w-full max-w-full justify-start overflow-hidden"
            >
                <span class="truncate">{{ $organizationName ?? __('Organization') }}</span>
            </flux:button>
        </div>
    </flux:tooltip>
</div>
