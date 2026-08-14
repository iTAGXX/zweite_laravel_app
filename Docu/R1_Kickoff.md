# R1 Kickoff – Gemeinsamer Kern

> Kurze Ausführungsliste. **Kein zweites Backlog.** Ticket-Details, Akzeptanzkriterien und Cursor-Prompts stehen in [`Entwicklungsbacklog_EquiFlow_v2_0_Laravel.md`](Entwicklungsbacklog_EquiFlow_v2_0_Laravel.md) Abschnitt 4 und 5.

Stand: 13. August 2026 · Quelle: R0-Ist vs. CORE-Tickets · R0: [`R0_Kickoff.md`](R0_Kickoff.md)

## Zweck

R1 schließt den Plattformkern, bevor R2 (Verein) oder R3 (Pferdebetrieb) beginnen. Ohne Org-Typ, Feature Flags, Personenstamm, Aufgaben und Dokumentablage sind Fachmodule unsicher.

## Arbeitsregel

1. **Ein Ticket = ein Agent-Chat = ein Commit.** Prompt aus Abschnitt 5 des Backlogs kopieren.
2. Den **gesamten Backlog nicht** in den Chat legen. Reicht: Ticket-ID, Prompt, diese Datei, `AGENTS.md`/`CLAUDE.md`, betroffene Dateien.
3. R0-Code **nicht neu bauen.** Tenant, RBAC, Audit, Fortify, Shell und CI stehen. Nur den Ticket-Delta schließen.
4. Kein sachfremdes Refactoring, kein Produktions-Deploy.
5. Nach jedem Ticket: Tests, Review, Commit, Status unten auf `done` setzen.
6. **Kein `/multitask` über ganz R1.** CORE-001 und CORE-002 sequentiell. CORE-003 und CORE-005 dürfen danach in getrennten Chats parallel laufen (eigene Commits, eigene Reviews).

## Ist-Stand (Repo)

Vorhanden: R0 komplett (Tenant-Session [ADR-0003](architecture/adr/0003-organization-context-session.md), `BelongsToOrganization`, RBAC [ADR-0004](architecture/adr/0004-custom-rbac.md), Audit [ADR-0005](architecture/adr/0005-immutable-audit-log.md), Fortify, PWA, Flux-Shell). `organizations` (UUID, `name`/`slug`, `type`, `enabled_modules`). Org-Settings. User-Settings (Profil/Sicherheit/Erscheinung). Nav-Items hinter Gates und Modul-Flags. Placeholder `/members` `/finance` `/stable`. Storage-Disks `local`/`s3` in `config/filesystems.php`. `people` (UUID, Kontaktfelder, optionales `user_id`, `archived_at`, [ADR-0007](architecture/adr/0007-person-aggregate-and-archive.md)). `tasks` (UUID, Assignee=`Person`, Status/Priorität, `task_links` Allowlist, [ADR-0009](architecture/adr/0009-task-aggregate-and-allowlisted-links.md)). `task_recurrences` (monatlich/jährlich, Idempotenz `occurrence_key`, Pause/Ende, [ADR-0010](architecture/adr/0010-recurrence-subset.md)). `documents`/`document_versions` (zufällige Keys, MIME-Whitelist, Signed Downloads, [ADR-0008](architecture/adr/0008-document-storage.md)).

## Empfohlene Reihenfolge

Abweichungen vom Backlog sind Absicht: Stufe-A-P0 zuerst; CORE-006/007 bewusst später (brauchen Fachobjekte bzw. echten Versandbedarf). CORE-004 bleibt in R1, obwohl Stufe B es nennt — Recurrence jetzt ist kleiner als nachträglich.

| # | Ticket | Art | Warum jetzt |
|---|--------|-----|-------------|
| 1 | **CORE-001** | done | Org-Typ + Feature Flags. Blockt Navigation und jedes Fachmodul. |
| 2 | **CORE-002** | done | Personenstamm. Blockt Aufgaben, Dokumente, später CLUB/EQ. |
| 3 | **CORE-003** | done | Aufgaben. Nach CORE-002 unabhängig von Dokumenten. |
| 4 | **CORE-005** | done | Storage/Dokumente. Nach CORE-002 unabhängig von Aufgaben. Parallel zu CORE-003 erlaubt. |
| 5 | **CORE-004** | done | Recurrence. Erst wenn Tasks stehen. |
| 6 | **CORE-006** | skipped | Dokumentverknüpfungen brauchen Pferde/Sitzungen/Verträge. Erst mit R2/R3. |
| 7 | **CORE-007** | skipped | Inbox/Mail erst, wenn Aufgaben und echte Empfänger produktiv genutzt werden. |

R2 (`CLUB-001`) erst, wenn die Tabelle unten durchgängig `done` oder bewusst `skipped` ist, und der R0-Nachzug ([`R0_Nachzug_Kickoff.md`](R0_Nachzug_Kickoff.md), SEC-008–011) erledigt ist.

## Gap-Analyse

| Ticket | Status | Schon da | Noch zu tun |
|--------|--------|----------|-------------|
| CORE-001 | **done** | `OrganizationType` + `enabled_modules` JSON auf `organizations` ([ADR-0006](architecture/adr/0006-organization-type-and-module-flags.md)); Admin-UI `/settings/organization`; Nav/Routen folgen Flags (`module:club`/`module:stable`); Änderungen nur aktiver Mandant | — |
| CORE-002 | **done** | `Person` UUID-Fachaggregat, `BelongsToOrganization`, Kontaktfelder, optionales `user_id`, `archived_at` (kein SoftDeletes) ([ADR-0007](architecture/adr/0007-person-aggregate-and-archive.md)); CRUD `/people` hinter `people.manage`; Standardliste ohne Archivierte; Cross-Tenant 404 | — |
| CORE-003 | **done** | `Task` UUID-Fachaggregat, `BelongsToOrganization`, Assignee=`Person`, Status/Priorität-Enums, `task_links` mit Allowlist (`person` zuerst) ([ADR-0009](architecture/adr/0009-task-aggregate-and-allowlisted-links.md)); CRUD `/tasks` hinter `tasks.manage`; Quick-Create in 3 Schritten; Filter Status/Verantwortlicher; Cross-Tenant 404 | — |
| CORE-004 | **done** | `TaskRecurrence` UUID, `BelongsToOrganization`; Frequenz-Enum `monthly`/`yearly` (kein RRULE); Job `GenerateDueRecurringTasks` täglich mit `occurrence_key`-Idempotenz, `next_occurrence_on`, Pause/`ends_on`; Cross-Tenant 404; UI `/tasks/recurrences` hinter `tasks.manage` ([ADR-0010](architecture/adr/0010-recurrence-subset.md)) | — |
| CORE-005 | **done** | `Document`/`DocumentVersion` UUID, `BelongsToOrganization`; Disk `local`/`s3` via `DOCUMENT_DISK`; zufällige Keys; MIME-/Größen-Whitelist; Signed Temporary URLs; Upload/Download/Delete hinter `documents.manage`; Cross-Tenant-Download 403 ([ADR-0008](architecture/adr/0008-document-storage.md)) | — |
| CORE-006 | **skipped** | — | Bewusst nicht: polymorphe DocumentLinks und Kategorien, bis R2/R3 Objekte existieren. |
| CORE-007 | **skipped** | Fortify-Mail (`MAIL_MAILER=log`); Queue-Infrastruktur im Stack | Bewusst nicht: Notification-Inbox und Mail-Adapter. Nachziehen, wenn CORE-003 produktiv und Versandbedarf da ist. |

## Offene Entscheidungen (in den genannten Tickets treffen, nicht vorher)

| Entscheidung | Ticket | Hinweis |
|--------------|--------|---------|
| Org-Typ speichern | CORE-001 | Erledigt: PHP-Enum-Cast `OrganizationType` auf `organizations.type` ([ADR-0006](architecture/adr/0006-organization-type-and-module-flags.md)). |
| Feature Flags speichern | CORE-001 | Erledigt: JSON `enabled_modules` auf `organizations` (`AsEnumCollection` von `ModuleName`). |
| Deaktiviertes Modul „API“ | CORE-001 | Erledigt: Middleware `module:club`/`module:stable` liefert 403; Nav blendet nur aus. |
| Person vs. User | CORE-002 | Erledigt: `User` bleibt Login (Integer). `Person` ist Fachaggregat (UUID), optionales `user_id`, keine Pflicht-1:1 ([ADR-0007](architecture/adr/0007-person-aggregate-and-archive.md)). |
| Archivierung | CORE-002 | Erledigt: `archived_at` statt SoftDeletes. Standardliste blendet archivierte aus. |
| Task-Verknüpfungen | CORE-003 | Erledigt: `task_links` mit Enum-Allowlist (`TaskLinkableType::Person`); keine Fake-Fachmodelle ([ADR-0009](architecture/adr/0009-task-aggregate-and-allowlisted-links.md)). |
| Recurrence-Format | CORE-004 | Erledigt: Eigenes Regel-Subset (monatlich/jährlich) statt voller iCal-RRULE ([ADR-0010](architecture/adr/0010-recurrence-subset.md)). |
| Document-Disk | CORE-005 | Erledigt: Default `local` in Dev/Tests, `s3` über `DOCUMENT_DISK` in Cloud. Kein Public-Pfad, Downloads immer `attachment` ([ADR-0008](architecture/adr/0008-document-storage.md)). |

Nicht in R1 entscheiden: Payment-Provider, Newsletter, polymorphe DocumentLinks (CORE-006), Health Scores, konkrete Fremdsoftware-Connectoren.

## Ticket starten

Neuen Agent-Chat öffnen und den Prompt des Tickets aus dem Backlog (Abschnitt 5) einfügen. Zusätzlich:

```text
Lies zuerst Docu/R1_Kickoff.md (Gap-Zeile dieses Tickets) und AGENTS.md.
R0 ist fertig. Tenant, RBAC, Audit, Fortify, Shell und CI nicht neu erfinden — nur den CORE-Delta schließen.
```

Nach Abschluss in dieser Datei Status auf `done` setzen und in 1–2 Zeilen notieren, was gebaut wurde.

## R1 Done

R1 ist fertig, wenn:

- CORE-001, CORE-002, CORE-003, CORE-004, CORE-005 `done` sind
- CORE-006 und CORE-007 bewusst `skipped` bleiben (oder später nachgezogen und dann `done`)
- Feature-Flag-Tests (CORE-001), Cross-Tenant-Tests (CORE-002, CORE-005) und Recurrence-Idempotenz (CORE-004) grün sind
- `composer ci:check` grün ist
- kein Produktions-Deploy ohne menschliche Freigabe erfolgte
