<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('admins see login users of the active organization including the demo admin', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create([
        'name' => 'Test User',
        'email' => DatabaseSeeder::DEMO_ADMIN_EMAIL,
    ]);
    $organization = $admin->organizations()->firstOrFail();

    $member = User::factory()->create([
        'name' => 'Anna Member',
        'email' => 'anna-member@example.com',
    ]);
    OrganizationMembership::factory()
        ->for($member)
        ->for($organization)
        ->forRole(RoleName::Member)
        ->create();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('invite-members')
        ->assertOk()
        ->assertSeeInOrder(['Test User', DatabaseSeeder::DEMO_ADMIN_EMAIL, 'Admin'])
        ->assertSeeInOrder(['Anna Member', 'anna-member@example.com', 'Member']);

    $this->actingAs($admin)
        ->get(route('members'))
        ->assertOk()
        ->assertSee(DatabaseSeeder::DEMO_ADMIN_EMAIL)
        ->assertSee('anna-member@example.com');
});

test('memberships from other organizations are not listed', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create([
        'name' => 'Home Admin',
        'email' => 'home-admin@example.com',
    ]);
    $organization = $admin->organizations()->firstOrFail();

    $foreignOrganization = Organization::factory()->create();
    $foreignUser = User::factory()->create([
        'name' => 'Foreign Admin',
        'email' => 'foreign-org-admin@example.net',
    ]);
    OrganizationMembership::factory()
        ->for($foreignUser)
        ->for($foreignOrganization)
        ->forRole(RoleName::Admin)
        ->create();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('invite-members')
        ->assertOk()
        ->assertSee('home-admin@example.com')
        ->assertDontSee('foreign-org-admin@example.net')
        ->assertDontSee('Foreign Admin');
});

test('staff cannot view the organization members list', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create([
        'email' => 'staff-without-manage@example.com',
    ]);
    $organization = $staff->organizations()->firstOrFail();
    setActiveOrganization($organization->id);

    $this->actingAs($staff)
        ->get(route('members'))
        ->assertForbidden()
        ->assertDontSee('staff-without-manage@example.com');

    Livewire::actingAs($staff)
        ->test('invite-members')
        ->assertForbidden();
});
