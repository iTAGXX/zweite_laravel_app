<?php

declare(strict_types=1);

use App\Enums\ModuleName;
use App\Enums\OrganizationType;
use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('mixed organizations expose club and stable modules', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    expect($organization->type)->toBe(OrganizationType::Mixed)
        ->and($organization->hasModule(ModuleName::Club))->toBeTrue()
        ->and($organization->hasModule(ModuleName::Stable))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('members'), false)
        ->assertSee(route('stable'), false);

    $this->actingAs($admin)
        ->get(route('members'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('stable'))
        ->assertOk();
});

test('a disabled club module is hidden from the ui and blocked on the route', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $organization->update([
        'type' => OrganizationType::Stable,
        'enabled_modules' => OrganizationType::Stable->defaultModules(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('members'), false)
        ->assertSee(route('stable'), false);

    $this->actingAs($admin)
        ->get(route('members'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('stable'))
        ->assertOk();
});

test('a disabled stable module is hidden from the ui and blocked on the route', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();
    $organization->update([
        'type' => OrganizationType::Club,
        'enabled_modules' => OrganizationType::Club->defaultModules(),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('members'), false)
        ->assertDontSee(route('stable'), false);

    $this->actingAs($admin)
        ->get(route('stable'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('members'))
        ->assertOk();
});

test('disabling a module in settings blocks its route and removes it from navigation', function () {
    $admin = User::factory()->withOrganization(RoleName::Admin)->create();
    $organization = $admin->organizations()->firstOrFail();

    $this->actingAs($admin);
    setActiveOrganization($organization->id);

    Livewire::test('pages::settings.organization')
        ->set('type', OrganizationType::Club->value)
        ->set('enabledModules', [ModuleName::Club->value])
        ->call('save')
        ->assertHasNoErrors();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('members'), false)
        ->assertDontSee(route('stable'), false);

    $this->actingAs($admin)
        ->get(route('stable'))
        ->assertForbidden();
});
