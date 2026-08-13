<?php

use App\Actions\SwitchOrganization;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function switchOrganization(string $organizationId, SwitchOrganization $switchOrganization): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $switchOrganization->handle($user, $organizationId);

        $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * @return Collection<int, Organization>
     */
    #[Computed]
    public function organizations(): Collection
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user->organizations()->orderBy('name')->get();
    }

    #[Computed]
    public function activeOrganization(): ?Organization
    {
        $organizationId = EnsureActiveOrganization::id();

        if ($organizationId === null) {
            return $this->organizations->first();
        }

        return $this->organizations->firstWhere('id', $organizationId);
    }
};
?>

<div {{ $attributes->class('min-w-0 max-w-full') }} data-test="organization-switcher">
    <flux:dropdown class="w-full min-w-0">
        <flux:button
            icon="building-office-2"
            icon:trailing="chevron-down"
            class="min-h-11 w-full max-w-full justify-start overflow-hidden"
            align="start"
        >
            <span class="truncate">{{ $this->activeOrganization?->name ?? __('Organization') }}</span>
        </flux:button>

        <flux:menu>
            @foreach ($this->organizations as $organization)
                <flux:menu.item
                    wire:key="org-{{ $organization->id }}"
                    wire:click="switchOrganization('{{ $organization->id }}')"
                    :icon="$organization->id === $this->activeOrganization?->id ? 'check' : null"
                >
                    {{ $organization->name }}
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>
</div>
