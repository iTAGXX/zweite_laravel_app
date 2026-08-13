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
- [ ] State-Änderungen über Web/Livewire. CSRF ist aktiv (`PreventRequestForgery` auf der `web`-Gruppe). Livewire sendet das Token automatisch. Zusätzliche AJAX-Calls: Header `X-CSRF-TOKEN` (aus `csrf_token()`) oder `X-XSRF-TOKEN` (Cookie `XSRF-TOKEN`). Webhooks nicht in `web` legen oder per `preventRequestForgery(except:)` ausnehmen und per Signatur prüfen — CSRF nie pauschal abschalten.
- [ ] Request-Body-Limits gehören in Webserver/PHP (`client_max_body_size`, `post_max_size`, `upload_max_filesize`), nicht in App-Code. Herd- und Cloud-Defaults reichen für R0; bei großen Uploads (CORE-005) explizit setzen.
- [ ] Uploads nur über `Storage`; die DB speichert Metadaten.

## Deploy

- [ ] Kein Produktions-Deploy durch den Agenten.
- [ ] Security-Änderungen, Datenmigrationen und Production brauchen menschliche Freigabe.
