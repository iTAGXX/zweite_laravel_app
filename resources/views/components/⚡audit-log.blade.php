<?php

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    /**
     * @return Collection<int, AuditLog>
     */
    #[Computed]
    public function entries(): Collection
    {
        return AuditLog::query()
            ->with('actor')
            ->latest('created_at')
            ->limit(100)
            ->get();
    }
};
?>

<div class="flex w-full min-w-0 flex-col gap-6">
    <flux:heading>{{ __('Audit log') }}</flux:heading>

    @forelse ($this->entries as $entry)
        <div wire:key="audit-{{ $entry->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="sm">{{ $entry->action }}</flux:heading>
            <flux:text>
                {{ $entry->actor?->email ?? __('System') }}
                · {{ $entry->created_at?->toDateTimeString() }}
            </flux:text>
            @if ($entry->metadata !== [])
                <flux:text class="font-mono text-xs">{{ json_encode($entry->metadata, JSON_UNESCAPED_UNICODE) }}</flux:text>
            @endif
        </div>
    @empty
        <x-ui.empty-state :heading="__('Nothing to show yet.')" :text="__('Audit events will appear here.')" />
    @endforelse
</div>
