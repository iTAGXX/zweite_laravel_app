<?php

use App\Actions\InviteMember;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Middleware\EnsureActiveOrganization;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $email = '';

    public string $roleId = '';

    public function mount(): void
    {
        $this->authorize(PermissionName::UsersManage->value);

        $memberRoleId = Role::query()->where('slug', RoleName::Member->value)->value('id');

        $this->roleId = $memberRoleId !== null ? (string) $memberRoleId : '';
    }

    public function sendInvitation(InviteMember $inviteMember): void
    {
        $this->authorize('create', Invitation::class);

        $organization = $this->activeOrganization();

        $validated = $this->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($organization): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $alreadyMember = $organization->users()->where('email', $value)->exists();

                    if ($alreadyMember) {
                        $fail(__('This person already belongs to the organization.'));
                    }
                },
            ],
            'roleId' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $role = Role::query()->findOrFail((int) $validated['roleId']);

        $inviteMember->handle($organization, $validated['email'], $role);

        $this->reset('email');

        $this->roleId = (string) (Role::query()->where('slug', RoleName::Member->value)->value('id') ?? $role->id);

        Flux::toast(variant: 'success', text: __('Invitation sent.'));
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, OrganizationMembership>
     */
    #[Computed]
    public function memberships(): Collection
    {
        $this->authorize(PermissionName::UsersManage->value);

        return OrganizationMembership::query()
            ->with(['user', 'role'])
            ->where('organization_id', $this->activeOrganization()->id)
            ->oldest()
            ->get();
    }

    /**
     * @return Collection<int, Invitation>
     */
    #[Computed]
    public function pendingInvitations(): Collection
    {
        return Invitation::query()
            ->with('role')
            ->whereNull('used_at')
            ->latest()
            ->get();
    }

    private function activeOrganization(): Organization
    {
        $organizationId = EnsureActiveOrganization::id();

        abort_unless(is_string($organizationId), 403);

        return Organization::query()->findOrFail($organizationId);
    }
};
?>

<div class="flex w-full min-w-0 flex-col gap-8">
    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Users') }}</flux:heading>

        @if ($this->memberships->isEmpty())
            <x-ui.empty-state :heading="__('No users yet')" :text="__('Invite someone with their email address.')" />
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Role') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->memberships as $membership)
                        <flux:table.row :key="$membership->id">
                            <flux:table.cell>{{ $membership->user?->name }}</flux:table.cell>
                            <flux:table.cell>{{ $membership->user?->email }}</flux:table.cell>
                            <flux:table.cell>{{ $membership->role?->name }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </div>

    <form wire:submit="sendInvitation" class="max-w-lg space-y-6">
        <flux:heading>{{ __('Invite a member') }}</flux:heading>

        <x-ui.form-errors />

        <flux:input wire:model="email" type="email" :label="__('Email')" required autocomplete="email" />

        <flux:select wire:model="roleId" :label="__('Role')">
            @foreach ($this->roles as $role)
                <flux:select.option wire:key="role-{{ $role->id }}" :value="$role->id">{{ $role->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:button variant="primary" type="submit" class="min-h-11">
            {{ __('Send invitation') }}
        </flux:button>
    </form>

    <div class="space-y-4">
        <flux:heading size="lg">{{ __('Pending invitations') }}</flux:heading>

        @forelse ($this->pendingInvitations as $invitation)
            <div wire:key="invitation-{{ $invitation->id }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ $invitation->email }}</flux:heading>
                <flux:text>
                    {{ $invitation->role?->name }} · {{ __('Expires :date', ['date' => $invitation->expires_at->toFormattedDateString()]) }}
                </flux:text>
            </div>
        @empty
            <x-ui.empty-state :heading="__('No pending invitations')" :text="__('Invite someone with their email address.')" />
        @endforelse
    </div>
</div>
