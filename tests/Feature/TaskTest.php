<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Enums\TaskStatus;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Person;
use App\Models\Task;
use App\Models\TaskLink;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('tasks use the belongs to organization scope', function () {
    expect(class_uses_recursive(Task::class))->toContain(BelongsToOrganization::class)
        ->and(class_uses_recursive(TaskLink::class))->toContain(BelongsToOrganization::class);
});

test('queries without an active organization return no tasks', function () {
    Task::factory()->create();

    EnsureActiveOrganization::forget();

    expect(Task::query()->count())->toBe(0);
});

test('a task can be captured in three interaction steps from quick create', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::tasks.index')
        ->call('openQuickCreate')
        ->assertSet('showQuickCreate', true)
        ->set('title', 'Feed the horses')
        ->set('dueDate', '2026-09-01')
        ->call('quickCreate')
        ->assertHasNoErrors()
        ->assertSet('showQuickCreate', false);

    $task = Task::query()->where('title', 'Feed the horses')->first();

    expect($task)->not->toBeNull()
        ->and($task?->organization_id)->toBe($organization->id)
        ->and($task?->status)->toBe(TaskStatus::Open)
        ->and($task?->due_date?->toDateString())->toBe('2026-09-01');
});

test('the task list filters by status and assignee', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $assignee = Person::factory()->for($organization)->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    Task::factory()->for($organization)->create(['title' => 'Open unassigned']);
    Task::factory()->for($organization)->assignedTo($assignee)->create(['title' => 'Open assigned']);
    Task::factory()->for($organization)->done()->create(['title' => 'Already done']);

    $this->actingAs($admin);

    Livewire::test('pages::tasks.index')
        ->assertSee('Open unassigned')
        ->assertSee('Open assigned')
        ->assertDontSee('Already done');

    Livewire::test('pages::tasks.index')
        ->set('status', TaskStatus::Done->value)
        ->assertSee('Already done')
        ->assertDontSee('Open unassigned');

    Livewire::test('pages::tasks.index')
        ->set('assignee', $assignee->id)
        ->assertSee('Open assigned')
        ->assertDontSee('Open unassigned')
        ->assertDontSee('Already done');
});

test('admins can complete a task from the list', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $task = Task::factory()->for($organization)->create([
        'title' => 'Close the stable',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::tasks.index')
        ->call('complete', $task->id)
        ->assertHasNoErrors();

    expect($task->refresh()->isDone())->toBeTrue();
});

test('admins can update assign and complete a task on the edit page', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $person = Person::factory()->for($organization)->create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
    ]);
    $task = Task::factory()->for($organization)->create([
        'title' => 'Draft agenda',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::tasks.edit', ['task' => $task])
        ->set('title', 'Draft agenda')
        ->set('assigneeId', $person->id)
        ->set('linkedPersonIds', [$person->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($task->refresh()->assignee_id)->toBe($person->id)
        ->and($task->links)->toHaveCount(1);

    Livewire::test('pages::tasks.edit', ['task' => $task])
        ->set('assigneeId', $person->id)
        ->call('assign')
        ->assertHasNoErrors();

    Livewire::test('pages::tasks.edit', ['task' => $task])
        ->call('complete')
        ->assertHasNoErrors();

    expect($task->refresh()->isDone())->toBeTrue();
});

test('staff cannot open task pages', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();
    $organization = $staff->organizations()->firstOrFail();
    $task = Task::factory()->for($organization)->create();

    $this->actingAs($staff)
        ->get(route('tasks.index'))
        ->assertForbidden();

    setActiveOrganization($organization->id);

    expect($staff->can('create', Task::class))->toBeFalse()
        ->and($staff->can('update', $task))->toBeFalse()
        ->and($staff->can('complete', $task))->toBeFalse();
});

test('a foreign task id returns 404 without leaking data', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    Task::factory()->for($organizationA)->create();

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $taskB = Task::factory()->for($organizationB)->create([
        'title' => 'Secret tenant task',
    ]);

    $this->actingAs($userA)
        ->get(route('tasks.edit', $taskB))
        ->assertNotFound()
        ->assertDontSee('Secret tenant task');
});

test('eloquent cannot read another organizations task', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    $taskA = Task::factory()->for($organizationA)->create();

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $taskB = Task::factory()->for($organizationB)->create();

    setActiveOrganization($organizationA->id);

    expect(Task::query()->find($taskA->id))->not->toBeNull()
        ->and(Task::query()->find($taskB->id))->toBeNull()
        ->and(Task::query()->count())->toBe(1);
});

test('tasks index is displayed for admins', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get(route('tasks.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::tasks.index')
        ->assertSee(__('Tasks'));
});

test('guests are redirected from tasks to login', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
});
