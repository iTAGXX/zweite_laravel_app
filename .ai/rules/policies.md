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
