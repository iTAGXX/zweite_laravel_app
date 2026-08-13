---
paths:
  - 'app/**/*.php'
---

# App

## No prod deploys, one ticket, no drive-by refactors
Never deploy to production. Never push --force to main. Security changes, data migrations, and production releases need a human. One backlog ticket per agent task; no unrelated refactors. Follow Docu/architecture/definition-of-done.md before marking a ticket done.

## Strict types, Actions, migrations with factories
Every new PHP file starts with declare(strict_types=1);. Business operations live in app/Actions with handle(). Validation traits live in app/Concerns. Schema changes only as a Laravel migration plus factory.
