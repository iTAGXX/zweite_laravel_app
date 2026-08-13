# ADR-0005: Unveränderbares Audit-Log mit Metadata-Allowlist

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** SEC-006
- **Supersedes:** —

## Kontext

Sensible Aktionen (Login, Einladungen, Rollenwechsel) müssen nachvollziehbar sein, ohne Passwörter, Tokens, IBAN-Vollwerte oder Secrets zu speichern. Ein allgemeines Änderungsprotokoll über alle Attribute wäre zu riskant. Eloquent-`updated_at` auf dem Log selbst würde Immutability untergraben.

## Entscheidung

- `AuditLog` ist ein UUID-Aggregat mit `BelongsToOrganization`. `organization_id` ist **nullable** (Login ohne Org-Kontext); die Admin-Ansicht sieht nur die aktive Organisation.
- `subject_id` ist ein String (UUID-Einladungen und Integer-User), kein Integer-Morph.
- Kein `updated_at`. `updating`/`deleting` am Modell geben `false` zurück. Policy: `view`/`viewAny` nur mit Permission `audit.view` (Admin); `create`/`update`/`delete` immer false.
- Schreiben nur über `AuditLogger` (Allowlist + Key-Fragment-Redaction). Observer `AuditsModelWrites` auf `Invitation` und `OrganizationMembership`; Login über Listener auf `Illuminate\Auth\Events\Login`. `AuditLog` selbst wird nicht beobachtet. Kein `ShouldHandleEventsAfterCommit` (RefreshDatabase würde Writes in Tests verschlucken).

## Konsequenzen

- Positiv: keine Secrets in Metadata; Staff sieht `/audit` nicht (403); Tenant-Isolation wie bei anderen Fachentitäten.
- Negativ / Follow-up: Query-Builder/`DB::table` umgeht Model-Hooks. DB-Trigger oder Append-only-Rechte sind später (SEC-008/DSGVO) möglich. Login ohne Membership hat `organization_id` null und erscheint nicht in der Org-Ansicht.

## Alternativen

1. Spatie Activitylog — Extra-Dependency, Allowlist müsste trotzdem selbst gebaut werden.
2. Integer-Morphs für Subject — bricht bei UUID-Subjects.
3. Alle Model-Attribute loggen und hinterher schwärzen — leicht, Tokens/IBANs zu übersehen.
