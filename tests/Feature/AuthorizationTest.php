<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('staff cannot open finance or members', function () {
    $user = User::factory()->withOrganization(RoleName::Staff)->create();

    $this->actingAs($user)
        ->get(route('finance'))
        ->assertForbidden()
        ->assertDontSee('secret-finance-data');

    $this->actingAs($user)
        ->get(route('members'))
        ->assertForbidden();
});

test('treasurer can open finance but not members', function () {
    $user = User::factory()->withOrganization(RoleName::Treasurer)->create();

    $this->actingAs($user)
        ->get(route('finance'))
        ->assertOk()
        ->assertSee(__('Finance'));

    $this->actingAs($user)
        ->get(route('members'))
        ->assertForbidden();
});

test('admin can open finance and members', function () {
    $user = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($user)
        ->get(route('finance'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('members'))
        ->assertOk();
});

test('staff does not see finance or members in the sidebar', function () {
    $user = User::factory()->withOrganization(RoleName::Staff)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('Finance'), false)
        ->assertDontSee(route('finance'), false)
        ->assertDontSee(route('members'), false);
});

test('treasurer sees finance but not members in the sidebar', function () {
    $user = User::factory()->withOrganization(RoleName::Treasurer)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Finance'))
        ->assertDontSee(__('Members'), false);
});
