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

Fehlt: Audit.

## Empfohlene Reihenfolge

Abweichungen vom Backlog sind Absicht: Doku früh, Tenant-Middleware erst nach Organisationen, Security-Header direkt nach Auth.

| # | Ticket | Art | Warum jetzt |
|---|--------|-----|-------------|
| 1 | **DEV-001** | done | Inventur geschlossen: Herd-README, Locale `de`, PHP 8.4, Smoke-Tests. |
| 2 | **DEV-008** | done | ADRs, DoD, Security-Checkliste, Git-Konvention, Agenten-Block. |
| 3 | **DEV-003** | done | `organizations` (UUID), Memberships, idempotenter Demo-Seeder, ADR-0001. |
| 4 | **DEV-004** | done | `tenant`-Middleware, Error/Empty/Loading, Validierungs-/Actions-Konvention. |
| 5 | **DEV-005** | done | Shell existiert. EquiFlow-Navigation, Org-Switcher-Platzhalter, `/dev/ui`, Mobile-Nav. |
| 6 | **DEV-006** | done | PWA hängt an der Shell. |
| 7 | **DEV-007** | skipped | CI härten (PHP 8.4, Migration, Vite-Build). Bewusst nicht: Starter-CI (PHP 8.4, `composer setup` + `ci:check`) reicht. |
| 8 | **SEC-001** | done | Fortify ist da. Session/Logout/Verifikation gegen Akzeptanzkriterien prüfen. |
| 9 | **SEC-007** | done | Login-Rate-Limit existiert. Header + 429-Tests nachziehen. Unabhängig von Tenant. |
| 10 | **SEC-002** | done | Reset ist da. Einladungs-Token-Modell neu (für SEC-005). |
| 11 | **SEC-003** | done | Harte Tenant-Isolation. Kern von R0. |
| 12 | **SEC-004** | done | RBAC, Policies, Standardrollen. |
| 13 | **SEC-005** | done | Einladungen + Org-Switcher. |
| 14 | **SEC-006** | neu | Audit-Log. |

R1 (`CORE-001`) erst, wenn die Tabelle unten durchgängig `done` oder bewusst `skipped` ist.

## Gap-Analyse

| Ticket | Status | Schon da | Noch zu tun |
|--------|--------|----------|-------------|
| DEV-001 | **done** | README/Herd, `.env.example` (SQLite/pgsql, `APP_LOCALE=de`), PHP ^8.4, CI PHP 8.4, `tests/Feature/SmokeTest.php` | — |
| DEV-003 | **done** | `organizations` (UUID-PK), `organization_memberships`, Factories, idempotenter Demo-Seeder, [ADR-0001](architecture/adr/0001-uuid-primary-keys.md) | — |
| DEV-004 | **done** | `tenant`-Gruppe (`EnsureActiveOrganization`), `errors/no-organization`, `<x-ui.*>`, JSON-Handler blieb | — |
| DEV-005 | **done** | Flux-Sidebar, Header, Layouts; Livewire-Bottom-Nav (`mobile-navigation`, 44px); Org-Switcher-Platzhalter; `/dev/ui` (local/testing); 360px-/Toggle-Tests | — |
| DEV-006 | **done** | Manifest-Route `/manifest.webmanifest`, Platzhalter-Icons 192/512, Service Worker `/sw.js` (App-Shell-Cache, keine POST-Writes), Offline-Seite `/offline`, Offline-Hinweis + Livewire-Fehler-Toast, `theme_color`/`background_color` | — |
| DEV-007 | **skipped** | `.github/workflows/tests.yml` (PHP 8.4, `composer setup` + `ci:check`) | Bewusst nicht nachgezogen: expliziter Migrate-Schritt, Vite-Build, extra `ci.yml`. |
| DEV-008 | **done** | `Docu/architecture/*`, EquiFlow-Block in AGENTS/CLAUDE, `.ai/rules/index.md` | — |
| SEC-001 | **done** | Fortify, `MustVerifyEmail`, `verified` auf Dashboard/Tenant, Session `http_only` + `same_site=lax`, Logout invalidiert Session (Pest) | — |
| SEC-002 | **done** | Fortify-Reset, `MAIL_MAILER=log`, enumeration-safe Link-Request, `invitations` (`expires_at`/`used_at`, gehasht) | — |
| SEC-003 | **done** | Session-Kontext [ADR-0003](architecture/adr/0003-organization-context-session.md), `BelongsToOrganization` auf `Invitation`, IDOR → 404 | — |
| SEC-004 | **done** | Eigenes RBAC [ADR-0004](architecture/adr/0004-custom-rbac.md), Rollen admin/treasurer/member/staff, Gates `users.manage`/`finance.view`, InvitationPolicy, 403 ohne Permission | — |
| SEC-005 | **done** | Einladen per Livewire + Mailable; Annehmen setzt Membership+Rolle; Org-Switcher schreibt Session (ADR-0003) | — |
| SEC-006 | offen / neu | — | AuditLog, Observer, Allowlist, Admin-Ansicht |
| SEC-007 | **done** | Login-RateLimiter (5/min); globale `SecurityHeaders`; CSRF in Security-Checkliste; 429- und Header-Tests | — |

## Offene Entscheidungen (in den genannten Tickets treffen, nicht vorher)

| Entscheidung | Ticket | Hinweis |
|--------------|--------|---------|
| UUID vs. Auto-Increment für Fachentitäten | DEV-003 | **Entschieden:** UUID-PK (`HasUuids`/UUIDv7) für Fachaggregate. `users` bleibt Integer. Memberships: Integer-PK. Siehe ADR-0001. |
| PHP 8.4 festziehen (`composer.json` + CI) | DEV-001 | **Entschieden:** `php: ^8.4`, CI `php-version: 8.4`. |
| UI-Locale Deutsch als Default | DEV-001 | **Entschieden:** `APP_LOCALE=de`, Fallback `en`. Tests erzwingen `en` in `phpunit.xml`. |
| E-Mail-Verifikation Pflicht für Pilot? | SEC-001 | **Entschieden:** Pflicht. `User` implementiert `MustVerifyEmail`; Dashboard/Tenant hinter `verified`. Siehe [ADR-0002](architecture/adr/0002-email-verification-required.md). |
| Standardrollen-Namen | SEC-004 | **Entschieden:** admin, treasurer, member, staff. Eigenes Katalog-RBAC, nicht Spatie. Siehe [ADR-0004](architecture/adr/0004-custom-rbac.md). |
| Org-Kontext: Session vs. URL | SEC-003 | **Entschieden:** Session (`active_organization_id`) plus `Context`. Siehe [ADR-0003](architecture/adr/0003-organization-context-session.md). |

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

- alle 14 Tickets `done` oder bewusst `skipped` sind (DEV-007: skipped)
- `composer ci:check` grün ist
- Cross-Tenant-Test (SEC-003) und Permission-Negativtests (SEC-004) grün sind
- `.ai/rules/index.md` existiert
- kein Produktions-Deploy ohne menschliche Freigabe erfolgte
