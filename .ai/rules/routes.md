---
paths:
  - 'routes/**'
  - routes/web.php
---

# Routes

## Livewire-first, no general REST API
Livewire-first. Do not add a general REST API in the MVP. Only dedicated controllers for webhooks, health, or an explicit public endpoint named in a ticket. Keep Fortify as the only auth implementation.

## Use tenant middleware group on tenant routes
Assign the tenant middleware group (EnsureActiveOrganization) to mandantenbezogene web routes after auth. It stores active_organization_id in the session and shows errors/no-organization with HTTP 403 when the user has no membership. Do not add BelongsToOrganization here; that is SEC-003. Session vs URL for org context is also SEC-003.

## UI kit route only in local and testing
`/dev/ui` (`dev.ui`) shows Flux building blocks and is registered only when APP_ENV is local or testing. Do not expose it in staging or production.

## PWA manifest is a public JSON route
Serve the web app manifest at /manifest.webmanifest (pwa.manifest) as a public JSON route. Keep it outside auth/tenant so browsers can install without a session.
