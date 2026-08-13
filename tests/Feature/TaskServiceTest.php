<?php

declare(strict_types=1);

use App\Actions\AssignTask;
use App\Actions\CompleteTask;
use App\Actions\CreateTask;
use App\Actions\DeleteTask;
use App\Actions\UpdateTask;
use App\Enums\RoleName;
use App\Enums\TaskLinkableType;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('create task stores a tenant scoped open task', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $task = (new CreateTask)->handle($admin, [
        'title' => 'Prepare board pack',
        'description' => 'Agenda and minutes',
        'assignee_id' => null,
        'due_date' => '2026-09-01',
        'priority' => TaskPriority::High->value,
        'status' => TaskStatus::Open->value,
        'linked_person_ids' => [],
    ]);

    expect($task->organization_id)->toBe($organization->id)
        ->and($task->title)->toBe('Prepare board pack')
        ->and($task->status)->toBe(TaskStatus::Open)
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($task->due_date?->toDateString())->toBe('2026-09-01')
        ->and($task->completed_at)->toBeNull();
});

test('a task can be assigned to a person in the same organization', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $person = Person::factory()->for($organization)->create();
    $task = Task::factory()->for($organization)->create();

    $task = (new AssignTask)->handle($admin, $task, $person);

    expect($task->assignee_id)->toBe($person->id)
        ->and($task->assignee?->is($person))->toBeTrue();
});

test('assigning a person from another organization is rejected', function () {
    $adminA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $adminA->organizations()->firstOrFail();
    setActiveOrganization($organizationA->id);
    $task = Task::factory()->for($organizationA)->create();

    $adminB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $adminB->organizations()->firstOrFail();
    setActiveOrganization($organizationB->id);
    $personB = Person::factory()->for($organizationB)->create();

    setActiveOrganization($organizationA->id);

    (new AssignTask)->handle($adminA, $task, $personB);
})->throws(HttpException::class);

test('complete task marks it done', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $task = Task::factory()->for($organization)->create();

    $task = (new CompleteTask)->handle($admin, $task);

    expect($task->status)->toBe(TaskStatus::Done)
        ->and($task->completed_at)->not->toBeNull()
        ->and($task->isDone())->toBeTrue();
});

test('update task can change status priority and link a person', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $person = Person::factory()->for($organization)->create();
    $task = Task::factory()->for($organization)->create();

    $task = (new UpdateTask)->handle($admin, $task, [
        'title' => 'Updated title',
        'description' => 'Details',
        'assignee_id' => $person->id,
        'due_date' => '2026-10-15',
        'priority' => TaskPriority::Low->value,
        'status' => TaskStatus::InProgress->value,
        'linked_person_ids' => [$person->id],
    ]);

    expect($task->title)->toBe('Updated title')
        ->and($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->priority)->toBe(TaskPriority::Low)
        ->and($task->assignee_id)->toBe($person->id)
        ->and($task->links)->toHaveCount(1)
        ->and($task->links->first()?->linkable_type)->toBe(TaskLinkableType::Person)
        ->and($task->links->first()?->linkable_id)->toBe($person->id)
        ->and($task->links->first()?->resolveLinkable()?->is($person))->toBeTrue();
});

test('delete task removes the row', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $task = Task::factory()->for($organization)->create();

    (new DeleteTask)->handle($admin, $task);

    expect(Task::query()->find($task->id))->toBeNull();
});

test('staff cannot create a task', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();
    $organization = $staff->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    (new CreateTask)->handle($staff, [
        'title' => 'Forbidden',
        'description' => null,
        'assignee_id' => null,
        'due_date' => null,
        'priority' => TaskPriority::Medium->value,
        'status' => TaskStatus::Open->value,
        'linked_person_ids' => [],
    ]);
})->throws(AuthorizationException::class);
