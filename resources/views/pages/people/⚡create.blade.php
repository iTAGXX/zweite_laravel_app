<?php

use App\Actions\CreatePerson;
use App\Concerns\PersonValidationRules;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Person;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New person')] class extends Component
{
    use PersonValidationRules;

    public string $firstName = '';

    public string $lastName = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $street = null;

    public ?string $postalCode = null;

    public ?string $city = null;

    public ?string $country = null;

    public ?string $dateOfBirth = null;

    public ?string $notes = null;

    public ?string $userId = null;

    public function mount(): void
    {
        $this->authorize('create', Person::class);
    }

    public function save(CreatePerson $createPerson): void
    {
        $this->authorize('create', Person::class);

        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $this->email = $this->email === '' ? null : $this->email;
        $this->phone = $this->phone === '' ? null : $this->phone;
        $this->street = $this->street === '' ? null : $this->street;
        $this->postalCode = $this->postalCode === '' ? null : $this->postalCode;
        $this->city = $this->city === '' ? null : $this->city;
        $this->country = $this->country === '' ? null : $this->country;
        $this->dateOfBirth = $this->dateOfBirth === '' ? null : $this->dateOfBirth;
        $this->notes = $this->notes === '' ? null : $this->notes;
        $this->userId = $this->userId === '' ? null : $this->userId;

        $validated = $this->validate($this->personRules());

        $createPerson->handle($user, $this->personPayload($validated));

        Flux::toast(variant: 'success', text: __('Person created.'));

        $this->redirect(route('people.index'), navigate: true);
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function linkableUsers(): Collection
    {
        $organizationId = EnsureActiveOrganization::id();

        abort_unless(is_string($organizationId), 403);

        $takenUserIds = Person::query()
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return User::query()
            ->whereHas('memberships', function ($query) use ($organizationId): void {
                $query->where('organization_id', $organizationId);
            })
            ->whereNotIn('id', $takenUserIds)
            ->orderBy('name')
            ->get();
    }
}; ?>

<section class="flex w-full min-w-0 flex-col gap-6">
    <div class="flex flex-col gap-2">
        <flux:heading>{{ __('New person') }}</flux:heading>
        <flux:subheading>{{ __('Contact details for the shared people directory. Login, member, and customer profiles stay optional.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-lg space-y-6" data-test="person-form">
        <x-ui.form-errors />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="firstName" :label="__('First name')" required autocomplete="given-name" />
            <flux:input wire:model="lastName" :label="__('Last name')" required autocomplete="family-name" />
        </div>

        <flux:input wire:model="email" type="email" :label="__('Email')" autocomplete="email" />
        <flux:input wire:model="phone" type="tel" :label="__('Phone')" autocomplete="tel" />
        <flux:input wire:model="street" :label="__('Street')" autocomplete="street-address" />

        <div class="grid gap-6 sm:grid-cols-2">
            <flux:input wire:model="postalCode" :label="__('Postal code')" autocomplete="postal-code" />
            <flux:input wire:model="city" :label="__('City')" autocomplete="address-level2" />
        </div>

        <flux:input wire:model="country" :label="__('Country')" autocomplete="country-name" />
        <flux:input wire:model="dateOfBirth" type="date" :label="__('Date of birth')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" rows="4" />

        <flux:select wire:model="userId" :label="__('Linked login')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->linkableUsers as $linkableUser)
                <flux:select.option wire:key="user-{{ $linkableUser->id }}" :value="$linkableUser->id">
                    {{ $linkableUser->name }} ({{ $linkableUser->email }})
                </flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex flex-col gap-3 sm:flex-row">
            <flux:button variant="primary" type="submit" class="min-h-11" data-test="save-person-button">
                {{ __('Save') }}
            </flux:button>
            <flux:button :href="route('people.index')" variant="ghost" wire:navigate class="min-h-11">
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>
</section>
