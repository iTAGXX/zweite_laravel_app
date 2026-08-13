<?php

declare(strict_types=1);

use App\Actions\UpdateOrganizationSettings;
use App\Enums\ModuleName;
use App\Enums\OrganizationType;
use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('organization settings page is displayed for admins', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($admin)
        ->get(route('organization.edit'))
        ->assertOk()
        ->assertSeeLivewire('pages::settings.organization')
        ->assertSee(__('Organization type'))
        ->assertSee(__('Modules'));
});

test('staff cannot open organization settings', function () {
    $staff = User::factory()->withOrganization(RoleName::Staff)->create();

    $this->actingAs($staff)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

test('guests are redirected from organization settings to login', function () {
    $this->get(route('organization.edit'))->assertRedirect(route('login'));
});

test('admins can update the organization type and modules', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::settings.organization')
        ->set('type', OrganizationType::Club->value)
        ->set('enabledModules', [ModuleName::Club->value])
        ->call('save')
        ->assertHasNoErrors();

    $organization->refresh();

    expect($organization->type)->toBe(OrganizationType::Club)
        ->and($organization->hasModule(ModuleName::Club))->toBeTrue()
        ->and($organization->hasModule(ModuleName::Stable))->toBeFalse();
});

test('organization settings changes only affect the active organization', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $admin->organizations()->firstOrFail();

    $organizationB = Organization::factory()->create();
    OrganizationMembership::factory()
        ->for($admin)
        ->for($organizationB)
        ->forRole(RoleName::Admin)
        ->create();

    $this->actingAs($admin);
    setActiveOrganization($organizationA->id);

    expect($admin->can('update', $organizationA))->toBeTrue()
        ->and($admin->can('update', $organizationB))->toBeFalse();

    Livewire::test('pages::settings.organization')
        ->set('type', OrganizationType::Stable->value)
        ->set('enabledModules', [ModuleName::Stable->value])
        ->call('save')
        ->assertHasNoErrors();

    expect($organizationA->refresh()->type)->toBe(OrganizationType::Stable)
        ->and($organizationA->hasModule(ModuleName::Stable))->toBeTrue()
        ->and($organizationB->refresh()->type)->toBe(OrganizationType::Mixed)
        ->and($organizationB->hasModule(ModuleName::Club))->toBeTrue()
        ->and($organizationB->hasModule(ModuleName::Stable))->toBeTrue();
});

test('admins cannot update another tenants organization', function () {
    $adminA = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationA = $adminA->organizations()->firstOrFail();

    $adminB = User::factory()->withOrganization(RoleName::Admin)->create();
    $organizationB = $adminB->organizations()->firstOrFail();

    $this->actingAs($adminA);
    setActiveOrganization($organizationA->id);

    expect($adminA->can('update', $organizationB))->toBeFalse();

    app(UpdateOrganizationSettings::class)->handle(
        $adminA,
        $organizationB,
        OrganizationType::Club,
        [ModuleName::Club],
    );
})->throws(AuthorizationException::class);

test('invalid organization type is rejected', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::settings.organization')
        ->set('type', 'nonprofit')
        ->set('enabledModules', [ModuleName::Club->value])
        ->call('save')
        ->assertHasErrors(['type']);

    expect($organization->refresh()->type)->toBe(OrganizationType::Mixed);
});
