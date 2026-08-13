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
Invitation is a UUID domain aggregate with organization_id NOT NULL, hashed token, expires_at, and used_at. Issue via IssueInvitationToken; consume() once. Do not store plaintext tokens. Invitation uses BelongsToOrganization.

## BelongsToOrganization is required on tenant models
Tenant-owned models (not Organization, User, or join tables like organization_memberships) must use the BelongsToOrganization trait. Missing context yields no rows. Cross-tenant IDs are 404, never 200 with a different error.

## Permissions come from membership role
RBAC is a global role catalog (admin, treasurer, member, staff) plus permission slugs. Membership.role_id in the active organization is the source of rights. Role/Permission are not tenant-scoped. User::hasPermission() is deny-by-default when context or role is missing.

## Invitation accept bypasses tenant scope
Accepting an invitation looks up Invitation::findForAcceptance() without the tenant global scope (guest/other-org). Do not use implicit Invitation binding on the accept route. Tokens stay hashed; the plaintext token is only in the mail URL.

## AuditLog is immutable and allowlisted
AuditLog is a UUID tenant model with BelongsToOrganization. organization_id is nullable for auth events without org context; the admin UI still only sees the active org. subject_id is a string (UUID invitations and integer users). No updated_at; updating/deleting return false. Never observe AuditLog itself. Writes go through AuditLogger, not HTTP.

## Org type enum and JSON module flags
Organization stores type as OrganizationType enum and enabled_modules as JSON (AsEnumCollection of ModuleName: club, stable). Mixed default is both modules. Do not add a feature_flags table; defaults live on OrganizationType::defaultModules(). hasModule() is the only check for whether a Fachmodul is on.

## Person is shared identity with archived_at
Person is a UUID tenant aggregate with BelongsToOrganization, optional user_id (unique per org, not required 1:1), and archived_at instead of SoftDeletes. Do not add a type/role column; Member/Customer attach later via person_id. Default lists use notArchived/archivedStatus; archived rows stay reachable for edit/unarchive. Cross-tenant IDs are 404.
