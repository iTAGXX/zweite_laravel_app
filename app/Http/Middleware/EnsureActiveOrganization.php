<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveOrganization
{
    public const string SESSION_KEY = 'active_organization_id';

    public static function id(): ?string
    {
        $fromContext = Context::get(self::SESSION_KEY);

        if (is_string($fromContext) && $fromContext !== '') {
            return $fromContext;
        }

        $fromSession = session()->get(self::SESSION_KEY);

        return is_string($fromSession) && $fromSession !== '' ? $fromSession : null;
    }

    public static function set(string $organizationId): void
    {
        Context::add(self::SESSION_KEY, $organizationId);
        session()->put(self::SESSION_KEY, $organizationId);
    }

    public static function forget(): void
    {
        Context::forget(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);
    }

    public static function organization(): ?Organization
    {
        $organizationId = self::id();

        if ($organizationId === null) {
            return null;
        }

        return Organization::query()->find($organizationId);
    }

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
            self::set($sessionOrganizationId);
            $request->attributes->set('activeOrganizationId', $sessionOrganizationId);

            return $next($request);
        }

        $membership = $user->memberships()->first();

        if ($membership === null) {
            self::forget();

            return response()->view('errors.no-organization', status: 403);
        }

        self::set($membership->organization_id);
        $request->attributes->set('activeOrganizationId', $membership->organization_id);

        return $next($request);
    }
}
