<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration stores a hashed password not plaintext', function () {
    $plainPassword = 'password';

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => $plainPassword,
        'password_confirmation' => $plainPassword,
    ])->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();
    $storedPassword = $user->getRawOriginal('password');

    expect($storedPassword)->not->toBe($plainPassword)
        ->and(Hash::isHashed($storedPassword))->toBeTrue()
        ->and(Hash::check($plainPassword, $storedPassword))->toBeTrue();
});

test('new users receive a verification email', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);
});
