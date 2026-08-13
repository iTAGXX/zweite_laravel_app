---
paths:
  - 'routes/**'
---

# Routes

## Livewire-first, no general REST API
Livewire-first. Do not add a general REST API in the MVP. Only dedicated controllers for webhooks, health, or an explicit public endpoint named in a ticket. Keep Fortify as the only auth implementation.

## Use tenant middleware group on tenant routes
Assign the tenant middleware group (EnsureActiveOrganization) to mandantenbezogene web routes after auth. It stores active_organization_id in the session and shows errors/no-organization with HTTP 403 when the user has no membership. Do not add BelongsToOrganization here; that is SEC-003. Session vs URL for org context is also SEC-003.
