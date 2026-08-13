---
paths:
  - composer.json
  - .env.example
  - phpstan.neon
---

# General

## PHP 8.4 is required
Require PHP ^8.4. Do not lower the constraint or run CI on 8.3. Local Herd and GitHub Actions must use PHP 8.4.

## SQLite local, Postgres in cloud, German UI locale
Local and tests use SQLite. Staging and production use PostgreSQL on Laravel Cloud. Never commit secrets; .env.example holds placeholders only. Default UI locale is de with en fallback. Keep tests on APP_LOCALE=en in phpunit.xml until translations exist.

## Larastan level 7, no casual ignores
Larastan runs at level 7 via composer types:check (`phpstan analyse --memory-limit=512M`). composer ci:check already includes Pint, Larastan, and Pest. Do not add ignore errors without a ticket and a short reason in phpstan.neon.
