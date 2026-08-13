<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\OrganizationSettingsValidationRules;
use App\Enums\ModuleName;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UpdateOrganizationSettings
{
    use OrganizationSettingsValidationRules;

    /**
     * @param  list<ModuleName>  $enabledModules
     */
    public function handle(
        User $actor,
        Organization $organization,
        OrganizationType $type,
        array $enabledModules,
    ): Organization {
        Gate::forUser($actor)->authorize('update', $organization);

        $organization->update([
            'type' => $type,
            'enabled_modules' => $enabledModules,
        ]);

        return $organization->refresh();
    }
}
