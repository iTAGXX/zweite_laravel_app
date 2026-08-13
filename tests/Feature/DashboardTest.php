<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->withOrganization()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard is renderable as a 360px shell', function () {
    $user = User::factory()->withOrganization()->create();
    $organization = $user->organizations()->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire('mobile-navigation')
        ->assertSee('data-test="mobile-bottom-nav"', false)
        ->assertSee('data-test="organization-switcher"', false)
        ->assertSee('data-test="sidebar-toggle"', false)
        ->assertSee('overflow-x-hidden', false)
        ->assertSee('min-h-11', false)
        ->assertSee($organization->name);
});
