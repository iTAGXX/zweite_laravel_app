---
paths:
  - 'app/Policies/*.php'
---

# Policies

## Policies and gates deny by default
Authorize with policies or named Gates matching PermissionName values (users.manage, finance.view). Deny by default. Missing permission is HTTP 403 with no data leak. @can in Blade is UX only; every sensitive route still uses can middleware or Gate::authorize.

## Audit logs are read-only even for admins
AuditLogPolicy: view/viewAny require audit.view (admin). create/update/delete/restore/forceDelete are always false, including for admins. Writes exist only through AuditLogger.

## Org settings only for active tenant
OrganizationPolicy update/view allow only users.manage and only when the target Organization is the active session org (EnsureActiveOrganization::id()). Never accept another tenant's organization id for settings writes.

## PersonPolicy archives instead of deleting
PersonPolicy uses people.manage for viewAny/view/create/update/archive/unarchive. delete, restore, and forceDelete stay false; archive via archived_at, no hard delete in CORE-002.

## DocumentPolicy uses documents.manage
DocumentPolicy uses documents.manage for viewAny/view/create/update/delete. restore and forceDelete stay false. Cross-tenant file download is 403 (signed route + policy), not 404.

## TaskPolicy uses tasks.manage
TaskPolicy uses tasks.manage for viewAny/view/create/update/complete/assign/delete. restore and forceDelete stay false. Staff does not have tasks.manage yet.

## TaskRecurrencePolicy uses tasks.manage
TaskRecurrencePolicy uses tasks.manage for viewAny/view/create/update/pause/resume/end. delete, restore, and forceDelete stay false; stop a series with pause or end, not a hard delete.
