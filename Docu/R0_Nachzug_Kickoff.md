# R0-Nachzug Kickoff – Organisations-Benutzerverwaltung

> Kurze Ausführungsliste. **Kein zweites Backlog.** Ticket-Details, Akzeptanzkriterien und Cursor-Prompts stehen in [`Entwicklungsbacklog_EquiFlow_v2_0_Laravel.md`](Entwicklungsbacklog_EquiFlow_v2_0_Laravel.md) Abschnitt 4 und 5 (SEC-008–SEC-011).

Stand: 13. August 2026 · Quelle: Gap nach SEC-005 · R0: [`R0_Kickoff.md`](R0_Kickoff.md) · R1: [`R1_Kickoff.md`](R1_Kickoff.md)

## Zweck

Login-User der Organisation auf `/members` sichtbar und ohne SMTP nutzbar machen. R0 hat nur Einladen+Mail. Vor R2 (`CLUB-001`, Vereinsmitglieder/`Person`) schließen.

## Arbeitsregel

1. **Ein Ticket = ein Agent-Chat = ein Commit.** Unten den Block des Tickets komplett in einen **neuen** Chat legen.
2. Den **gesamten Backlog nicht** in den Chat legen.
3. R0/R1-Code **nicht neu bauen.** Fortify, Tenant, RBAC, Audit, Invite-Flow und Shell stehen. Nur den Delta schließen.
4. `/members` = Login-Konten (`User` + `OrganizationMembership`). Nicht mit CLUB-001 (`Person`) vermischen.
5. Kein sachfremdes Refactoring, kein Produktions-Deploy.
6. Nach jedem Ticket: Tests, Review, Commit, Status unten auf `done` setzen.
7. **Kein `/multitask` über alle vier Tickets.** SEC-008 zuerst. SEC-010 darf danach parallel zu SEC-009/SEC-011 in einem eigenen Chat laufen.

## Ist-Stand (Repo)

Vorhanden: Invite-Livewire `resources/views/components/⚡invite-members.blade.php` auf `/members` inkl. Benutzerliste (Name, E-Mail, Rolle) der Session-Org; `InviteMember` queued `OrganizationInvitation`; Token gehasht; Annehmen nur mit Accept-URL; Demo-Admin `test@example.com` via Seeder; Gate `users.manage`; Audit auf Membership/Invitation.

Fehlt: Rolle ändern / Membership entfernen; kopierbarer Einladungslink; User direkt anlegen (verifiziert, ohne Mail).

## Empfohlene Reihenfolge

| # | Ticket | Art | Warum jetzt |
|---|--------|-----|-------------|
| 1 | **SEC-008** | done | Liste zuerst. Ohne sie sind 009/011 nicht bedienbar. |
| 2 | **SEC-010** | open | Hängt nur an SEC-005. Nach 008 oder parallel zu 009/011. |
| 3 | **SEC-009** | open | Braucht die Liste aus SEC-008. |
| 4 | **SEC-011** | open | Braucht die Liste aus SEC-008. |

R2 (`CLUB-001`) erst, wenn die Gap-Tabelle durchgängig `done` ist.

## Gap-Analyse

| Ticket | Status | Schon da | Noch zu tun |
|--------|--------|----------|-------------|
| SEC-008 | **done** | `/members` + `invite-members`; Memberships + Rollen; `users.manage` | Liste Name/E-Mail/Rolle der Session-Org in `invite-members` (Query `organization_memberships` der Session-Org, Gate `users.manage`); `test@example.com` sichtbar; Cross-Tenant; 403 ohne Permission |
| SEC-009 | **open** | Audit `membership.role_changed`; `OrganizationMembership` | Rolle ändern; Membership entfernen; letzter Admin und Self-Remove blocken |
| SEC-010 | **open** | Invite + gehashtes Token; Accept-Route | „Link kopieren“ / Resend mit neuem Token; Annahme ohne SMTP (`Mail::fake`) |
| SEC-011 | **open** | Fortify `CreateNewUser`; `MustVerifyEmail` | Admin legt User an (Passwort, `email_verified_at`, Membership); Duplikat; bestehender User nur Membership |

## Offene Entscheidungen (im Ticket treffen, nicht vorher)

| Entscheidung | Ticket | Hinweis |
|--------------|--------|---------|
| Liste in `invite-members` vs. zweite Livewire-Komponente | SEC-008 | Kleinste Lösung: bestehende Komponente erweitern. Zweite Komponente nur wenn die Datei unlesbar wird. Name nicht `members` (kollidiert mit der Page-View). |
| Copy-Link: Toast nach Invite vs. Resend-Token | SEC-010 | Token bleibt gehasht. Entweder Link nur direkt nach `sendInvitation`, oder Copy stellt neuen Token aus und invalidiert den alten. |
| Passwort bei bestehendem User | SEC-011 | Nie überschreiben. Nur Membership anlegen. |

Nicht in diesem Nachzug entscheiden: SMTP/CORE-007, Vereinsmitglieder (CLUB-001), öffentliches Register abschalten.

## Ticket starten

Neuen Agent-Chat öffnen, **einen** der vier Blöcke unten komplett einfügen.

Nach Abschluss in dieser Datei Status auf `done` setzen und in 1–2 Zeilen notieren, was gebaut wurde.

---

### Chat 1 — SEC-008

```text
Lies zuerst Docu/R0_Nachzug_Kickoff.md (Gap-Zeile SEC-008) und AGENTS.md.
R0 und R1 sind fertig. Tenant, RBAC, Audit, Fortify, Invite-Flow und Shell nicht neu erfinden — nur den SEC-008-Delta schließen.

Implementiere Ticket SEC-008 – Organisations-Benutzerliste.
Ziel: Admins sehen alle Login-User der aktiven Organisation (Name, E-Mail, Rolle), einschließlich des Seeder-Admins.
Abhängigkeiten: SEC-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Livewire `/members` um Liste der Session-Organisation erweitern (neben Invite-Formular); Query über organization_memberships inkl. User und Rolle; Gate users.manage; nicht mit CLUB-001 (Person/Vereinsmitglied) vermischen.
Akzeptanzkriterien: test@example.com sichtbar bei vorhandener Membership; keine fremden Orgs; ohne users.manage 403.
Tests: Liste der aktiven Org; Cross-Tenant-Isolation; 403 ohne Permission.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.

Nach Abschluss: in Docu/R0_Nachzug_Kickoff.md SEC-008 auf done setzen.
```

Gebaut: Liste Name/E-Mail/Rolle der Session-Org in `invite-members` (Query `organization_memberships`, Gate `users.manage`).

### Chat 2 — SEC-010 (nach SEC-008 oder parallel)

```text
Lies zuerst Docu/R0_Nachzug_Kickoff.md (Gap-Zeile SEC-010) und AGENTS.md.
R0 und R1 sind fertig. Invite-Flow nicht neu erfinden — nur den SEC-010-Delta schließen.

Implementiere Ticket SEC-010 – Einladungslink ohne Mailversand.
Ziel: Admins können den Accept-Link kopieren und Einladungen ohne SMTP annehmen lassen.
Abhängigkeiten: SEC-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Pending Invitations „Link kopieren“; Token bleibt gehasht; Copy/Resend stellt neuen Token aus oder zeigt Link nur direkt nach sendInvitation; Mailversand bleibt optional; Gate users.manage.
Akzeptanzkriterien: Annahme ohne Mail-Zustellung; expire/reuse 403; Staff sieht keinen Link.
Tests: Annahme mit Mail::fake; alter Token nach Resend ungültig; Staff ohne Link; expire/reuse 403.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.

Nach Abschluss: in Docu/R0_Nachzug_Kickoff.md SEC-010 auf done setzen.
```

### Chat 3 — SEC-009 (nach SEC-008)

```text
Lies zuerst Docu/R0_Nachzug_Kickoff.md (Gap-Zeile SEC-009) und AGENTS.md.
R0 und R1 sind fertig. SEC-008-Liste und Audit nicht neu erfinden — nur den SEC-009-Delta schließen.

Implementiere Ticket SEC-009 – Rolle ändern und Membership entfernen.
Ziel: Admins können Rolle ändern und Membership entfernen, ohne den letzten Admin zu verlieren.
Abhängigkeiten: SEC-008, SEC-006. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Aktionen auf der Benutzerliste; Gate users.manage; letzten Admin nicht entfernen/herabstufen; Self-Remove ablehnen; Audit für Rollenwechsel und Entfernen.
Akzeptanzkriterien: Rollenwechsel setzt Rechte in der Session-Org (Treasurer ohne users.manage); letzter Admin bleibt; Self-Remove und fremde Org ohne Datenleck.
Tests: Rollenwechsel + Audit; Entfernen nur aktive Org; letzter Admin und Self-Remove abgelehnt; Cross-Org 404; 403 ohne Permission.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.

Nach Abschluss: in Docu/R0_Nachzug_Kickoff.md SEC-009 auf done setzen.
```

### Chat 4 — SEC-011 (nach SEC-008)

```text
Lies zuerst Docu/R0_Nachzug_Kickoff.md (Gap-Zeile SEC-011) und AGENTS.md.
R0 und R1 sind fertig. Fortify-Register und SEC-008-Liste nicht neu erfinden — nur den SEC-011-Delta schließen.

Implementiere Ticket SEC-011 – Benutzer ohne E-Mail-Versand anlegen.
Ziel: Admins legen Login-User direkt an (Passwort, verifiziert, Membership), ohne SMTP.
Abhängigkeiten: SEC-008, SEC-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Formular auf `/members` (Name, E-Mail, Passwort, Rolle); email_verified_at setzen; Membership anlegen; kein öffentliches Register; Duplikat in derselben Org abweisen; bestehender User ohne Membership nur Membership, Passwort unverändert; Audit; Gate users.manage.
Akzeptanzkriterien: Sofortiger Login in die Org; Duplikat abgewiesen; ohne Permission 403.
Tests: Create + Login ohne Mail; Duplikat; bestehender User nur Membership; 403; Tenant-Isolation.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.

Nach Abschluss: in Docu/R0_Nachzug_Kickoff.md SEC-011 auf done setzen.
```

## R0-Nachzug Done

Der Nachzug ist fertig, wenn:

- SEC-008, SEC-009, SEC-010, SEC-011 `done` sind
- Demo-Admin in der Liste steht, Einladungslink kopierbar ist, User ohne SMTP anlegbar ist
- Cross-Tenant- und Permission-Negativtests grün sind
- `composer ci:check` grün ist
- kein Produktions-Deploy ohne menschliche Freigabe erfolgte

Weiter: R2 (`CLUB-001`) laut [`R1_Kickoff.md`](R1_Kickoff.md).
