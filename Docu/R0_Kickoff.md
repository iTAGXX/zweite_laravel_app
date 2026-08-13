# R0 Kickoff – Fundament & Sicherheit

> Kurze Ausführungsliste. **Kein zweites Backlog.** Ticket-Details, Akzeptanzkriterien und Cursor-Prompts stehen in [`Entwicklungsbacklog_EquiFlow_v2_0_Laravel.md`](Entwicklungsbacklog_EquiFlow_v2_0_Laravel.md) Abschnitt 4 und 5.

Stand: 13. August 2026 · Quelle: Livewire-Starter-Kit vs. R0-Tickets

## Zweck

R0 abschließen, bevor R1 (Plattformkern) oder Fachmodule (Verein, Pferdebetrieb) beginnen. Ohne Tenant, RBAC und Audit ist jedes Fach-Ticket unsicher.

## Arbeitsregel

1. **Ein Ticket = ein Agent-Chat = ein Commit.** Prompt aus Abschnitt 5 des Backlogs kopieren.
2. Den **gesamten Backlog nicht** in den Chat legen. Reicht: Ticket-ID, Prompt, diese Datei, `AGENTS.md`/`CLAUDE.md`, betroffene Dateien.
3. Starter-Kit-Code **nicht neu bauen**. Status `delta` heißt: Ist gegen Soll prüfen und nur die Lücke schließen.
4. Kein sachfremdes Refactoring, kein Produktions-Deploy.
5. Nach jedem Ticket: Tests, Review, Commit, Status unten auf `done` setzen.

## Ist-Stand (Repo)

Vorhanden: Laravel 13, PHP ^8.4, Livewire 4, Flux 2, Fortify, Pest, Pint, Larastan Level 7, App-Shell, Smoke-Tests, README/Herd, UI-Locale `de`, `Docu/architecture/*`, EquiFlow-Block in `AGENTS.md`/`CLAUDE.md`.

Fehlt: Tenant-Global-Scope, RBAC, Audit, Einladungen, PWA.

## Empfohlene Reihenfolge

Abweichungen vom Backlog sind Absicht: Doku früh, Tenant-Middleware erst nach Organisationen, Security-Header direkt nach Auth.

| # | Ticket | Art | Warum jetzt |
|---|--------|-----|-------------|
| 1 | **DEV-001** | done | Inventur geschlossen: Herd-README, Locale `de`, PHP 8.4, Smoke-Tests. |
| 2 | **DEV-008** | done | ADRs, DoD, Security-Checkliste, Git-Konvention, Agenten-Block. |
| 3 | **DEV-003** | done | `organizations` (UUID), Memberships, idempotenter Demo-Seeder, ADR-0001. |
| 4 | **DEV-004** | done | `tenant`-Middleware, Error/Empty/Loading, Validierungs-/Actions-Konvention. |
| 5 | **DEV-005** | delta | Shell existiert. EquiFlow-Navigation, Org-Switcher-Platzhalter, `/dev/ui`, Mobile-Nav. |
| 6 | **DEV-006** | neu | PWA hängt an der Shell. |
| 7 | **DEV-007** | delta | CI härten (PHP 8.4, Migration, Vite-Build). Braucht DEV-001 und DEV-003. |
| 8 | **SEC-001** | delta | Fortify ist da. Session/Logout/Verifikation gegen Akzeptanzkriterien prüfen. |
| 9 | **SEC-007** | delta | Login-Rate-Limit existiert. Header + 429-Tests nachziehen. Unabhängig von Tenant. |
| 10 | **SEC-002** | delta+neu | Reset ist da. Einladungs-Token-Modell neu (für SEC-005). |
| 11 | **SEC-003** | neu | Harte Tenant-Isolation. Kern von R0. |
| 12 | **SEC-004** | neu | RBAC, Policies, Standardrollen. |
| 13 | **SEC-005** | neu | Einladungen + Org-Switcher. |
| 14 | **SEC-006** | neu | Audit-Log. |

R1 (`CORE-001`) erst, wenn die Tabelle unten durchgängig `done` ist.

## Gap-Analyse

| Ticket | Status | Schon da | Noch zu tun |
|--------|--------|----------|-------------|
| DEV-001 | **done** | README/Herd, `.env.example` (SQLite/pgsql, `APP_LOCALE=de`), PHP ^8.4, CI PHP 8.4, `tests/Feature/SmokeTest.php` | — |
| DEV-003 | **done** | `organizations` (UUID-PK), `organization_memberships`, Factories, idempotenter Demo-Seeder, [ADR-0001](architecture/adr/0001-uuid-primary-keys.md) | — |
| DEV-004 | **done** | `tenant`-Gruppe (`EnsureActiveOrganization`), `errors/no-organization`, `<x-ui.*>`, JSON-Handler blieb | — |
| DEV-005 | offen / delta | Flux-Sidebar, Header, Layouts | Mobile Bottom-Nav; Org-Switcher-Platzhalter; `/dev/ui`; 360px-/Touch-Tests |
| DEV-006 | offen / neu | Favicons in `partials/head` | Manifest, Service Worker, Offline-Fallback, Theme-Farben |
| DEV-007 | offen / delta | `.github/workflows/tests.yml` (PHP 8.4, `composer setup` + `ci:check`) | Expliziter Migrate-Schritt; Vite-Build; ggf. `ci.yml` laut Ticket |
| DEV-008 | **done** | `Docu/architecture/*`, EquiFlow-Block in AGENTS/CLAUDE, `.ai/rules/index.md` | — |
| SEC-001 | offen / delta | Fortify vollständig, Auth-Tests, Session `http_only` + `same_site=lax` | Abgleich Akzeptanzkriterien; Session-Invalidierung nach Logout explizit testen |
| SEC-002 | offen / delta+neu | Password-Reset + Tests, `MAIL_MAILER=log` | Enumeration-safe prüfen; Token-Modell `expires_at`/`used_at` für Einladungen |
| SEC-003 | offen / neu | — | ActiveOrganization, Global Scope, IDOR-Tests |
| SEC-004 | offen / neu | — | Role/Permission, Policies, Seeder |
| SEC-005 | offen / neu | — | Invitation-Flow, Org-Switcher |
| SEC-006 | offen / neu | — | AuditLog, Observer, Allowlist, Admin-Ansicht |
| SEC-007 | offen / delta | Login-RateLimiter (5/min) in `FortifyServiceProvider` | Security-Header-Middleware; 429- und Header-Tests |

## Offene Entscheidungen (in den genannten Tickets treffen, nicht vorher)

| Entscheidung | Ticket | Hinweis |
|--------------|--------|---------|
| UUID vs. Auto-Increment für Fachentitäten | DEV-003 | **Entschieden:** UUID-PK (`HasUuids`/UUIDv7) für Fachaggregate. `users` bleibt Integer. Memberships: Integer-PK. Siehe ADR-0001. |
| PHP 8.4 festziehen (`composer.json` + CI) | DEV-001 | **Entschieden:** `php: ^8.4`, CI `php-version: 8.4`. |
| UI-Locale Deutsch als Default | DEV-001 | **Entschieden:** `APP_LOCALE=de`, Fallback `en`. Tests erzwingen `en` in `phpunit.xml`. |
| E-Mail-Verifikation Pflicht für Pilot? | SEC-001 | Feature ist aktiv. Ob unverifizierte User das Dashboard nutzen dürfen, hier entscheiden. |
| Standardrollen-Namen | SEC-004 | Backlog: admin, treasurer, member, staff. Nicht umbenennen ohne ADR. |
| Org-Kontext: Session vs. URL | SEC-003 | Kleinste reversible Lösung; als ADR dokumentieren. |

Nicht in R0 entscheiden: Payment-Provider, Newsletter, Health Scores, konkrete Fremdsoftware-Connectoren.

## Ticket starten

Neuen Agent-Chat öffnen und den Prompt des Tickets aus dem Backlog (Abschnitt 5) einfügen. Zusätzlich:

```text
Lies zuerst Docu/R0_Kickoff.md (Gap-Zeile dieses Tickets) und AGENTS.md.
Das Repo ist ein Livewire-Starter-Kit. Status "delta" bedeutet: bestehenden Code prüfen und nur die Lücke schließen, Auth/Shell/CI nicht neu erfinden.
```

Nach Abschluss in dieser Datei Status auf `done` setzen und in 1–2 Zeilen notieren, was gebaut wurde.

## R0 Done

R0 ist fertig, wenn:

- alle 14 Tickets `done` sind
- `composer ci:check` grün ist
- Cross-Tenant-Test (SEC-003) und Permission-Negativtests (SEC-004) grün sind
- `.ai/rules/index.md` existiert
- kein Produktions-Deploy ohne menschliche Freigabe erfolgte
