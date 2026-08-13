<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOrganization
{
    public const string SESSION_KEY = 'active_organization_id';

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $sessionOrganizationId = $request->session()->get(self::SESSION_KEY);

        if (is_string($sessionOrganizationId) && $user->memberships()->where('organization_id', $sessionOrganizationId)->exists()) {
            $request->attributes->set('activeOrganizationId', $sessionOrganizationId);

            return $next($request);
        }

        $membership = $user->memberships()->first();

        if ($membership === null) {
            $request->session()->forget(self::SESSION_KEY);

            return response()->view('errors.no-organization', status: 403);
        }

        $request->session()->put(self::SESSION_KEY, $membership->organization_id);
        $request->attributes->set('activeOrganizationId', $membership->organization_id);

        return $next($request);
    }
}
