# Ticket Definition of Done

Ein Ticket ist fertig, wenn alle Punkte zutreffen.

1. **Scope:** Nur das Ticket. Keine sachfremden Refactorings, keine Extra-Features.
2. **Akzeptanzkriterien** aus dem Backlog (Abschnitt 5) sind erfüllt.
3. **Tests:** Pflichttests des Tickets existieren und sind grün. `php artisan test --compact` für den betroffenen Bereich.
4. **Qualität:** `vendor/bin/pint --dirty` und `composer types:check` grün. Vor Abschluss `composer ci:check`, wenn PHP oder CI betroffen ist.
5. **Sicherheit:** [security-checklist.md](security-checklist.md) für das Ticket durchgegangen. Tenant-Daten: Global Scope + Test. Sensible Aktionen: Policy/Gate.
6. **Schema:** DB-Änderungen nur als Migration plus Factory.
7. **Doku:** Kickoff-Status auf `done`. ADR nur wenn eine Architekturentscheidung neu ist. `.ai/rules` per `record-rule`, wenn die Entscheidung dauerhaft gilt.
8. **Commit:** Eine Ticket-ID, siehe [git.md](git.md). Kein Secret im Diff.
9. **Kein Produktions-Deploy.** Staging nur nach grünem CI und menschlicher Freigabe.

Agents liefern am Ende: (1) Kurzfassung, (2) geänderte Dateien, (3) Migrationen, (4) Tests mit Ergebnis, (5) offene Risiken.
