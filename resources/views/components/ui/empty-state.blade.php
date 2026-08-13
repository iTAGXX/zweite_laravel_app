@props([
    'heading',
    'text' => null,
])

<div {{ $attributes->class('flex flex-col items-center gap-2 py-12 text-center') }} data-test="empty-state">
    <flux:heading>{{ $heading }}</flux:heading>

    @if (filled($text))
        <flux:text>{{ $text }}</flux:text>
    @endif

    {{ $slot }}
</div>
