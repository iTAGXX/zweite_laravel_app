<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ModuleName;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $moduleName = ModuleName::tryFrom($module);

        abort_if($moduleName === null, 403);

        $organization = EnsureActiveOrganization::organization();

        abort_if($organization === null || ! $organization->hasModule($moduleName), 403);

        return $next($request);
    }
}
