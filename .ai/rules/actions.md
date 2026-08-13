---
paths:
  - 'app/Actions/**/*.php'
---

# Actions

## Actions use handle except Fortify contracts
New application Actions use a single handle() method. Fortify actions under app/Actions/Fortify keep their contract methods (create, reset, …) and must not be rewritten to handle().

## AuditLogger strips non-allowlist metadata
AuditLogger::handle() is the only writer for audit_logs. Metadata keeps an allowlist (email, role_id, role_slug, user_id, ip, name, slug, from_role_id, to_role_id) and drops keys containing password, token, secret, iban, recovery, or two_factor. Never persist passwords, full IBANs, tokens, or secrets.

## Recurring task generation is idempotent and logged
GenerateRecurringTasks is a system Action (no User/Gate). It walks due TaskRecurrence rows across tenants, sets Context to organization_id, creates Task rows, and advances next_occurrence_on. UniqueConstraintViolationException on occurrence_key is ignored (idempotent replay). Per-rule Throwable is logged with recurrence_id and organization_id; other rules continue. Pause/End/Resume/Create still authorize via TaskRecurrencePolicy.
