<div {{ $attributes->class('flex flex-col gap-3') }} data-test="loading-state">
    <flux:skeleton.group animate="shimmer">
        <flux:skeleton.line class="w-1/3" />
        <flux:skeleton.line class="w-2/3" />
        <flux:skeleton class="mt-2 h-24 w-full rounded-xl" />
    </flux:skeleton.group>
</div>
