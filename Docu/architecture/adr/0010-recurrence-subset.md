# ADR-0010: Recurrence subset instead of iCal RRULE

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** CORE-004
- **Supersedes:** —

## Kontext

EquiFlow braucht zuverlässige monatliche und jährliche Vereins- und Betriebsaufgaben (CORE-004). Ein voller iCal-RRULE-Parser wäre für den aktuellen Bedarf zu groß. Kickoff: eigenes Regel-Subset, solange der Bedarf so klein ist. Erzeugte Aufgaben dürfen bei erneutem Joblauf nicht doppelt entstehen. Der Scheduler läuft ohne HTTP-Session, muss aber Tenant-Isolation trotzdem einhalten.

## Entscheidung

- `TaskRecurrence` ist ein UUID-Fachaggregat mit `organization_id` NOT NULL und `BelongsToOrganization`.
- Frequenz ist ein PHP-Enum-Subset: `monthly` / `yearly`. Kein RRULE, kein wöchentliches Intervall.
- `day_of_month` (1–31) gilt für beide Frequenzen; `month` (1–12) nur bei `yearly`. Tage, die im Zielmonat nicht existieren, werden auf den letzten Monatstag geklemmt.
- `next_occurrence_on` ist die nächste fällige Erzeugung. `paused_at` pausiert, `ends_on` beendet (letzte erlaubte Fälligkeit inklusive).
- Der Job `GenerateDueRecurringTasks` (täglich, `withoutOverlapping`, `ShouldBeUnique`) setzt pro Regel `Context` auf `organization_id` und erzeugt `Task`-Zeilen. Die Quersuche nutzt `withoutGlobalScope` nur in `TaskRecurrence::queryDueAcrossOrganizations()`.
- Idempotenz: `tasks.occurrence_key` = `{recurrence_id}:{Y-m-d}` mit Unique-Index `(organization_id, occurrence_key)`. Ein zweiter Lauf erzeugt keine zweite Aufgabe.
- Jobfehler je Regel werden geloggt (`recurrence_id`, `organization_id`, Message). Eine kaputte Regel stoppt die übrigen nicht.
- Rechte: `tasks.manage` über `TaskRecurrencePolicy` (create/pause/resume/end).

## Konsequenzen

- Positiv: Kleines, testbares Subset; Doppel-Erzeugen ist ein DB-Constraint, nicht nur ein Job-Lock. Tenant-Scope bleibt für HTTP und für das Erzeugen der Tasks.
- Negativ / Follow-up: Wöchentliche Regeln, RRULE und CLUB-009-Vorlagen kommen später. Der Job muss `Context` setzen, weil `BelongsToOrganization` sonst 0 Zeilen sieht.

## Alternativen

1. Voller iCal-RRULE — unverhältnismäßig für monatlich/jährlich.
2. Recurrence-Spalten direkt auf `tasks` — vermischt Vorlage und Instanz; Pause/Ende und Idempotenz werden unklar.
3. Nur Job-Uniqueness ohne `occurrence_key` — schützt nicht gegen einen zweiten Lauf nach Abschluss des ersten.
