# EquiFlow

Laravel-Monolith für Vereins- und Pferdebetriebsverwaltung. Lokale Entwicklung mit [Laravel Herd](https://herd.laravel.com/).

## Voraussetzungen

- PHP 8.4 (Herd bringt das mit)
- Composer, Node.js 22, npm
- SQLite lokal; PostgreSQL auf Laravel Cloud

## Setup mit Herd

Das Projekt liegt unter `Herd/zweite_laravel_app`. Herd stellt die App unter einer `.test`-Domain bereit (Ordnername).

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
```

In `.env` `APP_URL` an die Herd-Domain anpassen, z. B. `http://zweite_laravel_app.test`. Die App ist danach unter dieser URL erreichbar.

Frontend-Assets während der Entwicklung:

```bash
npm run dev
```

`composer run dev` startet zusätzlich `php artisan serve` und den Queue-Worker. Unter Herd ist der eingebaute PHP-Server überflüssig; Vite (`npm run dev`) reicht für das Frontend.

## Qualität

| Befehl | Zweck |
|--------|--------|
| `composer lint` | Laravel Pint (fix) |
| `composer lint:check` | Pint ohne Änderungen |
| `composer types:check` | Larastan Level 7 (`--memory-limit=512M`) |
| `php artisan test --compact` | Pest |
| `composer ci:check` | Pint → Larastan → Pest (CI-Einstieg) |

Keine Secrets in Git. `.env` bleibt lokal; `.env.example` enthält nur Platzhalter.

Agenten-Leitplanken: `AGENTS.md` / `CLAUDE.md`. Ticket-Reihenfolge: `Docu/R0_Kickoff.md`.
