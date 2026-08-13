# ADR-0002: E-Mail-Verifikation ist Pflicht für den Pilot

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** SEC-001
- **Supersedes:** —

## Kontext

Fortify hatte `Features::emailVerification()` und die Middleware `verified` bereits aktiv, `User` implementierte `MustVerifyEmail` aber nicht. Ohne das Interface lässt `EnsureEmailIsVerified` unverifizierte Nutzer durch. Kickoff ließ offen, ob das Dashboard im Pilot ohne Verifikation nutzbar sein darf.

## Entscheidung

E-Mail-Verifikation ist für den Pilot **Pflicht**. `User` implementiert `MustVerifyEmail`. Dashboard- und Tenant-Routen bleiben hinter `auth` + `verified`. Profil (`settings/profile`) bleibt hinter `auth`, damit unverifizierte Nutzer Adresse ändern und den Hinweis sehen können.

## Konsequenzen

- Positiv: `verified` greift; neue Registrierungen bekommen die Verify-Mail; unverifizierter Dashboard-Zugriff endet auf `verification.notice`.
- Negativ / Follow-up: Demo-Seeder und Factories müssen verifizierte User anlegen (Factory-Default bleibt `email_verified_at = now()`). Einladungen (SEC-005) können Nutzer vorab verifizieren, wenn der Token die Mail bestätigt.

## Alternativen

1. Verifikation optional lassen — Feature und Middleware wären tot; für Mandanten-Pilot zu unsicher.
2. Verifikation erst nach Einladungen (SEC-005) — Dashboard wäre bis dahin ohne Mail-Check offen.
