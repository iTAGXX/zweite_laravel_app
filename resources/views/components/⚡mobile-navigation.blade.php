<?php

use Livewire\Component;

new class extends Component
{
    public bool $sidebarOpen = false;

    public function toggleSidebar(): void
    {
        $this->sidebarOpen = ! $this->sidebarOpen;
    }
};
?>

<nav
    data-test="mobile-bottom-nav"
    class="fixed inset-x-0 bottom-0 z-40 max-w-full overflow-x-hidden border-t border-zinc-200 bg-zinc-50 pb-[env(safe-area-inset-bottom)] lg:hidden dark:border-zinc-700 dark:bg-zinc-900"
    aria-label="{{ __('Main') }}"
>
    <div class="flex min-h-11 items-stretch justify-around">
        <a
            href="{{ route('dashboard') }}"
            wire:navigate
            @class([
                'flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs touch-manipulation',
                'text-zinc-800 dark:text-white' => request()->routeIs('dashboard'),
                'text-zinc-500 dark:text-white/80' => ! request()->routeIs('dashboard'),
            ])
            @if (request()->routeIs('dashboard')) aria-current="page" @endif
        >
            <flux:icon.home class="size-5" />
            <span class="truncate">{{ __('Dashboard') }}</span>
        </a>

        <a
            href="{{ route('profile.edit') }}"
            wire:navigate
            @class([
                'flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs touch-manipulation',
                'text-zinc-800 dark:text-white' => request()->routeIs('profile.edit'),
                'text-zinc-500 dark:text-white/80' => ! request()->routeIs('profile.edit'),
            ])
            @if (request()->routeIs('profile.edit')) aria-current="page" @endif
        >
            <flux:icon.cog class="size-5" />
            <span class="truncate">{{ __('Settings') }}</span>
        </a>

        <button
            type="button"
            wire:click="toggleSidebar"
            x-on:click="$dispatch('flux-sidebar-toggle')"
            class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 px-1 py-2 text-xs text-zinc-500 touch-manipulation dark:text-white/80"
            data-test="sidebar-toggle"
            aria-expanded="{{ $sidebarOpen ? 'true' : 'false' }}"
            aria-label="{{ __('Toggle sidebar') }}"
        >
            <flux:icon.bars-2 class="size-5" />
            <span class="truncate">{{ __('Menu') }}</span>
        </button>
    </div>
</nav>
