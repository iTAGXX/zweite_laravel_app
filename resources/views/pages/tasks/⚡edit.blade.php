<?php

use App\Actions\AssignTask;
use App\Actions\CompleteTask;
use App\Actions\DeleteTask;
use App\Actions\UpdateTask;
use App\Concerns\TaskValidationRules;
use App\Enums\TaskLinkableType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit task')] class extends Component
{
    use TaskValidationRules;

    public Task $task;

    public string $title = '';

    public ?string $description = null;

    public ?string $assigneeId = null;

    public ?string $dueDate = null;

    public string $priority = '';

    public string $status = '';

    /**
     * @var list<string>
     */
    public array $linkedPersonIds = [];

    public function mount(Task $task): void
    {
        $this->authorize('update', $task);

        $this->task = $task->load('links');
        $this->title = $task->title;
        $this->description = $task->description;
        $this->assigneeId = $task->assignee_id;
        $this->dueDate = $task->due_date?->toDateString();
        $this->priority = $task->priority->value;
        $this->status = $task->status->value;
        $this->linkedPersonIds = $task->links
            ->where('linkable_type', TaskLinkableType::Person)
            ->pluck('linkable_id')
            ->values()
            ->all();
    }

    public function save(UpdateTask $updateTask): void
    {
        $this->authorize('update', $this->task);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->description = $this->description === '' ? null : $this->description;
        $this->assigneeId = $this->assigneeId === '' ? null : $this->assigneeId;
        $this->dueDate = $this->dueDate === '' ? null : $this->dueDate;

        $validated = $this->validate($this->taskRules());

        $updateTask->handle($user, $this->task, $this->taskPayload($validated));

        Flux::toast(variant: 'success', text: __('Task updated.'));

        $this->redirect(route('tasks.index'), navigate: true);
    }

    public function complete(CompleteTask $completeTask): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $completeTask->handle($user, $this->task);

        Flux::toast(variant: 'success', text: __('Task completed.'));

        $this->redirect(route('tasks.index'), navigate: true);
    }

    public function assign(AssignTask $assignTask): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->assigneeId = $this->assigneeId === '' ? null : $this->assigneeId;

        $assignee = $this->assigneeId !== null
            ? Person::query()->findOrFail($this->assigneeId)
            : null;

        $assignTask->handle($user, $this->task, $assignee);

        $this->task->refresh();

        Flux::toast(variant: 'success', text: __('Task assigned.'));
    }

    public function delete(DeleteTask $deleteTask): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $deleteTask->handle($user, $this->task);

        Flux::toast(variant: 'success', text: __('Task deleted.'));

        $this->redirect(route('tasks.index'), navigate: true);
    }

    /**
     * @return Collection<int, Person>
     */
    #[Computed]
    public function people(): Collection
    {
        return Person::query()
            ->notArchived()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}; ?>

<section class="flex w-full min-w-0 flex-col gap-6" data-test="task-edit">
    <div class="flex flex-col gap-2">
        <flux:heading>{{ __('Edit task') }}</flux:heading>
        <flux:subheading>{{ $this->task->title }}</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-lg space-y-6" data-test="task-form">
        <x-ui.form-errors />

        <flux:input wire:model="title" :label="__('Title')" required />
        <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

        <flux:select wire:model="assigneeId" :label="__('Assignee')" data-test="task-assignee">
            <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
            @foreach ($this->people as $person)
                <flux:select.option wire:key="assignee-{{ $person->id }}" :value="$person->id">
                    {{ $person->display_name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="dueDate" type="date" :label="__('Due date')" />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:select wire:model="priority" :label="__('Priority')">
                @foreach (TaskPriority::cases() as $priority)
                    <flux:select.option wire:key="priority-{{ $priority->value }}" :value="$priority->value">
                        {{ $priority->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="status" :label="__('Status')">
                @foreach (TaskStatus::cases() as $status)
                    <flux:select.option wire:key="status-{{ $status->value }}" :value="$status->value">
                        {{ $status->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div class="space-y-3">
            <flux:heading size="sm">{{ __('Related people') }}</flux:heading>
            <flux:text>{{ __('Link existing people. Other domain objects can be added later without fake models.') }}</flux:text>
            @forelse ($this->people as $person)
                <flux:checkbox
                    wire:key="link-{{ $person->id }}"
                    wire:model="linkedPersonIds"
                    :value="$person->id"
                    :label="$person->display_name"
                />
            @empty
                <flux:text>{{ __('No people yet') }}</flux:text>
            @endforelse
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <flux:button variant="primary" type="submit" class="min-h-11" data-test="save-task-button">
                {{ __('Save') }}
            </flux:button>
            <flux:button type="button" wire:click="assign" variant="filled" class="min-h-11" data-test="assign-task-button">
                {{ __('Assign') }}
            </flux:button>
            <flux:button :href="route('tasks.index')" variant="ghost" wire:navigate class="min-h-11">
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>

    <div class="max-w-lg space-y-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
        @unless ($this->task->isDone())
            <flux:button wire:click="complete" variant="primary" class="min-h-11" data-test="complete-task-button">
                {{ __('Complete task') }}
            </flux:button>
        @endunless
        <flux:button
            wire:click="delete"
            wire:confirm="{{ __('Delete this task? This cannot be undone.') }}"
            variant="danger"
            class="min-h-11"
            data-test="delete-task-button"
        >
            {{ __('Delete task') }}
        </flux:button>
    </div>
</section>
