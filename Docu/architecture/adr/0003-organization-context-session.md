# ADR-0003: Mandantenkontext in der Session

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** SEC-003
- **Supersedes:** —

## Kontext

Jede fachliche Anfrage muss an genau eine aktive Organisation gebunden sein. Kickoff ließ Session vs. URL offen. DEV-004 speichert bereits `active_organization_id` in der Session (`EnsureActiveOrganization`).

## Entscheidung

Der Org-Kontext liegt in der **Session** (Key `active_organization_id`), gespiegelt in Laravel `Context` für Eloquent-Scopes und Queues. URL-Segmente (`/{organization}/…`) kommen nicht in R0.

`BelongsToOrganization` filtert Fachmodelle auf diese ID. Fehlt der Kontext, liefert der Scope keine Zeilen. Cross-Tenant-IDs werden über Route-Model-Binding zu **404**.

## Konsequenzen

- Positiv: kleinste Änderung; bestehendes Middleware-Verhalten bleibt; Org-Switcher (SEC-005) schreibt nur den Session-Key um.
- Negativ / Follow-up: Deep Links auf eine fremde Org in der URL gibt es nicht. Umstellung auf URL wäre ein neues ADR und reversibel, solange der Session-Key parallel gesetzt wird.

## Alternativen

1. Org-ID in der URL — teilbar und bookmarkbar, aber bricht die aktuelle Shell und jedes interne `route()`.
2. Nur Request-Attribute ohne Session — Jobs und Folge-Requests verlieren den Mandanten.
