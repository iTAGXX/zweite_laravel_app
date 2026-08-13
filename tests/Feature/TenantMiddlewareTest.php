<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users without an organization see the no-organization page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden()
        ->assertSee(__('No organization'), false);
});

test('authenticated users with an organization can visit the dashboard', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSessionHas(EnsureActiveOrganization::SESSION_KEY);
});
