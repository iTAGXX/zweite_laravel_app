# EquiFlow – Architekturübersicht

Kurze Landkarte für Entwickler und Coding-Agents. Ticket-Details stehen im Backlog, die Reihenfolge in [`Docu/R1_Kickoff.md`](../R1_Kickoff.md). R0 ist abgeschlossen ([`R0_Kickoff.md`](../R0_Kickoff.md)).

## Stack (Ist)

| Schicht | Wahl |
|---------|------|
| App | Laravel 13 Monolith, PHP 8.4, `declare(strict_types=1)` |
| UI | Livewire 4 Single-File-Components, Flux UI 2, Tailwind 4, Vite 8 |
| Auth | Laravel Fortify (Login, Register, Reset, Verifikation, 2FA, Passkeys). E-Mail-Verifikation Pflicht ([ADR-0002](adr/0002-email-verification-required.md)). |
| Daten | SQLite lokal und in Tests; PostgreSQL auf Laravel Cloud. Fachaggregate: UUID-PK ([ADR-0001](adr/0001-uuid-primary-keys.md)); `users` bleibt Integer. |
| Qualität | Pint, Larastan Level 7, Pest; Einstieg `composer ci:check` |
| Lokal | Laravel Herd (`.test`-Domain). Setup: [`README.md`](../../README.md) |

## Arbeitsweise

- Ein Ticket pro Agent-Chat, dann Tests, Review, Commit.
- Starter-Kit-Code nicht neu erfinden (Auth, Shell, CI).
- Kein Produktions-Deploy ohne menschliche Freigabe.
- Keine sachfremden Refactorings.
- Architekturentscheidungen, die hier nicht stehen: kleinste reversible Lösung + ADR nach [`adr-template.md`](adr-template.md).

## Backend

- Fachlogik in `app/Actions`. Neue Actions: eine Klasse, Methode `handle()`. Fortify-Actions behalten die Vertragsmethoden (`create`, …).
- Livewire-Validierung: Traits in `app/Concerns` (Muster: `ProfileValidationRules`). Feldfehler über `<flux:input>` / `<flux:field>`; Formularzusammenfassung: `<x-ui.form-errors />`.
- Leer-/Fehler-/Ladezustände: `<x-ui.empty-state>`, `<x-ui.error-state>`, `<x-ui.loading-state>`.
- Autorisierung: Gäste → Login. Unverifiziert → `verification.notice`. Authentifiziert ohne Recht → 403. Ohne Organisation auf `tenant`-Routen → `errors/no-organization` (403). Rollen über `organization_memberships.role_id`; Permissions als Gates (`users.manage`, `finance.view`, `audit.view`, `people.manage`, `documents.manage`, `tasks.manage`). UI-`@can` ist nur UX ([ADR-0004](adr/0004-custom-rbac.md)).
- Audit: unveränderbare `audit_logs` über `AuditLogger` + Observer/Login-Listener; Metadata-Allowlist, keine Passwörter/Tokens/IBAN-Vollwerte ([ADR-0005](adr/0005-immutable-audit-log.md)). Admin-Ansicht `/audit`.
- JSON-Fehler für `api/*` und `expectsJson()` in `bootstrap/app.php` (schon konfiguriert).
- Livewire-first: keine allgemeine REST-API im MVP. Nur punktuelle Controller (Webhooks, Health `/up`, öffentliche Anmeldung später).
- Security-Header global (`SecurityHeaders`: `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`). Login-Rate-Limit 5/min (Fortify `RateLimiter` `login`). Passwort-Reset enumeration-safe; Einladungs-Token (`invitations`) mit `expires_at`/`used_at` (gehasht, Single-Use).
- Routen: `routes/web.php` plus bereichsspezifische Dateien (`routes/settings.php`). Mandanten-Routen: Middleware-Gruppe `tenant` (`EnsureActiveOrganization`, Session-Key `active_organization_id`, [ADR-0003](adr/0003-organization-context-session.md)). Fachmodule zusätzlich `EnsureModuleEnabled` (`module:club` / `module:stable`, [ADR-0006](adr/0006-organization-type-and-module-flags.md)).

## Frontend

- Seiten: `resources/views/pages/**/⚡name.blade.php`
- Layouts: `resources/views/layouts/` (Desktop: Flux-Sidebar; Mobil: Livewire-Bottom-Nav `mobile-navigation`, Touch-Targets `min-h-11`)
- Org-Switcher: Livewire `organization-switcher` schreibt `active_organization_id` (SEC-005)
- Flux-Bausteine: `/dev/ui` nur in `local`/`testing`
- PWA: Manifest `pwa.manifest` (`/manifest.webmanifest`), Service Worker `/sw.js` (nur GET cachen, keine Livewire-Writes), Offline-Seite `pwa.offline`, Hinweis `x-offline-notice`, Farben in `config/pwa.php`
- UI-Texte über `__()` / Sprachdateien. Default-Locale `de`, Fallback `en`. Tests: `APP_LOCALE=en` in `phpunit.xml`.

## Mandanten (ab DEV-003 / SEC-003)

`EnsureActiveOrganization` setzt die aktive Organisation in Session und `Context` ([ADR-0003](adr/0003-organization-context-session.md)). Jede Fachentität trägt `organization_id` und den Eloquent Global Scope `BelongsToOrganization`. Cross-Tenant-Zugriff liefert 404 ohne Datenleck. UI-Ausblenden ersetzt keine serverseitige Prüfung. `organization_memberships` bleibt ohne diesen Scope (Join-Tabelle).

`Person` ist das zentrale Personenaggregat (UUID, optionales `user_id`, Archiv über `archived_at`, [ADR-0007](adr/0007-person-aggregate-and-archive.md)). Member-/Customer-Profile kommen später und zeigen auf dieselbe Person.

`Task` ist das zentrale Aufgabenaggregat (UUID, Assignee=`Person`, Status/Priorität, Quick-Create, [ADR-0009](adr/0009-task-aggregate-and-allowlisted-links.md)). Objekt-Links über `task_links` mit Enum-Allowlist (zuerst `person`); keine Fake-Fachmodelle. Wiederkehrende Aufgaben sind `TaskRecurrence` (monatlich/jährlich, [ADR-0010](adr/0010-recurrence-subset.md)): der tägliche Job erzeugt `Task`-Instanzen mit Idempotenzschlüssel `occurrence_key`; Pause/`ends_on` stoppen die Serie.

`Document` / `DocumentVersion` speichern Dateimetadaten mandantenbezogen; Bytes liegen auf der Storage-Disk (`local` bzw. `s3` über `DOCUMENT_DISK`), Downloads über signierte Temporary URLs mit `Content-Disposition: attachment` ([ADR-0008](adr/0008-document-storage.md)). Cross-Tenant-Download ist 403.

Organisationstyp (`OrganizationType`) und Modul-Flags (`enabled_modules` JSON) liegen auf `organizations` ([ADR-0006](adr/0006-organization-type-and-module-flags.md)). Deaktivierte Module fehlen in der Nav und liefern 403 auf der Route. Org-Settings ändern nur den aktiven Mandanten (`OrganizationPolicy`, `users.manage`).

## Dateien und Betrieb

- Dateien über Laravel `Storage` (`local` lokal, S3-kompatibel in der Cloud, `DOCUMENT_DISK`). Die DB speichert Metadaten, keine Binärdateien. Kein Public-Pfad; zufällige Keys; MIME-/Größen-Whitelist in `config/documents.php`.
- Deploy: GitHub → Laravel Cloud. Staging nach grünem CI, Production nur mit Freigabe.

## Dokumente in diesem Ordner

| Datei | Inhalt |
|-------|--------|
| [definition-of-done.md](definition-of-done.md) | Ticket-DoD |
| [security-checklist.md](security-checklist.md) | Tenant, Policy, Secrets, Logs |
| [adr-template.md](adr-template.md) | Vorlage für ADRs |
| [git.md](git.md) | Commit- und PR-Konvention |
