@props([
    'heading',
    'text' => null,
])

<div data-test="error-state">
    <flux:callout variant="danger" icon="x-circle">
        <flux:callout.heading>{{ $heading }}</flux:callout.heading>

        @if (filled($text))
            <flux:callout.text>{{ $text }}</flux:callout.text>
        @endif

        {{ $slot }}
    </flux:callout>
</div>
