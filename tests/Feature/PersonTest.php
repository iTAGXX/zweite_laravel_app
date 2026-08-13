<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\OrganizationMembership;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('people use the belongs to organization scope', function () {
    expect(class_uses_recursive(Person::class))->toContain(BelongsToOrganization::class);
});

test('queries without an active organization return no people', function () {
    Person::factory()->create();

    EnsureActiveOrganization::forget();

    expect(Person::query()->count())->toBe(0);
});

test('a person is a shared identity without an exclusive profile type', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $person = Person::factory()->for($organization)->create([
        'user_id' => null,
    ]);

    expect($person->user_id)->toBeNull()
        ->and(Schema::hasColumn('people', 'type'))->toBeFalse()
        ->and(Schema::hasColumn('people', 'role'))->toBeFalse()
        ->and(Schema::hasColumn('people', 'user_id'))->toBeTrue();
});

test('admins can create a person', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::people.create')
        ->set('firstName', 'Ada')
        ->set('lastName', 'Lovelace')
        ->set('email', 'ada@example.com')
        ->set('phone', '+49 1234')
        ->set('city', 'Berlin')
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::query()->where('email', 'ada@example.com')->first();

    expect($person)->not->toBeNull()
        ->and($person?->first_name)->toBe('Ada')
        ->and($person?->last_name)->toBe('Lovelace')
        ->and($person?->organization_id)->toBe($organization->id)
        ->and($person?->user_id)->toBeNull()
        ->and($person?->archived_at)->toBeNull();
});

test('admins can update a person and optionally link a login user', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $member = User::factory()->create();
    OrganizationMembership::factory()
        ->for($member)
        ->for($organization)
        ->forRole(RoleName::Member)
        ->create();

    $person = Person::factory()->for($organization)->create([
        'first_name' => 'Grace',
        'last_name' => 'Hopper',
        'email' => 'grace@example.com',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::people.edit', ['person' => $person])
        ->set('firstName', 'Grace')
        ->set('lastName', 'Hopper')
        ->set('email', 'grace.hopper@example.com')
        ->set('userId', (string) $member->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($person->refresh()->email)->toBe('grace.hopper@example.com')
        ->and($person->user_id)->toBe($member->id)
        ->and($person->user?->is($member))->toBeTrue();
});

test('archived people are hidden from the default list', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    Person::factory()->for($organization)->create([
        'first_name' => 'Visible',
        'last_name' => 'Active',
    ]);
    Person::factory()->for($organization)->archived()->create([
        'first_name' => 'Hidden',
        'last_name' => 'Archive',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::people.index')
        ->assertSee('Visible Active')
        ->assertDontSee('Hidden Archive');

    Livewire::test('pages::people.index')
        ->set('status', 'archived')
        ->assertSee('Hidden Archive')
        ->assertDontSee('Visible Active');
});

test('admins can archive and restore a person', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $person = Person::factory()->for($organization)->create([
        'first_name' => 'Alan',
        'last_name' => 'Turing',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::people.edit', ['person' => $person])
        ->call('archive')
        ->assertHasNoErrors();

    expect($person->refresh()->isArchived())->toBeTrue();

    Livewire::test('pages::people.edit', ['person' => $person])
        ->call('unarchive')
        ->assertHasNoErrors();

    expect($person->refresh()->isArchived())->toBeFalse();
});

test('search filters people by name and email', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    Person::factory()->for($organization)->create([
        'first_name' => 'Marie',
        'last_name' => 'Curie',
        'email' => 'marie@example.com',
    ]);
    Person::factory()->for($organization)->create([
        'first_name' => 'Niels',
        'last_name' => 'Bohr',
        'email' => 'niels@example.com',
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::people.index')
        ->set('search', 'marie')
        ->assertSee('Marie Curie')
        ->assertDontSee('Niels Bohr');
});

test('staff cannot open people pages', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();
    $organization = $staff->organizations()->firstOrFail();
    $person = Person::factory()->for($organization)->create();

    $this->actingAs($staff)
        ->get(route('people.index'))
        ->assertForbidden();

    setActiveOrganization($organization->id);

    expect($staff->can('create', Person::class))->toBeFalse()
        ->and($staff->can('update', $person))->toBeFalse();
});

test('a foreign person id returns 404 without leaking data', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    Person::factory()->for($organizationA)->create();

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $personB = Person::factory()->for($organizationB)->create([
        'first_name' => 'Secret',
        'last_name' => 'Tenant',
        'email' => 'secret-tenant@example.com',
    ]);

    $this->actingAs($userA)
        ->get(route('people.edit', $personB))
        ->assertNotFound()
        ->assertDontSee('Secret Tenant')
        ->assertDontSee('secret-tenant@example.com');
});

test('eloquent cannot read another organizations person', function () {
    $userA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $userA->organizations()->firstOrFail();
    $personA = Person::factory()->for($organizationA)->create();

    $userB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $userB->organizations()->firstOrFail();
    $personB = Person::factory()->for($organizationB)->create();

    setActiveOrganization($organizationA->id);

    expect(Person::query()->find($personA->id))->not->toBeNull()
        ->and(Person::query()->find($personB->id))->toBeNull()
        ->and(Person::query()->count())->toBe(1);
});

test('people index is displayed for admins', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get(route('people.index'))
        ->assertOk()
        ->assertSeeLivewire('pages::people.index')
        ->assertSee(__('People'));
});

test('guests are redirected from people to login', function () {
    $this->get(route('people.index'))->assertRedirect(route('login'));
});
