<?php

use App\Actions\CreateTaskRecurrence;
use App\Actions\EndTaskRecurrence;
use App\Actions\PauseTaskRecurrence;
use App\Actions\ResumeTaskRecurrence;
use App\Concerns\TaskRecurrenceValidationRules;
use App\Enums\RecurrenceFrequency;
use App\Enums\TaskPriority;
use App\Models\Person;
use App\Models\TaskRecurrence;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recurring tasks')] class extends Component
{
    use TaskRecurrenceValidationRules;

    public bool $showCreate = false;

    public string $title = '';

    public ?string $description = null;

    public ?string $assigneeId = null;

    public string $priority = '';

    public string $frequency = '';

    public int $dayOfMonth = 1;

    public ?int $month = null;

    public ?string $startsOn = null;

    public ?string $endsOn = null;

    public function mount(): void
    {
        $this->authorize('viewAny', TaskRecurrence::class);

        $this->priority = TaskPriority::Medium->value;
        $this->frequency = RecurrenceFrequency::Monthly->value;
        $this->dayOfMonth = (int) now()->day;
        $this->startsOn = now()->toDateString();
    }

    public function openCreate(): void
    {
        $this->authorize('create', TaskRecurrence::class);

        $this->resetValidation();
        $this->title = '';
        $this->description = null;
        $this->assigneeId = null;
        $this->priority = TaskPriority::Medium->value;
        $this->frequency = RecurrenceFrequency::Monthly->value;
        $this->dayOfMonth = (int) now()->day;
        $this->month = null;
        $this->startsOn = now()->toDateString();
        $this->endsOn = null;
        $this->showCreate = true;
    }

    public function save(CreateTaskRecurrence $createTaskRecurrence): void
    {
        $this->authorize('create', TaskRecurrence::class);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->description = $this->description === '' ? null : $this->description;
        $this->assigneeId = $this->assigneeId === '' ? null : $this->assigneeId;
        $this->endsOn = $this->endsOn === '' ? null : $this->endsOn;

        $validated = $this->validate($this->recurrenceRules());

        $createTaskRecurrence->handle($user, $this->recurrencePayload($validated));

        $this->showCreate = false;

        Flux::toast(variant: 'success', text: __('Recurring task created.'));
    }

    public function pause(string $recurrenceId, PauseTaskRecurrence $pauseTaskRecurrence): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $recurrence = TaskRecurrence::query()->findOrFail($recurrenceId);

        $pauseTaskRecurrence->handle($user, $recurrence);

        Flux::toast(variant: 'success', text: __('Recurring task paused.'));
    }

    public function resume(string $recurrenceId, ResumeTaskRecurrence $resumeTaskRecurrence): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $recurrence = TaskRecurrence::query()->findOrFail($recurrenceId);

        $resumeTaskRecurrence->handle($user, $recurrence);

        Flux::toast(variant: 'success', text: __('Recurring task resumed.'));
    }

    public function end(string $recurrenceId, EndTaskRecurrence $endTaskRecurrence): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $recurrence = TaskRecurrence::query()->findOrFail($recurrenceId);

        $endTaskRecurrence->handle($user, $recurrence);

        Flux::toast(variant: 'success', text: __('Recurring task ended.'));
    }

    /**
     * @return Collection<int, TaskRecurrence>
     */
    #[Computed]
    public function recurrences(): Collection
    {
        return TaskRecurrence::query()
            ->with('assignee')
            ->latest()
            ->get();
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

<section class="flex w-full min-w-0 flex-col gap-6" data-test="task-recurrences">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-col gap-2">
            <flux:heading>{{ __('Recurring tasks') }}</flux:heading>
            <flux:subheading>{{ __('Monthly and yearly club and stable duties. The scheduler creates the next occurrence once.') }}</flux:subheading>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <flux:button :href="route('tasks.index')" variant="ghost" wire:navigate class="min-h-11">
                {{ __('Tasks') }}
            </flux:button>
            <flux:button
                variant="primary"
                icon="plus"
                wire:click="openCreate"
                class="min-h-11"
                data-test="create-recurrence-button"
            >
                {{ __('New recurring task') }}
            </flux:button>
        </div>
    </div>

    @forelse ($this->recurrences as $recurrence)
        <div wire:key="recurrence-{{ $recurrence->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" data-test="recurrence-row">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <flux:heading size="sm">{{ $recurrence->title }}</flux:heading>
                    <flux:text>
                        {{ $recurrence->frequency->label() }}
                        · {{ __('Day') }} {{ $recurrence->day_of_month }}
                        @if ($recurrence->frequency === RecurrenceFrequency::Yearly && $recurrence->month !== null)
                            · {{ __('Month') }} {{ $recurrence->month }}
                        @endif
                        · {{ __('Next') }} {{ $recurrence->next_occurrence_on->toFormattedDateString() }}
                        @if ($recurrence->assignee)
                            · {{ $recurrence->assignee->display_name }}
                        @endif
                        @if ($recurrence->isPaused())
                            · {{ __('Paused') }}
                        @endif
                        @if ($recurrence->isEnded())
                            · {{ __('Ended') }}
                        @endif
                    </flux:text>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    @if ($recurrence->isPaused())
                        <flux:button
                            wire:click="resume('{{ $recurrence->id }}')"
                            variant="filled"
                            class="min-h-11"
                            data-test="resume-recurrence-button"
                        >
                            {{ __('Resume') }}
                        </flux:button>
                    @elseif (! $recurrence->isEnded())
                        <flux:button
                            wire:click="pause('{{ $recurrence->id }}')"
                            variant="filled"
                            class="min-h-11"
                            data-test="pause-recurrence-button"
                        >
                            {{ __('Pause') }}
                        </flux:button>
                    @endif
                    @unless ($recurrence->isEnded())
                        <flux:button
                            wire:click="end('{{ $recurrence->id }}')"
                            wire:confirm="{{ __('End this recurring task? No further occurrences will be created.') }}"
                            variant="danger"
                            class="min-h-11"
                            data-test="end-recurrence-button"
                        >
                            {{ __('End') }}
                        </flux:button>
                    @endunless
                </div>
            </div>
        </div>
    @empty
        <x-ui.empty-state :heading="__('No recurring tasks yet')" :text="__('Create a monthly or yearly rule. The job generates each occurrence once.')" />
    @endforelse

    <flux:modal wire:model.self="showCreate" class="min-w-0 md:w-96" data-test="create-recurrence-modal">
        <form wire:submit="save" class="space-y-6" data-test="create-recurrence-form">
            <div>
                <flux:heading size="lg">{{ __('New recurring task') }}</flux:heading>
                <flux:subheading>{{ __('Only monthly and yearly rules. Full iCal RRULE is out of scope.') }}</flux:subheading>
            </div>

            <x-ui.form-errors />

            <flux:input wire:model="title" :label="__('Title')" required data-test="recurrence-title" />
            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

            <flux:select wire:model="assigneeId" :label="__('Assignee')">
                <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                @foreach ($this->people as $person)
                    <flux:select.option wire:key="recurrence-assignee-{{ $person->id }}" :value="$person->id">
                        {{ $person->display_name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="priority" :label="__('Priority')">
                @foreach (TaskPriority::cases() as $priority)
                    <flux:select.option wire:key="recurrence-priority-{{ $priority->value }}" :value="$priority->value">
                        {{ $priority->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="frequency" :label="__('Frequency')" data-test="recurrence-frequency">
                @foreach (RecurrenceFrequency::cases() as $frequency)
                    <flux:select.option wire:key="recurrence-frequency-{{ $frequency->value }}" :value="$frequency->value">
                        {{ $frequency->label() }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="dayOfMonth" type="number" min="1" max="31" :label="__('Day of month')" required />

            @if ($frequency === RecurrenceFrequency::Yearly->value)
                <flux:select wire:model="month" :label="__('Month')" data-test="recurrence-month">
                    <flux:select.option value="">{{ __('Select month') }}</flux:select.option>
                    @foreach (range(1, 12) as $monthNumber)
                        <flux:select.option wire:key="recurrence-month-{{ $monthNumber }}" :value="$monthNumber">
                            {{ $monthNumber }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="startsOn" type="date" :label="__('Starts on')" required data-test="recurrence-starts-on" />
            <flux:input wire:model="endsOn" type="date" :label="__('Ends on')" />

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreate', false)" class="min-h-11">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit" class="min-h-11" data-test="save-recurrence-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
