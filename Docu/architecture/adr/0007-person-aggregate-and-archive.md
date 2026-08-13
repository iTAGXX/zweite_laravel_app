# ADR-0007: Person als Fachaggregat, Archiv über archived_at

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** CORE-002
- **Supersedes:** —

## Kontext

EquiFlow braucht einen mandantenbezogenen Personenstamm, bevor Mitglieder, Kunden, Aufgaben und Dokumente darauf zeigen. `User` ist die Login-Identität (Integer-PK, Fortify). Kickoff: Verknüpfung optional, keine Pflicht-1:1; Standardliste blendet Archivierte aus; SoftDeletes vs. `archived_at` war offen.

## Entscheidung

- `Person` ist ein UUID-Fachaggregat (`HasUuids`) mit `organization_id` NOT NULL und `BelongsToOrganization`.
- `user_id` ist optional (Integer-FK auf `users`, unique je Organisation, `nullOnDelete`). Member-/Customer-Profile kommen später als eigene Tabellen mit `person_id`, nicht als Typ-Spalte auf `people`.
- Archivierung nutzt `archived_at`, nicht Eloquent SoftDeletes. Die Standardliste filtert `whereNull('archived_at')`; Archivierte bleiben per Route/Edit erreichbar. Hard-Delete bleibt aus (DSGVO später).
- Rechte: Permission `people.manage` (Admin). Policy `PersonPolicy`; `delete`/`restore`/`forceDelete` sind false, Archiv über `archive`/`unarchive`.

## Konsequenzen

- Positiv: eine Person kann später mehrere Profile tragen ohne Dublette; Login bleibt vom Stamm getrennt; Archivierte verschwinden nicht aus der Datenbank.
- Negativ / Follow-up: Staff hat `people.manage` noch nicht (Fachmodule können das erweitern). Kein Audit-Observer auf Person in diesem Ticket.

## Alternativen

1. SoftDeletes — blendet Archivierte aus jedem Query inkl. Route-Binding aus, bis `withTrashed()`; Archiv ist aber ein Fachzustand, kein Delete.
2. Pflicht-1:1 Person↔User — schließt Personen ohne Login aus (Eltern, Einsteller ohne Konto).
3. `type`-Spalte auf `people` — würde Member/Customer als exklusive Rolle festnageln und Dubletten begünstigen.
