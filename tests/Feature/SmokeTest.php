<?php

use App\Models\User;

test('the home page is reachable', function () {
    $this->get(route('home'))->assertOk();
});

test('guests are redirected from the dashboard to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
