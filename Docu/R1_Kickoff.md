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

Vorhanden: R0 komplett (Tenant-Session [ADR-0003](architecture/adr/0003-organization-context-session.md), `BelongsToOrganization`, RBAC [ADR-0004](architecture/adr/0004-custom-rbac.md), Audit [ADR-0005](architecture/adr/0005-immutable-audit-log.md), Fortify, PWA, Flux-Shell). `organizations` (UUID, `name`/`slug`, `type`, `enabled_modules`). Org-Settings. User-Settings (Profil/Sicherheit/Erscheinung). Nav-Items hinter Gates und Modul-Flags. Placeholder `/members` `/finance` `/stable`. Storage-Disks `local`/`s3` in `config/filesystems.php`.

Fehlt: `Person`, `Task`, Recurrence, `Document`.

## Empfohlene Reihenfolge

Abweichungen vom Backlog sind Absicht: Stufe-A-P0 zuerst; CORE-006/007 bewusst später (brauchen Fachobjekte bzw. echten Versandbedarf). CORE-004 bleibt in R1, obwohl Stufe B es nennt — Recurrence jetzt ist kleiner als nachträglich.

| # | Ticket | Art | Warum jetzt |
|---|--------|-----|-------------|
| 1 | **CORE-001** | done | Org-Typ + Feature Flags. Blockt Navigation und jedes Fachmodul. |
| 2 | **CORE-002** | open | Personenstamm. Blockt Aufgaben, Dokumente, später CLUB/EQ. |
| 3 | **CORE-003** | open | Aufgaben. Nach CORE-002 unabhängig von Dokumenten. |
| 4 | **CORE-005** | open | Storage/Dokumente. Nach CORE-002 unabhängig von Aufgaben. Parallel zu CORE-003 erlaubt. |
| 5 | **CORE-004** | open | Recurrence. Erst wenn Tasks stehen. |
| 6 | **CORE-006** | skipped | Dokumentverknüpfungen brauchen Pferde/Sitzungen/Verträge. Erst mit R2/R3. |
| 7 | **CORE-007** | skipped | Inbox/Mail erst, wenn Aufgaben und echte Empfänger produktiv genutzt werden. |

R2 (`CLUB-001`) erst, wenn die Tabelle unten durchgängig `done` oder bewusst `skipped` ist.

## Gap-Analyse

| Ticket | Status | Schon da | Noch zu tun |
|--------|--------|----------|-------------|
| CORE-001 | **done** | `OrganizationType` + `enabled_modules` JSON auf `organizations` ([ADR-0006](architecture/adr/0006-organization-type-and-module-flags.md)); Admin-UI `/settings/organization`; Nav/Routen folgen Flags (`module:club`/`module:stable`); Änderungen nur aktiver Mandant | — |
| CORE-002 | **open** | `User` (Login, Integer-PK); UUID-Konvention [ADR-0001](architecture/adr/0001-uuid-primary-keys.md); `BelongsToOrganization` | `Person`-CRUD (Kontaktfelder, Archiv, Suche/Filter); optionale Bindung an `User`; eine Person, mehrere spätere Profile (Member/Customer); Tenant-Isolation + Cross-Tenant-Test |
| CORE-003 | **open** | Shell, Actions-Konvention, Empty/Error/Loading | `Task`-CRUD; assignee, due date, priority/status; Filter; mobile Quick-Create (max. 3 Schritte); generische Objekt-Links ohne Fachmodelle vorwegzunehmen |
| CORE-004 | **open** | `routes/console.php` (Scheduler-Einstieg) | Recurrence-Subset (monatlich/jährlich); Job mit Idempotenzschlüssel; next occurrence; pause/end; kein Doppel-Erzeugen bei erneutem Lauf |
| CORE-005 | **open** | `config/filesystems.php` (`local` + `s3`); DB speichert keine Binärdateien (Leitplanke) | `Document`/`DocumentVersion`; zufällige Keys; MIME-/Größen-Whitelist; Signed Temporary URLs; Upload/Download/Delete; Cross-Tenant → 403 |
| CORE-006 | **skipped** | — | Bewusst nicht: polymorphe DocumentLinks und Kategorien, bis R2/R3 Objekte existieren. |
| CORE-007 | **skipped** | Fortify-Mail (`MAIL_MAILER=log`); Queue-Infrastruktur im Stack | Bewusst nicht: Notification-Inbox und Mail-Adapter. Nachziehen, wenn CORE-003 produktiv und Versandbedarf da ist. |

## Offene Entscheidungen (in den genannten Tickets treffen, nicht vorher)

| Entscheidung | Ticket | Hinweis |
|--------------|--------|---------|
| Org-Typ speichern | CORE-001 | Erledigt: PHP-Enum-Cast `OrganizationType` auf `organizations.type` ([ADR-0006](architecture/adr/0006-organization-type-and-module-flags.md)). |
| Feature Flags speichern | CORE-001 | Erledigt: JSON `enabled_modules` auf `organizations` (`AsEnumCollection` von `ModuleName`). |
| Deaktiviertes Modul „API“ | CORE-001 | Erledigt: Middleware `module:club`/`module:stable` liefert 403; Nav blendet nur aus. |
| Person vs. User | CORE-002 | `User` bleibt Login-Identität (Integer). `Person` ist Fachaggregat (UUID). Verknüpfung optional, keine Pflicht-1:1. |
| Archivierung | CORE-002 | `archived_at` vs. SoftDeletes. Standardliste blendet archivierte aus. |
| Task-Verknüpfungen | CORE-003 | Noch keine Pferde/Sitzungen. Nur generischen, sicheren Link-Ansatz vorbereiten — keine Fake-Fachmodelle. |
| Recurrence-Format | CORE-004 | Eigenes Regel-Subset (monatlich/jährlich) statt voller iCal-RRULE, solange der Bedarf so klein ist. |
| Document-Disk | CORE-005 | Default `local` in Dev/Tests, `s3` über Env in Cloud. Kein Public-Pfad, keine ausführbaren Downloads. |

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
