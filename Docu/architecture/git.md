# Commit- und PR-Konvention

## Commit

Ein Ticket = ein Commit (oder wenige zusammenhängende Commits desselben Tickets).

```text
DEV-008: Architektur- und Agenten-Dokumentation anlegen
```

- Erste Zeile: Ticket-ID, dann warum (nicht die Dateiliste).
- Kein `--no-verify`, kein `--no-gpg-sign`.
- Kein `git commit --amend` an fremde oder bereits gepushte Commits.
- `.env`, Keys und Credentials nie stagen.

## Pull Request

- Titel wie der Commit, mit Ticket-ID.
- Body: Kurzfassung, Testhinweis, Risiken.
- CI (`composer ci:check`) muss grün sein.
- Production-Merge nur nach menschlicher Freigabe.

## Was Agents nicht tun

- `git push --force` auf `main`/`master`
- `git config` ändern
- Produktions-Deploy auslösen
