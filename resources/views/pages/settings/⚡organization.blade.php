<?php

use App\Actions\UpdateOrganizationSettings;
use App\Concerns\OrganizationSettingsValidationRules;
use App\Enums\ModuleName;
use App\Enums\OrganizationType;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Organization;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Organization settings')] class extends Component {
    use OrganizationSettingsValidationRules;

    public string $type = '';

    /**
     * @var list<string>
     */
    public array $enabledModules = [];

    public function mount(): void
    {
        $organization = $this->organization();

        $this->authorize('update', $organization);

        $this->type = $organization->type->value;
        $this->enabledModules = $organization->enabled_modules
            ->map(fn (ModuleName $module): string => $module->value)
            ->values()
            ->all();
    }

    public function save(UpdateOrganizationSettings $updateOrganizationSettings): void
    {
        $organization = $this->organization();
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->authorize('update', $organization);

        $validated = $this->validate($this->organizationSettingsRules());

        /** @var list<string> $enabledModuleValues */
        $enabledModuleValues = $validated['enabledModules'];

        $updateOrganizationSettings->handle(
            $user,
            $organization,
            OrganizationType::from($validated['type']),
            array_map(
                fn (string $value): ModuleName => ModuleName::from($value),
                $enabledModuleValues,
            ),
        );

        Flux::toast(variant: 'success', text: __('Organization settings updated.'));
    }

    /**
     * @return list<OrganizationType>
     */
    #[Computed]
    public function organizationTypes(): array
    {
        return OrganizationType::cases();
    }

    /**
     * @return list<ModuleName>
     */
    #[Computed]
    public function modules(): array
    {
        return ModuleName::cases();
    }

    private function organization(): Organization
    {
        $organization = EnsureActiveOrganization::organization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Organization settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Organization')" :subheading="__('Choose the organization type and enabled modules')">
        <form wire:submit="save" class="my-6 w-full space-y-6" data-test="organization-settings-form">
            <x-ui.form-errors />

            <flux:radio.group wire:model="type" :label="__('Organization type')" :invalid="$errors->has('type')">
                @foreach ($this->organizationTypes() as $organizationType)
                    <flux:radio
                        wire:key="type-{{ $organizationType->value }}"
                        :value="$organizationType->value"
                        :label="$organizationType->label()"
                    />
                @endforeach
            </flux:radio.group>
            <flux:error name="type" />

            <flux:checkbox.group wire:model="enabledModules" :label="__('Modules')" :invalid="$errors->has('enabledModules')">
                @foreach ($this->modules() as $module)
                    <flux:checkbox
                        wire:key="module-{{ $module->value }}"
                        :value="$module->value"
                        :label="$module->label()"
                        :data-test="'module-'.$module->value"
                    />
                @endforeach
            </flux:checkbox.group>
            <flux:error name="enabledModules" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full min-h-11" data-test="update-organization-settings-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
