@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-800">
        <div class="mx-auto flex min-h-svh max-w-lg flex-col justify-center gap-6 p-6">
            {{ $slot }}
        </div>

        @fluxScripts
    </body>
</html>
