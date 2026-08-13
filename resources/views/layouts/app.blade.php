<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="min-w-0 overflow-x-hidden max-lg:pb-20">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
