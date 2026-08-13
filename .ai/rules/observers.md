---
paths:
  - 'app/Observers/**/*.php'
---

# Observers

## Model write observer must not audit AuditLog
AuditsModelWrites observes Invitation and OrganizationMembership created/updated/deleted and logs via AuditLogger. Membership updates log only when role_id changes (membership.role_changed). Do not observe AuditLog (recursion). Do not use ShouldHandleEventsAfterCommit; RefreshDatabase would hide writes in tests.
