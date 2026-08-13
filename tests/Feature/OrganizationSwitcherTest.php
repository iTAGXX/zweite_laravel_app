<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('switching organization updates the session and permissions immediately', function () {
    $user = User::factory()->withOrganization(RoleName::Staff)->create();
    $organizationA = $user->organizations()->firstOrFail();

    $organizationB = Organization::factory()->create();
    OrganizationMembership::factory()
        ->for($user)
        ->for($organizationB)
        ->forRole(RoleName::Admin)
        ->create();

    $this->actingAs($user)
        ->withSession([EnsureActiveOrganization::SESSION_KEY => $organizationA->id])
        ->get(route('members'))
        ->assertForbidden();

    session([EnsureActiveOrganization::SESSION_KEY => $organizationA->id]);

    Livewire::actingAs($user)
        ->test('organization-switcher')
        ->call('switchOrganization', $organizationB->id)
        ->assertRedirect(route('dashboard'));

    expect(session(EnsureActiveOrganization::SESSION_KEY))->toBe($organizationB->id);

    $this->actingAs($user)
        ->withSession([EnsureActiveOrganization::SESSION_KEY => $organizationB->id])
        ->get(route('members'))
        ->assertOk();
});

test('a user cannot activate an organization without membership', function () {
    $user = User::factory()->withOrganization(RoleName::Member)->create();
    $foreign = Organization::factory()->create();

    Livewire::actingAs($user)
        ->test('organization-switcher')
        ->call('switchOrganization', $foreign->id)
        ->assertForbidden();

    expect($user->memberships()->where('organization_id', $foreign->id)->exists())->toBeFalse();
});
