<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::resetPasswords());
});

test('reset password link screen can be rendered', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertOk();

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
});

test('password reset tokens are stored hashed', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

    expect($storedToken)->toBeString()
        ->and(Hash::isHashed($storedToken))->toBeTrue();
});

test('password reset is enumeration safe for unknown emails', function () {
    Notification::fake();

    $user = User::factory()->create();

    $known = $this->from(route('password.request'))
        ->post(route('password.request'), ['email' => $user->email]);
    $knownStatus = session('status');

    $unknown = $this->from(route('password.request'))
        ->post(route('password.request'), ['email' => 'nobody@example.com']);
    $unknownStatus = session('status');

    $known->assertSessionHasNoErrors();
    $unknown->assertSessionHasNoErrors();

    expect($knownStatus)->toBe($unknownStatus)->not->toBeEmpty();

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertSentTimes(ResetPassword::class, 1);
});

test('an expired password reset token is rejected', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $this->travel(61)->minutes();

        $this->from(route('password.reset', $notification->token))
            ->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors();

        $this->assertGuest();

        return true;
    });
});

test('a password reset token can be used only once', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.request'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->from(route('password.reset', $notification->token))
            ->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors();

        return true;
    });
});

test('password reset invalidates the previous session', function () {
    $user = User::factory()->withOrganization()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $this->assertAuthenticated();
    $this->get(route('dashboard'))->assertOk();

    $token = Password::broker()->createToken($user);

    Password::broker()->reset([
        'email' => $user->email,
        'token' => $token,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ], function (User $user, string $password): void {
        $user->forceFill([
            'password' => $password,
        ])->save();

        $user->setRememberToken(Str::random(60));
        $user->save();
    });

    Auth::forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
