---
paths:
  - 'app/Models/*.php'
---

# Models

## Tenant scope and policies on domain models
Every tenant-owned model must have organization_id NOT NULL and a BelongsToOrganization global scope (SEC-003). Cross-tenant IDs return 404 with no data leak. Do not query tenant data without the scope. Sensitive writes go through a Policy or Gate (deny by default).

## UUID PKs for domain aggregates, integer users
Domain aggregates (Organization, later people/horses/contracts) use UUID primary keys via HasUuids (UUIDv7). The users table stays integer auto-increment. Join tables such as organization_memberships use an integer PK, a UUID organization_id FK, and an integer user_id FK. See Docu/architecture/adr/0001-uuid-primary-keys.md.

## MustVerifyEmail is required on User
User implements MustVerifyEmail (ADR-0002). Keep Fortify Features::emailVerification enabled. Do not drop the interface without a new ADR; otherwise verified middleware is a no-op and unverified users reach the dashboard.

## Invitation tokens are hashed single-use
Invitation is a UUID domain aggregate with organization_id NOT NULL, hashed token, expires_at, and used_at. Issue via IssueInvitationToken; consume() once. Do not store plaintext tokens. BelongsToOrganization global scope comes in SEC-003.
