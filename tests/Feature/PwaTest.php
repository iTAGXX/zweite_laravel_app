<?php

declare(strict_types=1);

use App\Models\User;

test('the manifest route returns installable web app json', function () {
    $response = $this->get(route('pwa.manifest'));

    $response
        ->assertSuccessful()
        ->assertExactJson([
            'id' => '/',
            'name' => config('app.name'),
            'short_name' => config('pwa.short_name'),
            'description' => config('pwa.description'),
            'start_url' => config('pwa.start_url'),
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => config('pwa.background_color'),
            'theme_color' => config('pwa.theme_color'),
            'lang' => config('app.locale'),
            'icons' => [
                [
                    'src' => '/icons/icon-192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => '/icons/icon-512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
        ]);

    expect($response->headers->get('content-type'))->toContain('application/manifest+json');
});

test('the offline fallback page is displayed', function () {
    $this->get(route('pwa.offline'))
        ->assertSuccessful()
        ->assertSee(__('You are offline'))
        ->assertSee(__('Reconnect to continue. Changes cannot be saved while you are offline.'))
        ->assertSee('data-test="error-state"', false)
        ->assertDontSee('data-test="offline-notice"', false);
});

test('the app shell links the manifest and shows an offline notice hook', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('rel="manifest"', false)
        ->assertSee(route('pwa.manifest', absolute: false), false)
        ->assertSee('name="theme-color"', false)
        ->assertSee((string) config('pwa.theme_color'), false)
        ->assertSee('name="apple-mobile-web-app-capable"', false)
        ->assertSee('content="yes"', false)
        ->assertSee('data-test="offline-notice"', false)
        ->assertSee('name="pwa-offline-write-heading"', false);
});

test('placeholder app icons exist', function () {
    expect(is_file(public_path('icons/icon-192.png')))->toBeTrue();
    expect(is_file(public_path('icons/icon-512.png')))->toBeTrue();
    expect(is_file(public_path('apple-touch-icon.png')))->toBeTrue();
});

test('the service worker caches the app shell and ignores non-get writes', function () {
    $this->get(route('pwa.service-worker'))->assertSuccessful();

    $script = (string) file_get_contents(public_path('sw.js'));

    expect($script)
        ->toContain('equiflow-shell-v1')
        ->toContain('/offline')
        ->toContain("request.method !== 'GET'");
});
