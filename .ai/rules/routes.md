---
paths:
  - 'routes/**'
  - routes/web.php
---

# Routes

## Livewire-first, no general REST API
Livewire-first. Do not add a general REST API in the MVP. Only dedicated controllers for webhooks, health, or an explicit public endpoint named in a ticket. Keep Fortify as the only auth implementation.

## Use tenant middleware group on tenant routes
Assign the tenant middleware group (EnsureActiveOrganization) to mandantenbezogene web routes after auth. It stores active_organization_id in the session and Context (ADR-0003) and shows errors/no-organization with HTTP 403 when the user has no membership. Tenant-owned models use BelongsToOrganization; join tables such as organization_memberships do not. EnsureActiveOrganization must run before SubstituteBindings (priority in bootstrap/app.php) so route model binding is tenant-scoped.

## UI kit route only in local and testing
`/dev/ui` (`dev.ui`) shows Flux building blocks and is registered only when APP_ENV is local or testing. Do not expose it in staging or production.

## PWA manifest is a public JSON route
Serve the web app manifest at /manifest.webmanifest (pwa.manifest) as a public JSON route. Keep it outside auth/tenant so browsers can install without a session.

## Verified middleware on tenant routes
Dashboard and tenant routes stay behind auth + verified. settings/profile stays auth-only so unverified users can change email and see the verification notice. Logout must invalidate the session and regenerate the CSRF token (Fortify and Livewire Logout already do this).

## CSRF stays on for web writes
CSRF stays on via PreventRequestForgery for the web group. Livewire sends the token. Extra AJAX must send X-CSRF-TOKEN or X-XSRF-TOKEN. Webhooks go outside web or use preventRequestForgery(except:) plus a signature check. Login remains Fortify RateLimiter login at 5/min.

## Module routes require EnsureModuleEnabled
Fachmodule (members=club, stable=stable) must use the module middleware alias (module:club / module:stable) in addition to any Gate. Hiding a nav item is UX only; a disabled module route must 403. Finance stays Gate-only (finance.view), not a type module.
