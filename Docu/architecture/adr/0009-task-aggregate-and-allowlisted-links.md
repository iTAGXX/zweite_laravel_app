# ADR-0009: Task as tenant aggregate with allowlisted links

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** CORE-003
- **Supersedes:** —

## Kontext

EquiFlow braucht allgemeine Aufgaben als Organisationsobjekt, bevor Recurrence (CORE-004) und Fachmodule darauf aufbauen. Kickoff: Verknüpfungen zu Pferden/Sitzungen gibt es noch nicht; nur einen generischen, sicheren Link-Ansatz vorbereiten — keine Fake-Fachmodelle. Assignee, Fälligkeit, Priorität/Status, Filter und mobiles Quick-Create (max. 3 Schritte) sind Pflicht.

## Entscheidung

- `Task` ist ein UUID-Fachaggregat (`HasUuids`) mit `organization_id` NOT NULL und `BelongsToOrganization`.
- Verantwortliche sind `Person` (`assignee_id`), nicht `User`. Ohne Person bleibt die Aufgabe unzugewiesen.
- Status (`open` / `in_progress` / `done`) und Priorität (`low` / `medium` / `high`) sind PHP-Enums. Abschließen setzt `status=done` und `completed_at`.
- Objekt-Links liegen in `task_links` (`organization_id`, `task_id`, `linkable_type`, `linkable_id`). `linkable_type` ist ein geschlossenes Enum (`TaskLinkableType`); der erste erlaubte Typ ist `person`. Kein offenes Morph, keine erfundenen Fachmodelle. Neue Typen erst, wenn das Aggregat existiert.
- Rechte: Permission `tasks.manage` (Admin). Policy `TaskPolicy` für view/create/update/complete/assign/delete. Staff hat `tasks.manage` noch nicht.
- Quick-Create: Modal auf der Liste (Titel + optionales Fälligkeitsdatum). Drei Schritte: öffnen, Titel, speichern.

## Konsequenzen

- Positiv: Aufgaben sind mandantenisoliert; Delegation zeigt auf den Personenstamm; Links sind erweiterbar, ohne R2/R3-Objekte vorwegzunehmen.
- Negativ / Follow-up: Recurrence (CORE-004) kommt als eigenes Ticket. Staff-Rechte können Fachmodule später erweitern. Pest-Browser-Plugin ist nicht im Stack; Quick-Create wird als Livewire-Dreischritt getestet.

## Alternativen

1. Offenes `morphTo` ohne Allowlist — unsicher, sobald beliebige Klassen gespeichert werden können.
2. Assignee als `user_id` — schließt Personen ohne Login aus (wie CORE-002).
3. Fake-Modelle (Pferd, Sitzung) nur für Links — verstößt gegen den Kickoff und erzeugt toten Code.
