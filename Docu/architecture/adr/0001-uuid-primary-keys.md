# ADR-0001: UUID-Primärschlüssel für Fachentitäten

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** DEV-003
- **Supersedes:** —

## Kontext

EquiFlow braucht stabile IDs für Historie, Export und spätere Integrationen. `users` kommt aus dem Fortify-Starter mit Auto-Increment und ist bereits von Sessions, Passkeys und 2FA referenziert.

## Entscheidung

- **Fachaggregate** (zuerst `organizations`, später Personen, Pferde, Verträge): UUID-Primärschlüssel über `HasUuids` (Laravel erzeugt UUIDv7).
- **`users`:** bleibt Integer-Auto-Increment.
- **Zuordnungstabellen** wie `organization_memberships`: Integer-PK, `organization_id` als UUID-FK, `user_id` als Integer-FK.

## Konsequenzen

- Positiv: keine erratbaren Org-IDs; UUIDv7 bleibt indexierbar; User-Auth unangetastet.
- Negativ / Follow-up: SQLite speichert UUID als String. Global Scope (`BelongsToOrganization`) kommt in SEC-003; `Organization` selbst ist der Mandant und hat kein `organization_id`.

## Alternativen

1. Integer überall — einfach, aber öffentliche IDs sind erratbar und schlechter portabel.
2. Integer-PK plus extra UUID-Spalte — doppelte Schlüssel ohne Nutzen, solange wir keine Legacy-Joins brauchen.
3. User ebenfalls auf UUID umstellen — bricht Fortify-Starter und bestehende FKs; nicht reversibel genug für R0.
