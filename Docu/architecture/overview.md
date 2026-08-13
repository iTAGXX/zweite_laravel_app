# EquiFlow – Architekturübersicht

Kurze Landkarte für Entwickler und Coding-Agents. Ticket-Details stehen im Backlog, die Reihenfolge in [`Docu/R0_Kickoff.md`](../R0_Kickoff.md).

## Stack (Ist)

| Schicht | Wahl |
|---------|------|
| App | Laravel 13 Monolith, PHP 8.4, `declare(strict_types=1)` |
| UI | Livewire 4 Single-File-Components, Flux UI 2, Tailwind 4, Vite 8 |
| Auth | Laravel Fortify (Login, Register, Reset, Verifikation, 2FA, Passkeys) |
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
- Autorisierung: Gäste → Login. Authentifiziert ohne Recht → 403. Ohne Organisation auf `tenant`-Routen → `errors/no-organization` (403).
- JSON-Fehler für `api/*` und `expectsJson()` in `bootstrap/app.php` (schon konfiguriert).
- Livewire-first: keine allgemeine REST-API im MVP. Nur punktuelle Controller (Webhooks, Health `/up`, öffentliche Anmeldung später).
- Routen: `routes/web.php` plus bereichsspezifische Dateien (`routes/settings.php`). Mandanten-Routen: Middleware-Gruppe `tenant` (`EnsureActiveOrganization`, Session-Key `active_organization_id`). Session vs. URL entscheidet SEC-003.

## Frontend

- Seiten: `resources/views/pages/**/⚡name.blade.php`
- Layouts: `resources/views/layouts/`
- UI-Texte über `__()` / Sprachdateien. Default-Locale `de`, Fallback `en`. Tests: `APP_LOCALE=en` in `phpunit.xml`.

## Mandanten (ab DEV-003 / SEC-003)

`EnsureActiveOrganization` setzt die aktive Organisation in der Session. Jede Fachentität trägt `organization_id` und (ab SEC-003) einen Eloquent Global Scope. Cross-Tenant-Zugriff liefert 404 ohne Datenleck. UI-Ausblenden ersetzt keine serverseitige Prüfung.

## Dateien und Betrieb

- Dateien über Laravel `Storage` (`local` lokal, S3-kompatibel in der Cloud). Die DB speichert Metadaten, keine Binärdateien.
- Deploy: GitHub → Laravel Cloud. Staging nach grünem CI, Production nur mit Freigabe.

## Dokumente in diesem Ordner

| Datei | Inhalt |
|-------|--------|
| [definition-of-done.md](definition-of-done.md) | Ticket-DoD |
| [security-checklist.md](security-checklist.md) | Tenant, Policy, Secrets, Logs |
| [adr-template.md](adr-template.md) | Vorlage für ADRs |
| [git.md](git.md) | Commit- und PR-Konvention |
