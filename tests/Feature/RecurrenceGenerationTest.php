<?php

declare(strict_types=1);

use App\Actions\CreateTaskRecurrence;
use App\Actions\EndTaskRecurrence;
use App\Actions\GenerateRecurringTasks;
use App\Actions\PauseTaskRecurrence;
use App\Actions\RecurrenceCalculator;
use App\Actions\ResumeTaskRecurrence;
use App\Enums\RecurrenceFrequency;
use App\Enums\RoleName;
use App\Enums\TaskPriority;
use App\Jobs\GenerateDueRecurringTasks;
use App\Models\Organization;
use App\Models\Task;
use App\Models\TaskRecurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('recurring task generation is scheduled daily without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->firstWhere('description', 'recurrences:generate');

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe('0 0 * * *')
        ->and($event?->withoutOverlapping)->toBeTrue();
});

test('a monthly recurrence creates a task once per occurrence even if the job runs twice', function () {
    $organization = Organization::factory()->create();
    $recurrence = TaskRecurrence::factory()->for($organization)->monthly(13)->create([
        'title' => 'Board pack',
        'next_occurrence_on' => '2026-08-13',
    ]);

    $on = CarbonImmutable::parse('2026-08-13');
    $generate = app(GenerateRecurringTasks::class);

    $generate->handle($on);
    $generate->handle($on);

    setActiveOrganization($organization->id);

    $tasks = Task::query()->get();

    expect($tasks)->toHaveCount(1)
        ->and($tasks->first()?->title)->toBe('Board pack')
        ->and($tasks->first()?->occurrence_key)->toBe($recurrence->id.':2026-08-13')
        ->and($tasks->first()?->recurrence_id)->toBe($recurrence->id)
        ->and($tasks->first()?->due_date?->toDateString())->toBe('2026-08-13');

    expect($recurrence->refresh()->next_occurrence_on->toDateString())->toBe('2026-09-13');
});

test('a yearly recurrence creates the next years occurrence', function () {
    $organization = Organization::factory()->create();
    $recurrence = TaskRecurrence::factory()->for($organization)->yearly(3, 1)->create([
        'title' => 'AGM invitation',
        'next_occurrence_on' => '2026-03-01',
    ]);

    $generate = app(GenerateRecurringTasks::class);
    $generate->handle(CarbonImmutable::parse('2026-03-01'));
    $generate->handle(CarbonImmutable::parse('2026-03-01'));
    $generate->handle(CarbonImmutable::parse('2027-03-01'));

    setActiveOrganization($organization->id);

    $tasks = Task::query()->orderBy('due_date')->get();

    expect($tasks)->toHaveCount(2)
        ->and($tasks->pluck('occurrence_key')->all())->toBe([
            $recurrence->id.':2026-03-01',
            $recurrence->id.':2027-03-01',
        ])
        ->and($recurrence->refresh()->next_occurrence_on->toDateString())->toBe('2028-03-01');
});

test('paused recurrences do not create tasks until resumed', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $recurrence = TaskRecurrence::factory()->for($organization)->monthly(13)->create([
        'next_occurrence_on' => '2026-08-13',
    ]);

    (new PauseTaskRecurrence)->handle($admin, $recurrence);

    app(GenerateRecurringTasks::class)->handle(CarbonImmutable::parse('2026-08-13'));

    expect(Task::query()->count())->toBe(0);

    (new ResumeTaskRecurrence)->handle($admin, $recurrence->refresh());

    app(GenerateRecurringTasks::class)->handle(CarbonImmutable::parse('2026-08-13'));

    expect(Task::query()->count())->toBe(1);
});

test('ended recurrences do not create further tasks', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $recurrence = TaskRecurrence::factory()->for($organization)->monthly(13)->create([
        'next_occurrence_on' => '2026-08-13',
    ]);

    (new EndTaskRecurrence)->handle($admin, $recurrence);

    app(GenerateRecurringTasks::class)->handle(CarbonImmutable::parse('2026-08-13'));

    expect(Task::query()->count())->toBe(0)
        ->and($recurrence->refresh()->isEnded())->toBeTrue();
});

test('generation errors are logged and other recurrences still run', function () {
    $organization = Organization::factory()->create();
    TaskRecurrence::factory()->for($organization)->monthly(13)->create([
        'title' => 'Broken rule',
        'next_occurrence_on' => '2026-08-13',
    ]);
    $healthy = TaskRecurrence::factory()->for($organization)->monthly(15)->create([
        'title' => 'Healthy rule',
        'next_occurrence_on' => '2026-08-15',
    ]);

    $calculator = Mockery::mock(RecurrenceCalculator::class);
    $calculator->shouldReceive('nextOccurrence')
        ->andReturnUsing(function (RecurrenceFrequency $frequency, mixed $from, int $dayOfMonth, ?int $month): CarbonImmutable {
            if ($dayOfMonth === 13) {
                throw new RuntimeException('calendar exploded');
            }

            return CarbonImmutable::parse('2026-09-15');
        });

    Log::spy();

    (new GenerateRecurringTasks($calculator))->handle(CarbonImmutable::parse('2026-08-15'));

    Log::shouldHaveReceived('error')->once();

    setActiveOrganization($organization->id);

    expect(Task::query()->where('title', 'Healthy rule')->count())->toBe(1)
        ->and($healthy->refresh()->next_occurrence_on->toDateString())->toBe('2026-09-15');
});

test('the queued job logs failures', function () {
    Log::spy();

    (new GenerateDueRecurringTasks)->failed(new RuntimeException('queue down'));

    Log::shouldHaveReceived('error')->once();
});

test('the job generates tasks for every organization', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    TaskRecurrence::factory()->for($organizationA)->monthly(13)->create([
        'title' => 'Org A duty',
        'next_occurrence_on' => '2026-08-13',
    ]);
    TaskRecurrence::factory()->for($organizationB)->monthly(13)->create([
        'title' => 'Org B duty',
        'next_occurrence_on' => '2026-08-13',
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-08-13 12:00:00'));

    (new GenerateDueRecurringTasks)->handle(app(GenerateRecurringTasks::class));

    setActiveOrganization($organizationA->id);
    expect(Task::query()->where('title', 'Org A duty')->count())->toBe(1)
        ->and(Task::query()->where('title', 'Org B duty')->count())->toBe(0);

    setActiveOrganization($organizationB->id);
    expect(Task::query()->where('title', 'Org B duty')->count())->toBe(1)
        ->and(Task::query()->where('title', 'Org A duty')->count())->toBe(0);
});

test('admins can create pause and end recurrences from the page', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $this->actingAs($admin);

    Livewire::test('pages::tasks.recurrences')
        ->call('openCreate')
        ->set('title', 'Stable inspection')
        ->set('frequency', RecurrenceFrequency::Monthly->value)
        ->set('dayOfMonth', 13)
        ->set('startsOn', '2026-08-13')
        ->set('priority', TaskPriority::Medium->value)
        ->call('save')
        ->assertHasNoErrors();

    $recurrence = TaskRecurrence::query()->where('title', 'Stable inspection')->first();

    expect($recurrence)->not->toBeNull()
        ->and($recurrence?->next_occurrence_on->toDateString())->toBe('2026-08-13');

    Livewire::test('pages::tasks.recurrences')
        ->call('pause', $recurrence->id)
        ->assertHasNoErrors();

    expect($recurrence->refresh()->isPaused())->toBeTrue();

    Livewire::test('pages::tasks.recurrences')
        ->call('resume', $recurrence->id)
        ->call('end', $recurrence->id)
        ->assertHasNoErrors();

    expect($recurrence->refresh()->isEnded())->toBeTrue();
});

test('staff cannot create recurrences', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();
    $organization = $staff->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $this->actingAs($staff)
        ->get(route('tasks.recurrences'))
        ->assertForbidden();

    (new CreateTaskRecurrence(app(RecurrenceCalculator::class)))->handle($staff, [
        'title' => 'Forbidden',
        'description' => null,
        'assignee_id' => null,
        'priority' => TaskPriority::Medium->value,
        'frequency' => RecurrenceFrequency::Monthly->value,
        'day_of_month' => 1,
        'month' => null,
        'starts_on' => '2026-08-13',
        'ends_on' => null,
    ]);
})->throws(AuthorizationException::class);

test('a foreign recurrence id cannot be paused', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    setActiveOrganization($organizationA->id);

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $recurrenceB = TaskRecurrence::factory()->for($organizationB)->create([
        'title' => 'Secret recurrence',
    ]);

    $this->actingAs($userA)
        ->get(route('tasks.recurrences'))
        ->assertOk()
        ->assertDontSee('Secret recurrence');

    expect(TaskRecurrence::query()->find($recurrenceB->id))->toBeNull()
        ->and(TaskRecurrence::query()->count())->toBe(0);

    Livewire::test('pages::tasks.recurrences')
        ->call('pause', $recurrenceB->id);
})->throws(ModelNotFoundException::class);
