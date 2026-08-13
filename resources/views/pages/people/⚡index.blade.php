<?php

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('People')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'active';

    public function mount(): void
    {
        $this->authorize('viewAny', Person::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    #[Computed]
    public function people(): LengthAwarePaginator
    {
        return Person::query()
            ->with('user')
            ->search($this->search)
            ->archivedStatus($this->status)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);
    }
}; ?>

<section class="flex w-full min-w-0 flex-col gap-6" data-test="people-index">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading>{{ __('People') }}</flux:heading>

        <flux:button
            :href="route('people.create')"
            variant="primary"
            icon="plus"
            wire:navigate
            class="min-h-11"
            data-test="create-person-button"
        >
            {{ __('New person') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:input
            wire:model.live.debounce.400ms="search"
            :label="__('Search')"
            :placeholder="__('Name, email, or phone')"
            icon="magnifying-glass"
            data-test="search-people"
        />

        <flux:select wire:model.live="status" :label="__('Status')" data-test="people-status-filter">
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
        </flux:select>
    </div>

    @forelse ($this->people as $person)
        <div wire:key="person-{{ $person->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" data-test="person-row">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <flux:heading size="sm">{{ $person->display_name }}</flux:heading>
                    <flux:text>
                        {{ $person->email ?? __('No email') }}
                        @if (filled($person->phone))
                            · {{ $person->phone }}
                        @endif
                    </flux:text>
                    @if ($person->isArchived())
                        <flux:badge color="zinc" size="sm" class="mt-2">{{ __('Archived') }}</flux:badge>
                    @endif
                </div>

                <flux:button
                    :href="route('people.edit', $person)"
                    variant="ghost"
                    wire:navigate
                    class="min-h-11"
                >
                    {{ __('Edit') }}
                </flux:button>
            </div>
        </div>
    @empty
        <x-ui.empty-state :heading="__('No people yet')" :text="__('Add a person to the shared directory. Member and customer profiles can attach later.')" />
    @endforelse

    @if ($this->people->hasPages())
        <flux:pagination :paginator="$this->people" />
    @endif
</section>
