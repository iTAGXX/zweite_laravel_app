@unless (request()->routeIs('pwa.offline'))
    <div
        x-data="{ offline: ! navigator.onLine }"
        x-on:online.window="offline = false"
        x-on:offline.window="offline = true"
        x-cloak
        x-show="offline"
        class="pointer-events-none fixed inset-x-0 top-0 z-50 p-4"
        data-test="offline-notice"
        role="status"
        aria-live="polite"
    >
        <div class="pointer-events-auto mx-auto max-w-lg">
            <x-ui.error-state
                :heading="__('You are offline')"
                :text="__('Changes cannot be saved until you are back online.')"
            />
        </div>
    </div>
@endunless
