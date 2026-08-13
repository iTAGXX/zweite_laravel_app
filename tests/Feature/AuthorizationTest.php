<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

test('staff cannot open finance, members, people, or audit', function () {
    $user = User::factory()->withOrganization(RoleName::Staff)->create();

    $this->actingAs($user)
        ->get(route('finance'))
        ->assertForbidden()
        ->assertDontSee('secret-finance-data');

    $this->actingAs($user)
        ->get(route('members'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('people.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('audit'))
        ->assertForbidden();
});

test('treasurer can open finance but not members or people', function () {
    $user = User::factory()->withOrganization(RoleName::Treasurer)->create();

    $this->actingAs($user)
        ->get(route('finance'))
        ->assertOk()
        ->assertSee(__('Finance'));

    $this->actingAs($user)
        ->get(route('members'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('people.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('audit'))
        ->assertForbidden();
});

test('admin can open finance, members, people, documents, and audit', function () {
    $user = User::factory()->withOrganization(RoleName::Admin)->create();

    $this->actingAs($user)
        ->get(route('finance'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('members'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('people.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('documents.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('audit'))
        ->assertOk();
});

test('staff does not see finance, members, or people in the sidebar', function () {
    $user = User::factory()->withOrganization(RoleName::Staff)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('Finance'), false)
        ->assertDontSee(route('finance'), false)
        ->assertDontSee(route('members'), false)
        ->assertDontSee(route('people.index'), false)
        ->assertDontSee(route('tasks.index'), false)
        ->assertDontSee(route('documents.index'), false)
        ->assertDontSee(route('audit'), false);
});

test('treasurer sees finance but not members in the sidebar', function () {
    $user = User::factory()->withOrganization(RoleName::Treasurer)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('Finance'))
        ->assertDontSee(__('Members'), false)
        ->assertDontSee(__('People'), false)
        ->assertDontSee(__('Tasks'), false)
        ->assertDontSee(__('Documents'), false);
});
