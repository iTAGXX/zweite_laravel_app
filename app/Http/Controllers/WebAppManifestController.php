<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class WebAppManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json([
                'id' => '/',
                'name' => (string) config('app.name'),
                'short_name' => (string) config('pwa.short_name'),
                'description' => (string) config('pwa.description'),
                'start_url' => (string) config('pwa.start_url'),
                'scope' => '/',
                'display' => (string) config('pwa.display'),
                'background_color' => (string) config('pwa.background_color'),
                'theme_color' => (string) config('pwa.theme_color'),
                'lang' => (string) config('app.locale'),
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
            ])
            ->header('Content-Type', 'application/manifest+json');
    }
}
