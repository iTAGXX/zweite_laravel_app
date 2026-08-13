# Security-Checkliste

Vor jedem Ticket-Abschluss prüfen. Punkte, die das Ticket nicht berührt, kurz überspringen.

## Tenant

- [ ] Neue Fachentität hat `organization_id` (NOT NULL) und `BelongsToOrganization`-Scope (ab SEC-003).
- [ ] Cross-Tenant-ID liefert 404, keine Fremddaten, keine unterscheidbare Fehlermeldung.
- [ ] Pest: negativer IDOR-Test, sobald die Ressource mandantenbezogen ist.

## Autorisierung

- [ ] Sensible Aktionen über Policy oder Gate (deny by default, ab SEC-004).
- [ ] UI-Ausblenden (`@can`) ist nur UX; die Serverprüfung bleibt.
- [ ] Pest: 403 ohne Berechtigung.

## Secrets und Logs

- [ ] Keine Secrets in Git, Prompts oder ADRs. `.env` bleibt lokal; `.env.example` nur Platzhalter.
- [ ] Logs enthalten keine Passwörter, Tokens, IBAN-Vollwerte oder Recovery-Codes.
- [ ] `APP_DEBUG=false` ist die Vorgabe außerhalb von `local`.

## Session, CSRF, Input

- [ ] Auth bleibt Fortify. Keine eigenen Login-/Reset-Flows.
- [ ] State-Änderungen über Web/Livewire (CSRF aktiv). Keine neue REST-Schreib-API ohne Ticket.
- [ ] Uploads nur über `Storage`; die DB speichert Metadaten.

## Deploy

- [ ] Kein Produktions-Deploy durch den Agenten.
- [ ] Security-Änderungen, Datenmigrationen und Production brauchen menschliche Freigabe.
