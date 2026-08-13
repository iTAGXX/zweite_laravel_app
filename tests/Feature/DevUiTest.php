<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('guests are redirected from the ui kit to login', function () {
    $this->get(route('dev.ui'))->assertRedirect(route('login'));
});

test('authenticated users without an organization cannot view the ui kit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dev.ui'))
        ->assertForbidden();
});

test('authenticated users can view the flux building blocks', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('dev.ui'))
        ->assertOk()
        ->assertSee(__('UI kit'))
        ->assertSee(__('Button'))
        ->assertSee(__('Input'))
        ->assertSee(__('Select'))
        ->assertSee(__('Modal'))
        ->assertSee(__('Table'))
        ->assertSee('data-test="empty-state"', false)
        ->assertSee('data-test="error-state"', false)
        ->assertSee('data-test="loading-state"', false);
});

test('the ui kit route is registered in testing', function () {
    expect(Route::has('dev.ui'))->toBeTrue();
});
