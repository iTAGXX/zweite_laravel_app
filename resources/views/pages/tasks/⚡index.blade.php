<?php

use App\Actions\CompleteTask;
use App\Actions\CreateTask;
use App\Concerns\TaskValidationRules;
use App\Enums\TaskStatus;
use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Tasks')] class extends Component
{
    use TaskValidationRules, WithPagination;

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $assignee = 'all';

    public bool $showQuickCreate = false;

    public string $title = '';

    public ?string $dueDate = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Task::class);
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingAssignee(): void
    {
        $this->resetPage();
    }

    public function openQuickCreate(): void
    {
        $this->authorize('create', Task::class);

        $this->resetValidation();
        $this->title = '';
        $this->dueDate = null;
        $this->showQuickCreate = true;
    }

    public function quickCreate(CreateTask $createTask): void
    {
        $this->authorize('create', Task::class);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->dueDate = $this->dueDate === '' ? null : $this->dueDate;

        $validated = $this->validate($this->quickCreateRules());

        $createTask->handle($user, $this->quickCreatePayload($validated));

        $this->showQuickCreate = false;
        $this->title = '';
        $this->dueDate = null;

        Flux::toast(variant: 'success', text: __('Task created.'));
    }

    public function complete(string $taskId, CompleteTask $completeTask): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $task = Task::query()->findOrFail($taskId);

        $completeTask->handle($user, $task);
        unset($this->tasks);

        Flux::toast(variant: 'success', text: __('Task completed.'));
    }

    /**
     * @return LengthAwarePaginator<int, Task>
     */
    #[Computed]
    public function tasks(): LengthAwarePaginator
    {
        return Task::query()
            ->with('assignee')
            ->statusFilter($this->status)
            ->assigneeFilter($this->assignee)
            ->latest()
            ->paginate(15);
    }

    /**
     * @return Collection<int, Person>
     */
    #[Computed]
    public function assignees(): Collection
    {
        return Person::query()
            ->notArchived()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}; ?>

<section class="flex w-full min-w-0 flex-col gap-6" data-test="tasks-index">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <flux:heading>{{ __('Tasks') }}</flux:heading>

        <flux:button
            variant="primary"
            icon="plus"
            wire:click="openQuickCreate"
            class="min-h-11"
            data-test="quick-create-task-button"
        >
            {{ __('New task') }}
        </flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <flux:select wire:model.live="status" :label="__('Status')" data-test="task-status-filter">
            <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
            <flux:select.option value="{{ TaskStatus::Open->value }}">{{ TaskStatus::Open->label() }}</flux:select.option>
            <flux:select.option value="{{ TaskStatus::InProgress->value }}">{{ TaskStatus::InProgress->label() }}</flux:select.option>
            <flux:select.option value="{{ TaskStatus::Done->value }}">{{ TaskStatus::Done->label() }}</flux:select.option>
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="assignee" :label="__('Assignee')" data-test="task-assignee-filter">
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
            <flux:select.option value="unassigned">{{ __('Unassigned') }}</flux:select.option>
            @foreach ($this->assignees as $person)
                <flux:select.option wire:key="assignee-filter-{{ $person->id }}" :value="$person->id">
                    {{ $person->display_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    @forelse ($this->tasks as $task)
        <div wire:key="task-{{ $task->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" data-test="task-row">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <flux:heading size="sm">{{ $task->title }}</flux:heading>
                    <flux:text>
                        {{ $task->status->label() }}
                        · {{ $task->priority->label() }}
                        @if ($task->assignee)
                            · {{ $task->assignee->display_name }}
                        @else
                            · {{ __('Unassigned') }}
                        @endif
                        @if ($task->due_date)
                            · {{ $task->due_date->toFormattedDateString() }}
                        @endif
                    </flux:text>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    @unless ($task->isDone())
                        <flux:button
                            wire:click="complete('{{ $task->id }}')"
                            variant="filled"
                            class="min-h-11"
                            data-test="complete-task-button"
                        >
                            {{ __('Complete') }}
                        </flux:button>
                    @endunless
                    <flux:button
                        :href="route('tasks.edit', $task)"
                        variant="ghost"
                        wire:navigate
                        class="min-h-11"
                    >
                        {{ __('Edit') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @empty
        <x-ui.empty-state :heading="__('No tasks yet')" :text="__('Create a task in three steps: open, title, save.')" />
    @endforelse

    @if ($this->tasks->hasPages())
        <flux:pagination :paginator="$this->tasks" />
    @endif

    <flux:modal wire:model.self="showQuickCreate" class="min-w-0 md:w-96" data-test="quick-create-task-modal">
        <form wire:submit="quickCreate" class="space-y-6" data-test="quick-create-task-form">
            <div>
                <flux:heading size="lg">{{ __('New task') }}</flux:heading>
                <flux:subheading>{{ __('Title is enough. Assignee and links can be added afterwards.') }}</flux:subheading>
            </div>

            <x-ui.form-errors />

            <flux:input wire:model="title" :label="__('Title')" required data-test="quick-create-task-title" />
            <flux:input wire:model="dueDate" type="date" :label="__('Due date')" data-test="quick-create-task-due-date" />

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" wire:click="$set('showQuickCreate', false)" class="min-h-11">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit" class="min-h-11" data-test="quick-create-task-save">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
