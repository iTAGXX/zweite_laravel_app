<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

test('the mobile navigation can toggle the sidebar', function () {
    $user = User::factory()->withOrganization()->create();

    Livewire::actingAs($user)
        ->test('mobile-navigation')
        ->assertSet('sidebarOpen', false)
        ->assertSee(__('Dashboard'))
        ->assertSee(__('Menu'))
        ->call('toggleSidebar')
        ->assertSet('sidebarOpen', true)
        ->assertSee('aria-expanded="true"', false)
        ->call('toggleSidebar')
        ->assertSet('sidebarOpen', false)
        ->assertSee('aria-expanded="false"', false);
});
