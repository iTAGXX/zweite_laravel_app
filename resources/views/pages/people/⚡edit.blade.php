<?php

use App\Actions\ArchivePerson;
use App\Actions\UnarchivePerson;
use App\Actions\UpdatePerson;
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

new #[Title('Edit person')] class extends Component
{
    use PersonValidationRules;

    public Person $person;

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

    public function mount(Person $person): void
    {
        $this->authorize('update', $person);

        $this->person = $person;
        $this->firstName = $person->first_name;
        $this->lastName = $person->last_name;
        $this->email = $person->email;
        $this->phone = $person->phone;
        $this->street = $person->street;
        $this->postalCode = $person->postal_code;
        $this->city = $person->city;
        $this->country = $person->country;
        $this->dateOfBirth = $person->date_of_birth?->toDateString();
        $this->notes = $person->notes;
        $this->userId = $person->user_id !== null ? (string) $person->user_id : null;
    }

    public function save(UpdatePerson $updatePerson): void
    {
        $this->authorize('update', $this->person);

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

        $validated = $this->validate($this->personRules($this->person));

        $updatePerson->handle($user, $this->person, $this->personPayload($validated));

        Flux::toast(variant: 'success', text: __('Person updated.'));

        $this->redirect(route('people.index'), navigate: true);
    }

    public function archive(ArchivePerson $archivePerson): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $archivePerson->handle($user, $this->person);

        Flux::toast(variant: 'success', text: __('Person archived.'));

        $this->redirect(route('people.index'), navigate: true);
    }

    public function unarchive(UnarchivePerson $unarchivePerson): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $unarchivePerson->handle($user, $this->person);

        Flux::toast(variant: 'success', text: __('Person restored.'));

        $this->redirect(route('people.edit', $this->person), navigate: true);
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
            ->where('id', '!=', $this->person->id)
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
        <flux:heading>{{ __('Edit person') }}</flux:heading>
        <flux:subheading>{{ $this->person->display_name }}</flux:subheading>
        @if ($this->person->isArchived())
            <flux:badge color="zinc" size="sm">{{ __('Archived') }}</flux:badge>
        @endif
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

    <div class="max-w-lg border-t border-zinc-200 pt-6 dark:border-zinc-700">
        @if ($this->person->isArchived())
            <flux:button wire:click="unarchive" variant="primary" class="min-h-11" data-test="unarchive-person-button">
                {{ __('Restore person') }}
            </flux:button>
        @else
            <flux:button
                wire:click="archive"
                wire:confirm="{{ __('Archive this person? They will be hidden from the default list.') }}"
                variant="danger"
                class="min-h-11"
                data-test="archive-person-button"
            >
                {{ __('Archive person') }}
            </flux:button>
        @endif
    </div>
</section>
