<?php

test('http responses include the security headers', function (string $route) {
    $this->get(route($route))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
})->with([
    'home',
    'login',
]);
