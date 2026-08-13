<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\ModuleName;
use App\Enums\OrganizationType;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule as ValidationRuleFactory;

trait OrganizationSettingsValidationRules
{
    /**
     * @return array<string, array<int, Rule|ValidationRule|array<mixed>|string>>
     */
    protected function organizationSettingsRules(): array
    {
        return [
            'type' => ['required', ValidationRuleFactory::enum(OrganizationType::class)],
            'enabledModules' => ['present', 'array'],
            'enabledModules.*' => [ValidationRuleFactory::enum(ModuleName::class)],
        ];
    }
}
