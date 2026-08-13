<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Http\Middleware\EnsureActiveOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * @implements Scope<Model>
 */
class BelongsToOrganizationScope implements Scope
{
    /**
     * @param  Builder<covariant Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $organizationId = EnsureActiveOrganization::id();

        if ($organizationId === null) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->where($model->qualifyColumn('organization_id'), $organizationId);
    }
}
