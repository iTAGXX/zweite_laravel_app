# Entwicklungsbacklog – EquiFlow

> **Version 2.0 (Laravel):** Technisch überarbeitet auf den tatsächlichen Projekt-Stack (Laravel + Livewire + Herd + GitHub + Laravel Cloud + Boost). Fachlichkeit, Ticket-IDs und Priorisierung aus v1.3 bleiben unverändert. Ergänzt um **EquiFlow Time** für QR-basierte Mitglieder-Arbeitsdienste, Mitarbeiter-Arbeitszeiten und Übungsleiter-/Trainerstunden einschließlich Soll/Ist-Konten, Freigaben sowie Verknüpfung mit Events, People und Finance.


**Ticketplan für Programmierer und AI-Coding-Agents (Claude o. ä.)**  
Version 2.0 · strategisch priorisiert · August 2026

> **Arbeitsprinzip:** Nicht die komplette Software in einem einzigen Prompt erzeugen. Jedes Ticket wird einzeln implementiert, getestet und reviewed. Erst danach folgt das nächste Ticket.

## Inhaltsübersicht

- [1. Verbindliche technische Leitplanken](#1-verbindliche-technische-leitplanken)
- [2. Priorisierung und Arbeitsmodus](#2-priorisierung-und-arbeitsmodus)
- [3. Backlog-Übersicht](#3-backlog-übersicht)
- [4. Empfohlene Releases](#4-empfohlene-releases)
- [5. Detaillierte Tickets mit Cursor-Prompts](#5-detaillierte-tickets-mit-cursor-prompts)


| **Zweck**           | Umsetzbare Entwicklungsreihenfolge mit Akzeptanzkriterien und fertigen Cursor/Claude-Prompts |
|---------------------|----------------------------------------------------------------------------------------------|
| **Zielarchitektur** | Laravel 13 Monolith, Livewire 4, Flux UI 2, PostgreSQL/Eloquent, Laravel Cloud              |
| **Hosting**         | Laravel Cloud (Staging + Production), lokale Entwicklung via Laravel Herd                   |
| **Startmarkt**      | Reitvereine, Pensionsställe, Reitschulen, Pferdebetriebe                                    |
| **Strategie**       | Generischer Plattformkern + separates Pferdesport-Fachmodul                                 |
| **Arbeitsweise**    | Ein Ticket pro Agenten-Task, Review + Tests + Commit vor nächstem Ticket                    |


## Entfallene und ersetzte Tickets (v1.3 → v2.0)

| Altes Ticket | Grund des Entfalls | Ersatz / Abdeckung |
|---|---|---|
| DEV-002 Docker-Entwicklungsumgebung | Herd übernimmt lokale Entwicklung vollständig | Setup-Kriterien in DEV-001 integriert |
| OPS-001 Produktions-Docker-Compose + Reverse Proxy | Laravel Cloud (inkl. HTTPS, Skalierung) | Laravel-Cloud-Abschnitt in Leitplanken |
| OPS-002 Staging-Umgebung und Releaseprozess | Laravel Cloud Staging-Environment | Laravel-Cloud-Abschnitt in Leitplanken |
| OPS-003 Automatische DB-/Dateibackups | Laravel Cloud Backups (DB + Storage) | Laravel-Cloud-Abschnitt in Leitplanken |
| OPS-004 Monitoring, Logs und Alarmierung | Laravel Cloud Observability + Log-Drain | Laravel-Cloud-Abschnitt in Leitplanken |


# 1. Verbindliche technische Leitplanken

### Anwendung

Laravel 13 Monolith, PHP 8.4. Kein Monorepo. `declare(strict_types=1)` in allen PHP-Dateien. Code-Stil via Laravel Pint, statische Analyse via Larastan.

### Frontend

Livewire 4 Single-File-Components unter `resources/views/pages/**/⚡name.blade.php`. Flux UI 2 für UI-Bausteine. Tailwind CSS 4, Vite 8. Alpine.js für clientseitige Interaktionen. Mobile-first. Keine native App im MVP.

### Backend-Struktur

`app/Models`, `app/Actions`, `app/Policies`, `app/Concerns` (Validierungs-Traits). Routen in `routes/web.php` und bereichsspezifischen Routendateien. Livewire-first: keine allgemeine REST-API im MVP. Nur punktuelle HTTP-Endpunkte (z. B. Webhooks, öffentliche Kursanmeldung, Health-Check) als dedizierte Controller.

### Datenbank

PostgreSQL auf Laravel Cloud, SQLite lokal und in Tests. Eloquent ORM, ausschließlich versionierte Migrationen in `database/migrations`. Jede mandantenbezogene Fachentität trägt `organization_id` und wird über einen Global Scope abgesichert.

### Authentifizierung

Laravel Fortify: Login, Registrierung, Passwort-Reset, E-Mail-Verifikation, 2FA (TOTP + Recovery Codes), Passkeys. Session-Handling durch Laravel. Keine Eigenimplementierung von Auth-Flows.

### Dateien

`Storage`-Disks: `local` für Entwicklung/Tests, S3-kompatibler Cloud-Bucket für Staging und Production. DB speichert Metadaten, nicht Binärdateien direkt.

### Betrieb und Deployment (Laravel Cloud)

GitHub → Laravel Cloud mit getrennten Staging- und Production-Environments. Deploy-Hook führt `php artisan migrate --force` aus. Laravel Cloud übernimmt: HTTPS, horizontale Skalierung, DB-Backups, File-Storage, Queue-Worker, Scheduler, Log-Drain und Alerts. Kein VPS, kein Docker Compose, kein Reverse Proxy nötig.

### CI/CD

GitHub Actions: `composer ci:check` (Pint → Larastan → Pest). Migration-Check auf SQLite. Deployment auf Staging nur nach grünem CI; Deployment auf Production nur nach menschlicher Freigabe.

### Sicherheit

Tenant-Isolation (Global Scope) und Policies/Gates werden in jedem Ticket mitgetestet. Keine Secrets im Repository. Strukturierte Logs ohne sensible Inhalte. CSRF durch Laravel eingebaut. Rate Limiting via `RateLimiter`-Facade.

### Sprache

UI zunächst Deutsch. Nutzertexte über Laravel-Sprachdateien zentralisieren, damit spätere Internationalisierung möglich bleibt.

## 1.1 Verzeichnisstruktur

```text
app/
  Actions/          – Einzel-Aktionsklassen (z. B. CreateNewUser)
  Concerns/         – Wiederverwendbare Traits (Validierungsregeln)
  Http/
    Controllers/    – Nur für punktuelle HTTP-Endpunkte
    Middleware/
  Livewire/         – Livewire-Komponenten-Klassen (falls nicht als SFC)
  Models/
  Policies/
  Providers/
database/
  migrations/
  factories/
  seeders/
resources/
  views/
    layouts/        – App-Shell, Auth-Layouts
    pages/          – ⚡Livewire-SFC nach Bereich gegliedert
    components/     – Blade-Komponenten
Docu/
  architecture/     – ADRs (Architecture Decision Records)
routes/
  web.php
  console.php       – Scheduler
  [bereich].php     – Bereichsspezifische Routendateien
tests/
  Feature/
  Unit/
```

## 1.2 Entscheidungsregel für Cursor / Claude

Wenn ein Ticket eine Architekturentscheidung verlangt, die hier nicht festgelegt ist, soll der Agent **nicht stillschweigend entscheiden**, sondern die kleinste reversible Lösung wählen und die Entscheidung als ADR unter `Docu/architecture` dokumentieren. Dauerhafte Regeln werden per Boost `record-rule` in `.ai/rules` gespeichert.

# 2. Priorisierung und Arbeitsmodus

- P0 = zwingende Grundlage für Pilotbetrieb oder Sicherheit.

- P1 = MVP-relevant, sobald die P0-Abhängigkeiten erfüllt sind.

- P2 = nach Pilotstart bzw. wenn frühe Nutzer den Bedarf bestätigen.

- Jedes Ticket endet mit Tests, Dokumentationsupdate und manueller Kurzprüfung.

- Kein Ticket darf sachfremde Refactorings enthalten.

- Produktivdeployment, Security-Änderungen und Datenmigrationen benötigen menschliche Freigabe.


## 2.1 Produktprinzip für v1.3

EquiFlow wird nicht als reine Verwaltungssoftware entwickelt, sondern als **digitales Vereins- und Betriebsbüro**. Der langfristige Wettbewerbsvorteil soll aus der Kombination von Daten, Historie, geführten Prozessen, Automatisierung, Wirtschaftlichkeit und Service entstehen.

**Produktbereiche:**

- **EquiFlow Verein** – Mitglieder, Vorstand, Sitzungen, Beschlüsse, Übergaben
- **EquiFlow Betrieb** – Pferde, Boxen, Einsteller, Leistungen
- **EquiFlow Office** – Dokumente, Historie, Newsletter, Vorlagen, Portallinks
- **EquiFlow Workflows** – geführte Abläufe: was, wann, wo und mit welchen Unterlagen zu erledigen ist
- **EquiFlow Events** – Kurse, Anmeldung, Warteliste, Online-Zahlung, Kommunikation
- **EquiFlow People** – Personal, Trainer, Übungsleiter, Lizenzen und Einsatzorganisation
- **EquiFlow Finance** – BWA, Kostenstellen, Controlling, Steuerberater-Vorbereitung
- **EquiFlow Intelligence** – Organisationsgedächtnis, Health Scores, Forecasts und später Benchmarking

### 2.2 Vier Marktstufen

Die Stufen sind **keine starre Zeitplanung**, sondern Freigabeschwellen. Ein Ticket wird erst in die nächste Stufe gezogen, wenn die vorherige Stufe produktiv genutzt wird und echte Kundensignale vorliegen.

| Stufe | Ziel | Freigabekriterium | Entwicklungslogik |
|---|---|---|---|
| **A – Pilot-MVP** | 5–10 Vereine + 5–10 Betriebe produktiv einsetzen | Kernprozesse funktionieren sicher; Nutzer können ohne Entwicklerhilfe arbeiten | Nur Kauf-/Nutzungshindernisse und strategische Datenmodell-Grundlagen |
| **B – Nach 10 Kunden** | Wiederholbaren Nutzen und Zahlungsbereitschaft belegen | Mind. 10 zahlende, aktive Organisationen; wiederkehrende Wünsche sichtbar | Automatisierung, Events, Kommunikation, Steuerberater-Vorbereitung |
| **C – Nach 50 Kunden** | Differenzierung und Bindung verstärken | Mind. 50 zahlende Organisationen; Retention und Kernnutzung stabil | Vereinsgedächtnis, Health Scores, Beraterportal, People-Ausbau |
| **D – Später / Skalierung** | Daten- und Netzwerkvorteile aufbauen | ausreichende Datenbasis, Partner und klare Nachfrage | Forecast/Digital Twin, Benchmarking, Verbandsportal, direkte Connectoren |

### 2.3 Architektur jetzt vorbereiten, Features später aktivieren

Folgende Grundlagen müssen bereits im Pilot technisch berücksichtigt werden, auch wenn die sichtbaren Premiumfunktionen später kommen:

1. **Historisierung und Verknüpfungen:** Dokumente, Beschlüsse, Aufgaben, Personen, Zahlungen und Workflows benötigen stabile IDs, Zeitbezug und Audit-Historie.
2. **Workflow-Engine:** Prozessvorlagen dürfen nicht hart in einzelne Vereinsseiten programmiert werden.
3. **Payment-Abstraktion:** Zahlungsanbieter über ein Interface anbinden; PayPal ist ein Adapter, nicht die Domäne.
4. **Dokument-/Vorlagen-Engine:** Newsletter, Präsentationen, Beraterpakete und Berichte sollen auf denselben Template-Grundlagen aufbauen können.
5. **Benchmarking-Fähigkeit:** Kennzahlen müssen sauber definiert, versioniert und aggregierbar sein; individuelle Daten dürfen niemals ohne passende Rechtsgrundlage/Einwilligung in Benchmarks einfließen.
6. **Providerunabhängigkeit:** Hosting, Storage, E-Mail und Payments über Adapter kapseln.

### 2.4 Stage-Gate: Welche bestehenden Tickets wann gebaut werden

**A – Pilot-MVP (jetzt bauen):**

DEV-001, DEV-003–008, SEC-001–007, CORE-001–005, CLUB-001, CLUB-003, CLUB-007, CLUB-008, CLUB-010, EQ-001–006, FIN-004–007, MIG-001–005, OPS-005, PILOT-001.

**B – Nach 10 Kunden:**

CORE-006–007, CORE-004, CLUB-002, CLUB-004–006, CLUB-009, EQ-007, FIN-001–003, FIN-008, MIG-006, PILOT-002 sowie die neuen Office-/Workflow-/Event-/People-/Advisor-Tickets der Stufe B.

**C – Nach 50 Kunden:**

Ausbau von People, Organisationsgedächtnis, Health Scores, Beraterportal und Präsentations-/Reporting-Automatisierung.

**D – Später:**

MIG-007 (konkrete Connectoren nur nach realer Herkunftssoftware priorisieren), Forecast/Digital Twin, Benchmarking, Verbandsportal und weitere Vereinsarten.

# 3. Backlog-Übersicht

Empfohlene Reihenfolge: strikt nach Abhängigkeiten. P1-Tickets können verschoben werden, sofern keine spätere P0-Abhängigkeit verletzt wird.

| **ID**    | **Epic**                     | **Ticket**                                                  | **Prio** | **Abhängigkeit**                            | **Req.**                               |
|-----------|------------------------------|-------------------------------------------------------------|----------|---------------------------------------------|----------------------------------------|
| DEV-001   | E0 Fundament                 | Laravel-Projektbasis und Qualitätsregeln                                     | P0       | \-                                          | NFR-008, AIDEV-001, AIDEV-002          |
| DEV-003   | E0 Fundament                 | Eloquent-Migrationen, Factories und Seed-Framework          | P0       | DEV-001                                     | INF-004, OPS2-002, AIDEV-003           |
| DEV-004   | E0 Fundament                 | Anwendungsstruktur, Validierung und Fehlerkonventionen                                     | P0       | DEV-001                                     | API-Grundsätze, NFR-008                |
| DEV-005   | E0 Fundament                 | App-Shell und Designsystem-Grundlage (Flux UI)                             | P0       | DEV-001                                     | UX-001, UX-003, UX-004, UX-005         |
| DEV-006   | E0 Fundament                 | PWA-Basis installieren                                      | P0       | DEV-005                                     | GEN-004, UX-002, UX-006                |
| DEV-007   | E0 Fundament                 | GitHub-Actions-CI-Pipeline                                  | P0       | DEV-001, DEV-003                   | OPS2-001, NFR-008, AIDEV-002           |
| DEV-008   | E0 Fundament                 | Architektur- und Agenten-Dokumentation                      | P0       | DEV-001                                     | AIDEV-006, AIDEV-007                   |
| SEC-001   | E1 Auth, Tenant, Rechte      | Passwort-Authentifizierung und Sessions                     | P0       | DEV-003, DEV-004                            | CORE-001, SEC-003, SEC-005             |
| SEC-002   | E1 Auth, Tenant, Rechte      | Passwort-Reset und Einladungs-Token-Framework               | P0       | SEC-001                                     | CORE-001, CORE-009, SEC-002            |
| SEC-003   | E1 Auth, Tenant, Rechte      | Mandantenkontext und harte Tenant-Isolation                 | P0       | SEC-001                                     | TEN-001, SEC-001, A6                   |
| SEC-004   | E1 Auth, Tenant, Rechte      | RBAC und Berechtigungsmodell                                | P0       | SEC-003                                     | Role/Permission, SEC-002, SEC-010      |
| SEC-005   | E1 Auth, Tenant, Rechte      | Benutzereinladung und Organisationswechsel                  | P0       | SEC-002, SEC-004                            | CORE-009, TEN-002                      |
| SEC-006   | E1 Auth, Tenant, Rechte      | Audit-Log-Grundsystem                                       | P0       | SEC-003, SEC-004                            | CORE-007, SEC-008                      |
| SEC-007   | E1 Auth, Tenant, Rechte      | Rate Limiting, Security Header und CORS/CSRF-Basis          | P0       | SEC-001, DEV-004                            | SEC-006, SEC-005                       |
| CORE-001  | E2 Plattformkern             | Mandantenkonfiguration und Organisationstyp                 | P0       | SEC-004                                     | TEN-003, TEN-004, CORE-010             |
| CORE-002  | E2 Plattformkern             | Zentraler Personenstamm                                     | P0       | CORE-001                                    | CORE-003                               |
| CORE-003  | E2 Plattformkern             | Aufgabenmanagement                                          | P0       | CORE-002                                    | CORE-004                               |
| CORE-004  | E2 Plattformkern             | Wiederkehrende Aufgaben                                     | P0       | CORE-003                                    | CORE-004, BOARD-006                    |
| CORE-005  | E2 Plattformkern             | Dokument- und Storage-Grundsystem                           | P0       | CORE-002                                    | INF-005, SEC-007                       |
| CORE-006  | E2 Plattformkern             | Dokumentkategorien und Objektverknüpfungen                  | P1       | CORE-005                                    | VER-006, Dokumente                     |
| CORE-007  | E2 Plattformkern             | Benachrichtigungs-Inbox und E-Mail-Adapter                  | P1       | CORE-003, SEC-002                           | API-001, Benachrichtigungen            |
| CLUB-001  | E3 Vereins-MVP               | Mitgliederprofil und Mitgliederliste                        | P0       | CORE-002                                    | VER-001, VER-002                       |
| CLUB-002  | E3 Vereins-MVP               | Beitragsmodelle und Sollbeitrag                             | P1       | CLUB-001                                    | VER-003                                |
| CLUB-003  | E3 Vereins-MVP               | Vorstandspositionen und Amtszeiten                          | P0       | CORE-002                                    | BOARD-001                              |
| CLUB-004  | E3 Vereins-MVP               | Vorstandssitzungen und Tagesordnung                         | P0       | CLUB-003, CORE-005                          | BOARD-002                              |
| CLUB-005  | E3 Vereins-MVP               | Protokolle und Beschlüsse                                   | P0       | CLUB-004                                    | BOARD-003, BOARD-004                   |
| CLUB-006  | E3 Vereins-MVP               | Aufgabe aus Beschluss                                       | P0       | CLUB-005, CORE-003                          | BOARD-005                              |
| CLUB-007  | E3 Vereins-MVP               | Geführtes Vereins-Onboarding                                | P0       | CLUB-001, CLUB-003, CORE-005                | ONB-001, ONB-002, ONB-003              |
| CLUB-008  | E3 Vereins-MVP               | Vorstandswechsel-Workflow                                   | P0       | CLUB-003, CORE-003, CORE-005                | HAND-001 ff., A2                       |
| CLUB-009  | E3 Vereins-MVP               | Wiederkehrende Vorstandspflichten aus Vorlagen              | P1       | CORE-004, CLUB-003                          | BOARD-006                              |
| CLUB-010  | E3 Vereins-MVP               | Orientierungs- und Fachberatungshinweise                    | P0       | CORE-001                                    | GEN-001, GEN-002                       |
| EQ-001    | E4 Pferdebetriebs-MVP        | Pferdestammdaten und Besitzerbezug                          | P0       | CORE-002                                    | HORSE/Pferdeanforderungen              |
| EQ-002    | E4 Pferdebetriebs-MVP        | Stall-, Boxen- und Flächenstruktur                          | P0       | CORE-001                                    | STALL-Anforderungen                    |
| EQ-003    | E4 Pferdebetriebs-MVP        | Zeitbezogene Boxenbelegung                                  | P0       | EQ-001, EQ-002                              | Occupancy, A3                          |
| EQ-004    | E4 Pferdebetriebs-MVP        | Kunden-/Einstellerprofil und Pensionsvertrag                | P0       | CORE-002, EQ-003                            | CustomerContract, A3                   |
| EQ-005    | E4 Pferdebetriebs-MVP        | Leistungskatalog                                            | P0       | EQ-004                                      | Leistungen                             |
| EQ-006    | E4 Pferdebetriebs-MVP        | Mobile Leistungserfassung                                   | P0       | EQ-005, DEV-006                             | UX-005, Leistungen                     |
| EQ-007    | E4 Pferdebetriebs-MVP        | Fütterungsplan Basis                                        | P1       | EQ-001                                      | Fütterung                              |
| FIN-001   | E5 Abrechnung & Finanzen     | Rechnungsdomäne und Nummernkreise                           | P0       | CLUB-002, EQ-005                            | Rechnungen, GEN-003                    |
| FIN-002   | E5 Abrechnung & Finanzen     | Monatliche Rechnungsentwürfe aus Verträgen/Leistungen       | P0       | FIN-001, EQ-006                             | A4                                     |
| FIN-003   | E5 Abrechnung & Finanzen     | Rechnungsfreigabe, PDF und Zahlstatus                       | P0       | FIN-002, CORE-005                           | A4                                     |
| FIN-004   | E5 Abrechnung & Finanzen     | Finanzimport Upload und Parser-Rahmen                       | P0       | CORE-005                                    | BWA/XLS Import, A5                     |
| FIN-005   | E5 Abrechnung & Finanzen     | Konten- und Spaltenmapping                                  | P0       | FIN-004                                     | BWA Mapping                            |
| FIN-006   | E5 Abrechnung & Finanzen     | Kostenstellen und Finanzperioden                            | P0       | FIN-005                                     | Kostenstellen, Managementauswertungen  |
| FIN-007   | E5 Abrechnung & Finanzen     | Rollenbezogenes Finanzdashboard                             | P0       | FIN-006, CLUB-010                           | Dashboard, NFR-002                     |
| FIN-008   | E5 Abrechnung & Finanzen     | Betriebs-KPIs Boxenauslastung und einfache Szenarien        | P1       | FIN-007, EQ-003                             | Auslastung, Szenarien                  |
| MIG-001   | E6 Migration & Integrationen | Generisches Import-Framework und Adaptervertrag             | P0       | CORE-002, SEC-006                           | Migration, Import, Integrationen       |
| MIG-002   | E6 Migration & Integrationen | CSV/XLSX-Import Personen und Mitglieder                     | P0       | MIG-001, CLUB-002                           | Migration Mitglieder, Personen         |
| MIG-003   | E6 Migration & Integrationen | CSV/XLSX-Import Pferde, Boxen und Einsteller                | P0       | MIG-001, EQ-003, EQ-004                     | Migration Pferdebetrieb                |
| MIG-004   | E6 Migration & Integrationen | Spaltenmapping und wiederverwendbare Importprofile          | P0       | MIG-002, MIG-003                            | Migration Mapping                      |
| MIG-005   | E6 Migration & Integrationen | Dublettenprüfung, Importvorschau und Rollback               | P0       | MIG-004, SEC-006                            | Migration Sicherheit, Audit            |
| MIG-006   | E6 Migration & Integrationen | Vollständiger Datenexport für Anbieterwechsel und Sicherung | P0       | CORE-005, CLUB-002, EQ-004, FIN-001         | Datenportabilität, Export              |
| MIG-007   | E6 Migration & Integrationen | Connector-SDK für spätere Fremdsoftware- und API-Adapter    | P1       | MIG-001, MIG-004                            | Integrationen, API-Adapter             |
| OPS-005   | E7 Betrieb & Pilot           | DSGVO-Basis: Datenexport und Lösch-/Anonymisierungsworkflow | P0       | CORE-002, CORE-005, SEC-006                 | SEC-009, SEC-010                       |
| PILOT-001 | E7 Betrieb & Pilot           | Demo-/Pilotdaten und End-to-End-Abnahme A1-A7               | P0       | CLUB-010, EQ-006, FIN-007, MIG-006, OPS-002 | A1-A7                                  |
| PILOT-002 | E7 Betrieb & Pilot           | Pilot-Feedback, Feature-Telemetrie und Fehlerkanal          | P1       | PILOT-001                                   | Pilotbetrieb                           |


## 3.1 Neue Tickets in v1.3 – Office, Workflows, Events, People, Advisor & Intelligence

| ID | Produktbereich | Ticket | Stufe | Prio | Abhängigkeit |
|---|---|---|---|---|---|
| OFF-001 | Office | Strukturierte Vereins-/Betriebsakte mit Metadaten | A | P0 | CORE-005, SEC-006 |
| OFF-002 | Office | Versionshistorie und stabile Dokumentverknüpfungen | A | P0 | OFF-001 |
| WF-001 | Workflows | Generische Workflow-Template-Engine | A | P0 | CORE-003, CORE-005 |
| WF-002 | Workflows | Geführter Prozess-Runner mit Schrittstatus | A | P0 | WF-001 |
| EVT-001 | Events | Event-/Kurs-Domänenmodell | A | P1 | CORE-002 |
| EVT-003 | Events | PaymentProvider-Abstraktion | A | P0 | DEV-004, SEC-006 |
| PEO-001 | People | Personal-/Trainer-/Übungsleiter-Grundmodell | A | P1 | CORE-002, CORE-005 |
| INT-001 | Intelligence | Historien-/Relation-Layer für Organisationsgedächtnis | A | P0 | SEC-006, OFF-002, CLUB-005 |
| INT-006 | Intelligence | KPI-Definitionen und Benchmarking-fähiges Messmodell | A | P1 | FIN-006, FIN-007 |
| OFF-003 | Office | Volltextsuche über Dokumentmetadaten und Historie | B | P1 | OFF-002 |
| OFF-004 | Office | Zentrale Template-Engine für Dokumente und Kommunikation | B | P1 | OFF-002 |
| OFF-005 | Office | Newsletter-Composer mit Empfängersegmenten | B | P1 | OFF-004, CORE-007, CLUB-001 |
| OFF-007 | Office | Kuratierte Portal-/Linkbibliothek mit Prozessbezug | B | P1 | WF-001 |
| WF-003 | Workflows | Vereinsjahreskalender aus Workflow-Vorlagen | B | P1 | WF-001, CORE-004 |
| WF-004 | Workflows | Unterlagen, Portale und Nachweise je Workflow-Schritt | B | P1 | WF-002, OFF-001, OFF-007 |
| WF-005 | Workflows | Erinnerungen, Fristen und Eskalationen | B | P1 | WF-002, CORE-007 |
| EVT-002 | Events | Öffentliche Kursanmeldung, Kapazität und Warteliste | B | P1 | EVT-001 |
| EVT-004 | Events | PayPal Checkout, Webhooks und Zahlungsabgleich | B | P1 | EVT-003, EVT-002 |
| EVT-005 | Events | Automatische Bestätigung, Erinnerung und Storno-Workflow | B | P1 | EVT-002, EVT-004, CORE-007 |
| PEO-002 | People | Qualifikationen, Lizenzen und Ablaufwarnungen | B | P1 | PEO-001, CORE-007 |
| ADV-001 | Finance/Advisor | Steuerberater-Paket auf Knopfdruck | B | P1 | FIN-007, OFF-001, FIN-003 |
| OFF-006 | Office | Präsentations-/Jahresbericht-Generator | C | P2 | OFF-004, FIN-007, CLUB-005 |
| PEO-003 | People | Einsatzplanung, Verfügbarkeit und Vertretungen | C | P2 | PEO-001 |
| PEO-004 | People | Vergütungs-/Abrechnungsgrundlagen und Export | C | P2 | PEO-003, FIN-001 |
| ADV-002 | Finance/Advisor | Beschränkter Steuerberater-/Beraterzugang | C | P2 | ADV-001, SEC-004, SEC-005 |
| INT-002 | Intelligence | Organisationsgedächtnis: kontextuelle Suche über Historie | C | P2 | INT-001, OFF-003 |
| INT-003 | Intelligence | Organisations-/Betriebs-Health-Score Engine | C | P2 | INT-006, WF-005 |
| INT-004 | Intelligence | Nachfolgefähigkeits-/Übergabesicherheits-Score | C | P2 | INT-003, CLUB-008, INT-001 |
| INT-005 | Intelligence | Forecast / Digitaler Zwilling mit Szenario-Modell | D | P2 | FIN-008, INT-006 |
| INT-007 | Intelligence | Anonymisiertes Branchenbenchmarking | D | P2 | INT-006, OPS-005 |
| PART-001 | Partner | Verbands-/Partnerportal mit freigegebenen Vorlagen | D | P2 | WF-001, SEC-004 |
| MIG-008 | Migration | Direkte Connectoren zu priorisierten Fremdsystemen | D | P2 | MIG-007 |

> **Hinweis:** Stufe A bedeutet bei INT-001/INT-006 vor allem Datenmodell und technische Grundlage, nicht bereits eine umfangreiche KI- oder Benchmarking-Oberfläche.

# 4. Empfohlene Releases

## R0 – Fundament & Sicherheit

Tickets: DEV-001, DEV-003, DEV-004, DEV-005, DEV-006, DEV-007, DEV-008, SEC-001, SEC-002, SEC-003, SEC-004, SEC-005, SEC-006, SEC-007

## R1 – Gemeinsamer Kern

Tickets: CORE-001, CORE-002, CORE-003, CORE-004, CORE-005, CORE-006, CORE-007

## R2 – Vereins-MVP

Tickets: CLUB-001, CLUB-002, CLUB-003, CLUB-004, CLUB-005, CLUB-006, CLUB-007, CLUB-008, CLUB-009, CLUB-010

## R3 – Pferdebetriebs-MVP

Tickets: EQ-001, EQ-002, EQ-003, EQ-004, EQ-005, EQ-006, EQ-007

## R4 – Abrechnung & Controlling

Tickets: FIN-001, FIN-002, FIN-003, FIN-004, FIN-005, FIN-006, FIN-007, FIN-008

## R5 – Migration & Portabilität

Tickets: MIG-001, MIG-002, MIG-003, MIG-004, MIG-005, MIG-006, MIG-007

## R6 – Pilotfähig

Tickets: OPS-005, PILOT-001, PILOT-002


## 4.1 Marktgetriebene Release-Roadmap v1.3

### A – Pilot-MVP

**Ziel:** Ein Verein und ein Pferdebetrieb können ihre Kernorganisation real abbilden, Daten migrieren, mobil arbeiten und wirtschaftliche Basisdaten importieren. Gleichzeitig sind Historie, Workflow-Engine und Payment-Abstraktion technisch vorbereitet.

**Nicht erforderlich im Pilot:** kompletter Newsletter-Versand, echte PayPal-Zahlung, Präsentationsgenerator, Health Score, Benchmarking.

### B – Nach 10 zahlenden Kunden

**Ziel:** Manuelle Routinearbeit sichtbar reduzieren. Priorität haben Funktionen, die heute Excel, Word, Mailprogramme, PayPal-Handarbeit und das manuelle Zusammenstellen von Unterlagen ersetzen.

Schwerpunkte: Newsletter, Kursanmeldung + PayPal, Workflow-Vorlagen, Portallinks, Lizenzwarnungen, Steuerberater-Paket.

### C – Nach 50 zahlenden Kunden

**Ziel:** EquiFlow wird schwer austauschbar, weil die Organisation ihre Historie, Prozesse und Zusammenarbeit im System aufbaut.

Schwerpunkte: Organisationsgedächtnis, Health Scores, Nachfolgefähigkeit, Beraterportal, Präsentationsgenerator, People-Ausbau.

### D – Später / Skalierung

**Ziel:** Daten- und Netzwerkeffekte.

Schwerpunkte: Forecast/Digital Twin, datenschutzkonformes Benchmarking, Verbandsportal, direkte Fremdsoftware-Connectoren nach echter Nachfrage, Öffnung für weitere Sport- und Vereinsarten.

# 5. Detaillierte Tickets mit Cursor-Prompts

Die Prompts sind absichtlich auf ein einzelnes Ticket begrenzt. Vor jedem Prompt sollte der Agent Zugriff auf AGENTS.md/CLAUDE.md, die .ai/rules und die unmittelbar relevanten Dateien erhalten.

## DEV-001 – Laravel-Projektbasis und Qualitätsregeln

| **Epic**           | E0 Fundament | **Priorität**  | P0                            |
|--------------------|--------------|----------------|-------------------------------|
| **Abhängigkeiten** | \-           | **Lastenheft** | NFR-008, AIDEV-001, AIDEV-002 |

### Ziel

Ein reproduzierbares Laravel-Projekt mit etablierten Qualitätsregeln, lokaler Herd-Entwicklungsumgebung und grundlegender CI-Struktur aufsetzen.

### Scope

- Überprüfung und Dokumentation der Herd-Setup-Schritte (composer install, php artisan key:generate, npm install, composer run dev)

- `.env.example` vollständig und ohne Secrets (SQLite lokal, PostgreSQL für Laravel Cloud)

- Pint-Konfiguration prüfen und `composer lint` / `composer ci:check` lauffähig machen

- Larastan-Konfiguration (Level, ignores) dokumentieren

- Pest Basis-Smoke-Test (Application läuft, Dashboard erreichbar für eingeloggten User)

- AGENTS.md/CLAUDE.md vorhanden und aktuell (Stack, Konventionen, Commit-Regeln)

### Akzeptanzkriterien

- `composer install && php artisan migrate` auf frischem Checkout funktioniert mit SQLite

- `composer ci:check` (Pint + Larastan + Pest) läuft grün

- Herd-App ist lokal unter .test-Domain erreichbar

- Kein Secret im Repository

### Pflichttests

- Smoke-Test: GET / und GET /dashboard (auth required)

- `composer ci:check` als CI-fähiges Skript

### Cursor-Prompt

```text
Implementiere Ticket DEV-001 – Laravel-Projektbasis und Qualitätsregeln.
Ziel: Ein reproduzierbares Laravel-Projekt mit Qualitätsregeln und lokaler Herd-Entwicklungsumgebung.
Abhängigkeiten: -. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Herd-Setup-Dokumentation; .env.example ohne Secrets (SQLite lokal); Pint und Larastan konfiguriert; composer ci:check lauffähig; Pest Basis-Smoke-Tests.
Akzeptanzkriterien: composer install + php artisan migrate funktioniert; composer ci:check grün; Herd lokal erreichbar; keine Secrets im Repo.
Tests: GET / und GET /dashboard Smoke-Test; composer ci:check.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## DEV-003 – Eloquent-Migrationen, Factories und Seed-Framework

| **Epic**           | E0 Fundament | **Priorität**  | P0                        |
|--------------------|--------------|----------------|---------------------------|
| **Abhängigkeiten** | DEV-001      | **Lastenheft** | AIDEV-003 |

### Ziel

Versionierte Datenbankbasis mit sauberem Migrations- und Seed-Prozess etablieren.

### Scope

- Erste Basistabellen: `organizations`, `organization_memberships` (User ist bereits vorhanden via Fortify)

- Eloquent-Migrationen mit `php artisan make:migration`

- Factory für jede neue Entität

- Idempotenter Development-Seeder (`DatabaseSeeder` erzeugt Demo-Organisation und Admin)

- UUID-Strategie dokumentieren (Laravel `Str::uuid()` vs. Auto-Increment; Entscheidung als ADR)

### Akzeptanzkriterien

- Frische SQLite-DB kann vollständig migriert werden (`php artisan migrate --seed`)

- Seeder erzeugt Demo-Organisation und Admin-User

- Zweiter Seeder-Lauf erzeugt keine unkontrollierten Dubletten

### Pflichttests

- Migration auf frischer SQLite-DB (in CI)

- Seeder-Integration-Test via Pest

### Cursor-Prompt

```text
Implementiere Ticket DEV-003 – Eloquent-Migrationen, Factories und Seed-Framework.
Ziel: Versionierte Datenbankbasis mit sauberem Migrations- und Seed-Prozess etablieren.
Abhängigkeiten: DEV-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Basistabellen organizations und organization_memberships; Eloquent-Migrationen; Factory je Entität; idempotenter DatabaseSeeder; UUID-Strategie als ADR dokumentieren.
Akzeptanzkriterien: php artisan migrate --seed auf frischer SQLite-DB funktioniert; Seed erzeugt Demo-Organisation und Admin; kein Duplikat bei zweitem Lauf.
Tests: Migration auf frischer DB; Seeder-Integration-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## DEV-004 – Anwendungsstruktur, Validierung und Fehlerkonventionen

| **Epic**           | E0 Fundament | **Priorität**  | P0            |
|--------------------|--------------|----------------|---------------|
| **Abhängigkeiten** | DEV-001      | **Lastenheft** | AIDEV-001     |

### Ziel

Einheitliche Laravel-Anwendungsstruktur mit klaren Konventionen für Validierung, Fehlerbehandlung und Autorisierung schaffen.

### Scope

- Konvention für Livewire-Validierung (Validation-Traits in `app/Concerns/` nach Muster `ProfileValidationRules`)

- Einheitliches Fehler-Layout in Blade/Flux (Fehlermeldungen, leere Zustände, Loading-States)

- Handler-Konfiguration in `bootstrap/app.php` für strukturierte JSON-Antworten bei API-Routen

- Middleware-Gruppe für mandantenbezogene Routen (Active-Organization-Middleware)

- Konvention für Actions (`app/Actions/`) dokumentieren

### Akzeptanzkriterien

- Validierungsfehler werden einheitlich über Flux-Komponenten dargestellt

- Unautorisierte Zugriffe liefern Redirect zu Login bzw. 403

- Middleware-Gruppe `tenant` ist konfiguriert und in .ai/rules dokumentiert

### Pflichttests

- Pest-Test: nicht eingeloggter User wird zu Login umgeleitet

- Pest-Test: eingeloggter User ohne Organisation erhält definierte Fehlerseite

### Cursor-Prompt

```text
Implementiere Ticket DEV-004 – Anwendungsstruktur, Validierung und Fehlerkonventionen.
Ziel: Einheitliche Laravel-Anwendungsstruktur mit klaren Konventionen für Validierung, Fehlerbehandlung und Autorisierung schaffen.
Abhängigkeiten: DEV-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Validierungs-Trait-Konvention; einheitliches Fehler-/Leer-/Loading-Layout; Handler-Konfiguration; Active-Organization-Middleware; Actions-Konvention.
Akzeptanzkriterien: Validierungsfehler einheitlich dargestellt; unautorisierte Zugriffe korrekt behandelt; tenant-Middleware konfiguriert.
Tests: Redirect-Test für nicht eingeloggten User; Fehlerseiten-Test ohne Organisation.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## DEV-005 – App-Shell und Designsystem-Grundlage (Flux UI)

| **Epic**           | E0 Fundament | **Priorität**  | P0                             |
|--------------------|--------------|----------------|--------------------------------|
| **Abhängigkeiten** | DEV-001      | **Lastenheft** | UX-001, UX-003, UX-004, UX-005 |

### Ziel

App-ähnliche responsive Grundshell mit Flux-UI-Bausteinen und Mobile-first-Navigation erstellen.

### Scope

- Desktop Sidebar (`resources/views/layouts/app/sidebar.blade.php`) + mobile Bottom Navigation

- Header mit Organisation-Switcher-Platzhalter (`resources/views/layouts/app/header.blade.php`)

- Flux-Bausteine in Layouts: `<flux:button>`, `<flux:input>`, `<flux:select>`, `<flux:modal>`, `<flux:table>`

- Touch-Targets (min. 44px) und responsive Breakpoints via Tailwind 4

- Loading/Empty/Error states als Blade-Partials

### Akzeptanzkriterien

- 360px Kernlayout ohne horizontalen Scroll

- Navigation per Touch bedienbar (44px Touch-Targets)

- Demo-Route (`/dev/ui`) zeigt alle Flux-Bausteine (nur in development)

### Pflichttests

- Livewire-Komponenten-Test für Navigation (Sidebar Toggle)

- Pest Browser Smoke-Test: Dashboard bei 360px renderbar

### Cursor-Prompt

```text
Implementiere Ticket DEV-005 – App-Shell und Designsystem-Grundlage (Flux UI).
Ziel: App-ähnliche responsive Grundshell mit Flux-UI-Bausteinen erstellen.
Abhängigkeiten: DEV-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Desktop Sidebar + mobile Bottom Navigation; Header/Organisation-Switcher-Platzhalter; Flux-Bausteine (Button, Input, Select, Modal, Table); Touch-Targets; Loading/Empty/Error states.
Akzeptanzkriterien: 360px ohne horizontalen Scroll; Touch-Navigation bedienbar; Demo-Route vorhanden.
Tests: Livewire Navigation-Test; Pest Browser Smoke bei 360px.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## DEV-006 – PWA-Basis installieren

| **Epic**           | E0 Fundament | **Priorität**  | P0                      |
|--------------------|--------------|----------------|-------------------------|
| **Abhängigkeiten** | DEV-005      | **Lastenheft** | GEN-004, UX-002, UX-006 |

### Ziel

Web-App als installierbare mobile PWA mit Standalone-Erlebnis bereitstellen.

### Scope

- Web App Manifest (`manifest.json` via Vite/Blade)

- App-Icons (Platzhalter PNG)

- Service Worker (Vite-Plugin oder manuell): App Shell Cache, Offline-Fallback-Seite

- Offline-Hinweis in der UI bei fehlender Verbindung

- `theme_color` und `background_color` konfiguriert

### Akzeptanzkriterien

- Installierbar auf unterstützten Browsern (Chrome Android, Safari iOS)

- Standalone-Modus ohne Browser-Chrome nutzbar

- Bei Offline keine stillen Schreibverluste (Livewire-Fehler sichtbar)

### Pflichttests

- Pest-Test: Manifest-Route liefert korrektes JSON

- Pest Browser: Offline-Fallback-Seite wird angezeigt

### Cursor-Prompt

```text
Implementiere Ticket DEV-006 – PWA-Basis installieren.
Ziel: Web-App als installierbare mobile PWA bereitstellen.
Abhängigkeiten: DEV-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Web App Manifest; App-Icons Platzhalter; Service Worker mit App Shell Cache und Offline-Fallback; Offline-Hinweis in der UI; theme_color und background_color.
Akzeptanzkriterien: installierbar auf Chrome Android und Safari iOS; Standalone-Modus; keine stillen Schreibverluste bei Offline.
Tests: Manifest-Route-Test; Pest Browser Offline-Fallback-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## DEV-007 – GitHub-Actions-CI-Pipeline

| **Epic**           | E0 Fundament    | **Priorität**  | P0                   |
|--------------------|-----------------|----------------|----------------------|
| **Abhängigkeiten** | DEV-001, DEV-003 | **Lastenheft** | AIDEV-002            |

### Ziel

Jeder Pull Request soll automatisch auf Qualität und Buildbarkeit geprüft werden.

### Scope

- GitHub Actions Workflow (`.github/workflows/ci.yml`)

- Jobs: `composer install → pint → larastan → pest` auf SQLite

- Migrationsprüfung (`php artisan migrate --no-interaction`)

- Vite-Build-Check (`npm ci && npm run build`)

- Fehler-Artifacts (Log-Ausgabe bei Fehlern)

### Akzeptanzkriterien

- PR mit Larastan-Fehler schlägt fehl

- Migrationen laufen in CI auf frischer SQLite-DB

- Grüner Main-Branch ist Voraussetzung für Laravel-Cloud-Deploy

### Pflichttests

- Pipeline selbst durch Test-PR verifizieren (Pint-Verletzung → rot, Fix → grün)

### Cursor-Prompt

```text
Implementiere Ticket DEV-007 – GitHub-Actions-CI-Pipeline.
Ziel: Jeder Pull Request soll automatisch auf Qualität und Buildbarkeit geprüft werden.
Abhängigkeiten: DEV-001, DEV-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: .github/workflows/ci.yml; Jobs: composer install, pint, larastan, pest auf SQLite; php artisan migrate --no-interaction; npm ci && npm run build; Fehler-Log-Artifact.
Akzeptanzkriterien: Larastan-Fehler bricht CI ab; Migrationen laufen auf frischer DB; grüner Main ist Deploy-Voraussetzung.
Tests: Pipeline-Verifikation per Test-PR.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## DEV-008 – Architektur- und Agenten-Dokumentation

| **Epic**           | E0 Fundament | **Priorität**  | P0                   |
|--------------------|--------------|----------------|----------------------|
| **Abhängigkeiten** | DEV-001      | **Lastenheft** | AIDEV-006, AIDEV-007 |

### Ziel

Coding-Agents erhalten kurze, verbindliche Regeln statt das gesamte Lastenheft in jedem Prompt.

### Scope

- AGENTS.md/CLAUDE.md auf aktuellen Laravel-Stack aktualisieren

- Architekturübersicht in `Docu/architecture/overview.md`

- Ticket-DoD (Definition of Done)

- Security-Checkliste (Tenant, Policy, Secrets, Logs)

- ADR-Template unter `Docu/architecture/adr-template.md`

- Commit/PR-Konvention

- `.ai/rules` Basisregeln via Boost `record-rule` anlegen

### Akzeptanzkriterien

- Neuer Entwickler versteht Setup aus README + AGENTS.md

- Agentenregeln verbieten ungeprüfte Prod-Deployments und sachfremde Refactorings

- `.ai/rules/index.md` vorhanden

### Pflichttests

- Dokumentationsreview (manuell)

### Cursor-Prompt

```text
Implementiere Ticket DEV-008 – Architektur- und Agenten-Dokumentation.
Ziel: Coding-Agents erhalten kurze, verbindliche Regeln statt das gesamte Lastenheft in jedem Prompt.
Abhängigkeiten: DEV-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: AGENTS.md/CLAUDE.md aktuell; Docu/architecture/overview.md; Ticket-DoD; Security-Checkliste; ADR-Template; Commit/PR-Konvention; .ai/rules via Boost record-rule.
Akzeptanzkriterien: Neuer Entwickler versteht Setup; Agentenregeln verbieten ungeprüfte Deployments; .ai/rules/index.md vorhanden.
Tests: Dokumentationsreview.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-001 – Authentifizierung mit Laravel Fortify

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0                         |
|--------------------|-------------------------|----------------|----------------------------|
| **Abhängigkeiten** | DEV-003, DEV-004        | **Lastenheft** | CORE-001, SEC-003, SEC-005 |

### Ziel

Sicheren Login, Logout und serverseitig widerrufbare Sessions über Laravel Fortify implementieren und sichern.

### Scope

- Fortify-Konfiguration in `config/fortify.php` prüfen (Features: Login, Registration, Reset, E-Mail-Verifikation)

- `app/Actions/Fortify/CreateNewUser.php` und `ResetUserPassword.php` auf Projektanforderungen abstimmen

- Laravel Session-Cookie-Einstellungen (HttpOnly, SameSite, Secure) in `config/session.php`

- Passwort-Hashing über Laravel Standard (bcrypt/Argon2id via `config/hashing.php`)

- Logout: Session invalidieren + CSRF-Token regenerieren

- E-Mail-Verifikation aktivieren falls gewünscht (Feature::emailVerification())

### Akzeptanzkriterien

- Kein Passwort im Klartext in DB oder Logs

- Logout invalidiert Session serverseitig

- Nicht eingeloggter Zugriff auf geschützte Route → Redirect zu Login

- Session-Cookie ist HttpOnly und SameSite=Lax

### Pflichttests

- Pest: Login mit korrekten/falschen Credentials

- Pest: Logout invalidiert Session

- Pest: Geschützte Route nach Logout nicht erreichbar

### Cursor-Prompt

```text
Implementiere Ticket SEC-001 – Authentifizierung mit Laravel Fortify.
Ziel: Sicheren Login, Logout und serverseitig widerrufbare Sessions über Laravel Fortify implementieren.
Abhängigkeiten: DEV-003, DEV-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Fortify-Konfiguration (Features); FortifyServiceProvider; CreateNewUser/ResetUserPassword Actions; Session-Cookie-Einstellungen; Passwort-Hashing; E-Mail-Verifikation.
Akzeptanzkriterien: kein Passwort im Klartext; Logout invalidiert Session; nicht eingeloggt → Login-Redirect; Cookie HttpOnly/SameSite.
Tests: Login/Logout Pest-Tests; Session-Ungültigkeitstest.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-002 – Passwort-Reset und Einladungs-Token-Framework

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0                          |
|--------------------|-------------------------|----------------|-----------------------------|
| **Abhängigkeiten** | SEC-001                 | **Lastenheft** | CORE-001, CORE-009, SEC-002 |

### Ziel

Zeitlich begrenzte Single-Use-Tokens für Passwort-Reset und spätere Einladungen bereitstellen.

### Scope

- Fortify Passwort-Reset-Flow aktivieren und testen (gespeicherte Token sind gehasht in `password_reset_tokens`)

- Generische Response gegen User Enumeration (Fortify-Standard prüfen und ggf. absichern)

- Mailable für Passwort-Reset (Laravel `ResetPassword` Notification anpassen)

- Dev-Mail via SMTP/log-Driver in `.env.example` dokumentiert

- Einladungs-Token-Grundlage: wiederverwendbares `TokenModel`-Muster mit `expires_at` und `used_at` für SEC-005

### Akzeptanzkriterien

- Token nur einmal gültig

- Abgelaufenes Token wird abgelehnt

- Reset invalidiert alte Sessions (Fortify-Standard)

- Response bei unbekannter E-Mail nicht unterscheidbar von Erfolg

### Pflichttests

- Pest: Token Expiry und Single-Use

- Pest: Enumeration-safe Response-Test

### Cursor-Prompt

```text
Implementiere Ticket SEC-002 – Passwort-Reset und Einladungs-Token-Framework.
Ziel: Zeitlich begrenzte Single-Use-Tokens für Passwort-Reset und spätere Einladungen bereitstellen.
Abhängigkeiten: SEC-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Fortify Passwort-Reset-Flow; password_reset_tokens gehasht; Mailable/Notification anpassen; Dev-Mail Log-Driver; Einladungs-Token-Grundlage mit expires_at/used_at.
Akzeptanzkriterien: Token nur einmal gültig; abgelaufenes Token abgelehnt; Reset invalidiert Session; Enumeration-safe Response.
Tests: Token-Expiry/Single-Use-Test; Enumeration-safe-Response-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-003 – Mandantenkontext und harte Tenant-Isolation

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0                   |
|--------------------|-------------------------|----------------|----------------------|
| **Abhängigkeiten** | SEC-001                 | **Lastenheft** | TEN-001, SEC-001, A6 |

### Ziel

Jede fachliche Anfrage sicher an den aktiven Mandanten binden.

### Scope

- `ActiveOrganization`-Middleware setzt aktive Organisation pro Request (aus Session oder URL)

- Eloquent Global Scope `BelongsToOrganization` für alle mandantenbezogenen Modelle

- `organization_id` Pflichtfeld in allen Fachentitäten (DB-Level NOT NULL + Constraint)

- 404/403 Verhalten ohne Datenleck (kein Unterschied zwischen "nicht vorhanden" und "fremder Mandant")

- Konvention in `.ai/rules` dokumentiert: jedes neue Modell mit `organization_id` muss Global Scope verwenden

### Akzeptanzkriterien

- Manipulierte Fremd-ID liefert keinerlei Fremddaten (404)

- Automatisierter A/B-Mandantentest: User von Org A kann Daten von Org B nicht abrufen

- Kein mandantenbezogenes Model ohne `BelongsToOrganization`-Scope

### Pflichttests

- Pest: Cross-Tenant-Zugriffstest (IDOR-Simulation)

- Pest: Negative IDOR-Tests für alle Basis-Ressourcen

### Cursor-Prompt

```text
Implementiere Ticket SEC-003 – Mandantenkontext und harte Tenant-Isolation.
Ziel: Jede fachliche Anfrage sicher an den aktiven Mandanten binden.
Abhängigkeiten: SEC-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: ActiveOrganization-Middleware; Eloquent Global Scope BelongsToOrganization; organization_id NOT NULL in Migrationen; 404 ohne Datenleck; Konvention in .ai/rules.
Akzeptanzkriterien: Fremd-ID → 404; A/B-Mandantentest grün; kein Modell ohne Global Scope.
Tests: Cross-Tenant-Zugriffstest; negative IDOR-Tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-004 – RBAC und Berechtigungsmodell

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0                                |
|--------------------|-------------------------|----------------|-----------------------------------|
| **Abhängigkeiten** | SEC-003                 | **Lastenheft** | Role/Permission, SEC-002, SEC-010 |

### Ziel

Rollen und feingranulare Permissions je Organisation durchsetzen.

### Scope

- `Role`- und `Permission`-Modelle mit Migrations und Factories

- `RolePermission`-Pivot; `OrganizationMembership` trägt `role_id`

- Seeder für Standardrollen (admin, treasurer, member, staff)

- Laravel Policies/Gates pro Fachdomäne (deny by default)

- Blade-Direktive `@can` / Flux-Bedingungen für UI-Sichtbarkeit

- Rollen-Prüfung ist serverseitig, UI-Ausblenden ist nur UX

### Akzeptanzkriterien

- Schatzmeister kann Finanzrecht erhalten ohne Benutzerverwaltung

- Staff sieht keine Finanzmodule ohne Permission (UI und serverseitig)

- Policy-Verletzung → 403, keine Datenleckage

### Pflichttests

- Pest: Permission-Matrix-Unit-Tests

- Pest: Negative Autorisierungstests (403 ohne Permission)

### Cursor-Prompt

```text
Implementiere Ticket SEC-004 – RBAC und Berechtigungsmodell.
Ziel: Rollen und feingranulare Permissions je Organisation durchsetzen.
Abhängigkeiten: SEC-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Role/Permission/RolePermission Modelle + Migrations + Factories; OrganizationMembership mit role_id; Seeder Standardrollen; Laravel Policies/Gates; @can in Blade; deny by default.
Akzeptanzkriterien: Treasurer erhält Finanzrecht ohne User-Management; Staff ohne Permission sieht keine Finanzmodule; Policy-Verletzung → 403.
Tests: Permission-Matrix-Unit-Tests; negative Autorisierungstests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-005 – Benutzereinladung und Organisationswechsel

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0                |
|--------------------|-------------------------|----------------|-------------------|
| **Abhängigkeiten** | SEC-002, SEC-004        | **Lastenheft** | CORE-009, TEN-002 |

### Ziel

Admins können Nutzer in eine Organisation einladen; Mehrfachzugehörige wechseln ohne neuen Login.

### Scope

- `Invitation`-Modell (Token aus SEC-002, `expires_at`, `used_at`, `role_id`)

- Livewire-Komponente: Einladung erstellen + Einladungs-Mail (Mailable)

- Einladung annehmen: `OrganizationMembership` anlegen, Rolle vergeben

- Org-Switcher im Header: aktive Organisation in Session speichern, `ActiveOrganization`-Middleware aktualisiert Rechte sofort

- Abgelaufene/wiederverwendete Einladungen korrekt ablehnen

### Akzeptanzkriterien

- Einladung ist zeitlich begrenzt und single-use

- Wechsel aktualisiert Rechte sofort (kein Re-Login nötig)

- User ohne Membership kann Organisation nicht aktivieren

### Pflichttests

- Pest: Invite-Integration-Tests (create/accept/expire/reuse)

- Pest: Multi-Org-Switch-Test

### Cursor-Prompt

```text
Implementiere Ticket SEC-005 – Benutzereinladung und Organisationswechsel.
Ziel: Admins können Nutzer einladen; Mehrfachzugehörige wechseln ohne Re-Login.
Abhängigkeiten: SEC-002, SEC-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Invitation-Modell mit Token/expires_at/used_at/role_id; Livewire Einladungsformular; Mailable; Einladung annehmen → OrganizationMembership; Org-Switcher in Session; expired/reused ablehnen.
Akzeptanzkriterien: Einladung zeitlich begrenzt + single-use; Wechsel aktualisiert Rechte sofort; kein Zugriff ohne Membership.
Tests: Invite-Integration-Tests; Multi-Org-Switch-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-006 – Audit-Log-Grundsystem

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0                |
|--------------------|-------------------------|----------------|-------------------|
| **Abhängigkeiten** | SEC-003, SEC-004        | **Lastenheft** | CORE-007, SEC-008 |

### Ziel

Sensible Aktionen unveränderbar nachvollziehbar protokollieren.

### Scope

- `AuditLog`-Modell: `actor_id`, `organization_id`, `action`, `subject_type`, `subject_id`, `metadata` (JSON allowlist), `created_at`

- Eloquent Model-Observer als zentraler Einstiegspunkt für Schreib-Events

- `AuditLogger`-Action-Klasse für manuelle Ereignisse (Login, Rollenänderung)

- `metadata`-Allowlist: Passwörter, IBAN-Vollwerte, sensible Daten werden nie gespeichert

- Read-only Livewire-Ansicht für Admin (Policy: nur Admin)

### Akzeptanzkriterien

- Rollenänderung und Login werden protokolliert

- Normale Nutzer können Audit-Einträge nicht ändern oder löschen

- Keine Passwörter/IBAN-Vollwerte im Audit-Metadata

### Pflichttests

- Pest: Audit-Write-Tests (Observer + manuell)

- Pest: Redaction-Test (sensible Felder nicht in Metadata)

### Cursor-Prompt

```text
Implementiere Ticket SEC-006 – Audit-Log-Grundsystem.
Ziel: Sensible Aktionen unveränderbar nachvollziehbar protokollieren.
Abhängigkeiten: SEC-003, SEC-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: AuditLog-Modell mit actor/org/action/subject/metadata; Eloquent Model-Observer; AuditLogger-Action; metadata Allowlist (keine Passwörter/IBANs); read-only Admin Livewire-Ansicht.
Akzeptanzkriterien: Rollenänderung + Login protokolliert; normale Nutzer können nicht ändern/löschen; keine sensiblen Werte in Metadata.
Tests: Audit-Write-Tests; Redaction-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## SEC-007 – Rate Limiting, Security Header und CSRF-Basis

| **Epic**           | E1 Auth, Tenant, Rechte | **Priorität**  | P0               |
|--------------------|-------------------------|----------------|------------------|
| **Abhängigkeiten** | SEC-001, DEV-004        | **Lastenheft** | SEC-006, SEC-005 |

### Ziel

Häufige Web-Angriffsflächen im Grundsystem reduzieren.

### Scope

- Login-Rate-Limit via `RateLimiter`-Facade in `AppServiceProvider` (Fortify nutzt dies bereits)

- Security-Header-Middleware (X-Content-Type-Options, X-Frame-Options, Referrer-Policy) in `bootstrap/app.php`

- CSRF: Laravel CSRF-Schutz ist eingebaut; Livewire integriert es automatisch; dokumentieren für AJAX-Routen

- Request-Body-Limits via Webserver-Konfiguration (dokumentieren, kein Code)

- Security-Header-Konfiguration in `.ai/rules` festhalten

### Akzeptanzkriterien

- Brute-force-Simulation auf Login-Route wird Rate-Limited (429)

- Security-Header sind in HTTP-Response vorhanden

- CSRF-Schutz für alle State-ändernden Anfragen dokumentiert und aktiv

### Pflichttests

- Pest: Rate-Limit-Integration-Test (429 nach N Versuchen)

- Pest: Security-Header-Response-Test

### Cursor-Prompt

```text
Implementiere Ticket SEC-007 – Rate Limiting, Security Header und CSRF-Basis.
Ziel: Häufige Web-Angriffsflächen im Grundsystem reduzieren.
Abhängigkeiten: SEC-001, DEV-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Login-Rate-Limit via RateLimiter-Facade; Security-Header-Middleware; CSRF dokumentieren; .ai/rules-Eintrag für Security-Header.
Akzeptanzkriterien: Login → 429 nach N Versuchen; Security-Header in Response; CSRF aktiv und dokumentiert.
Tests: Rate-Limit-Integration-Test; Security-Header-Response-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-001 – Mandantenkonfiguration und Organisationstyp

| **Epic**           | E2 Plattformkern | **Priorität**  | P0                         |
|--------------------|------------------|----------------|----------------------------|
| **Abhängigkeiten** | SEC-004          | **Lastenheft** | TEN-003, TEN-004, CORE-010 |

### Ziel

Verein, Betrieb oder Mischform konfigurierbar machen und Module per Feature Flag steuern.

### Scope

- Organization settings

- organizationType

- FeatureFlag/ModuleConfig

- Admin settings UI

- Navigation anhand aktivierter Module

### Akzeptanzkriterien

- deaktiviertes Modul fehlt in UI

- API des deaktivierten Moduls blockiert fachliche Nutzung

- Änderungen nur im eigenen Mandanten

### Pflichttests

- feature flag tests

- settings e2e

### Cursor-Prompt

```text
Implementiere Ticket CORE-001 – Mandantenkonfiguration und Organisationstyp.
Ziel: Verein, Betrieb oder Mischform konfigurierbar machen und Module per Feature Flag steuern.
Abhängigkeiten: SEC-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Organization settings; organizationType; FeatureFlag/ModuleConfig; Admin settings UI; Navigation anhand aktivierter Module.
Akzeptanzkriterien: deaktiviertes Modul fehlt in UI; API des deaktivierten Moduls blockiert fachliche Nutzung; Änderungen nur im eigenen Mandanten.
Tests: feature flag tests; settings e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-002 – Zentraler Personenstamm

| **Epic**           | E2 Plattformkern | **Priorität**  | P0       |
|--------------------|------------------|----------------|----------|
| **Abhängigkeiten** | CORE-001         | **Lastenheft** | CORE-003 |

### Ziel

Eine Person soll mehrere fachliche Rollen ohne Dublette besitzen können.

### Scope

- Person CRUD

- Kontaktfelder

- Beziehungen zu User/Member/Customer später

- Archivierung

- Suche/Filter

### Akzeptanzkriterien

- gleiche Person kann mehrere Profile tragen

- archivierte Person standardmäßig ausgeblendet

- Tenant-Isolation

### Pflichttests

- CRUD integration

- archive test

- cross-tenant test

### Cursor-Prompt

```text
Implementiere Ticket CORE-002 – Zentraler Personenstamm.
Ziel: Eine Person soll mehrere fachliche Rollen ohne Dublette besitzen können.
Abhängigkeiten: CORE-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Person CRUD; Kontaktfelder; Beziehungen zu User/Member/Customer später; Archivierung; Suche/Filter.
Akzeptanzkriterien: gleiche Person kann mehrere Profile tragen; archivierte Person standardmäßig ausgeblendet; Tenant-Isolation.
Tests: CRUD integration; archive test; cross-tenant test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-003 – Aufgabenmanagement

| **Epic**           | E2 Plattformkern | **Priorität**  | P0       |
|--------------------|------------------|----------------|----------|
| **Abhängigkeiten** | CORE-002         | **Lastenheft** | CORE-004 |

### Ziel

Allgemeine Aufgaben als zentrales Organisationsobjekt implementieren.

### Scope

- Task CRUD

- assignee

- due date

- priority/status

- links to domain objects

- filtering

- mobile quick create

### Akzeptanzkriterien

- Aufgabe in max. 3 Interaktionsschritten erfassbar

- delegier- und abschließbar

- Filter nach Status/Verantwortlichem

### Pflichttests

- service tests

- mobile e2e quick create

### Cursor-Prompt

```text
Implementiere Ticket CORE-003 – Aufgabenmanagement.
Ziel: Allgemeine Aufgaben als zentrales Organisationsobjekt implementieren.
Abhängigkeiten: CORE-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Task CRUD; assignee; due date; priority/status; links to domain objects; filtering; mobile quick create.
Akzeptanzkriterien: Aufgabe in max. 3 Interaktionsschritten erfassbar; delegier- und abschließbar; Filter nach Status/Verantwortlichem.
Tests: service tests; mobile e2e quick create.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-004 – Wiederkehrende Aufgaben

| **Epic**           | E2 Plattformkern | **Priorität**  | P0                  |
|--------------------|------------------|----------------|---------------------|
| **Abhängigkeiten** | CORE-003         | **Lastenheft** | CORE-004, BOARD-006 |

### Ziel

Regelmäßige Vereins- und Betriebsaufgaben zuverlässig erzeugen.

### Scope

- recurrence rule subset

- scheduler/job service

- next occurrence

- idempotency key

- pause/end

### Akzeptanzkriterien

- kein doppeltes Erzeugen bei erneutem Joblauf

- monatliche/jährliche Regeln funktionieren

- Jobfehler werden geloggt

### Pflichttests

- scheduler unit tests

- idempotency integration

### Cursor-Prompt

```text
Implementiere Ticket CORE-004 – Wiederkehrende Aufgaben.
Ziel: Regelmäßige Vereins- und Betriebsaufgaben zuverlässig erzeugen.
Abhängigkeiten: CORE-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: recurrence rule subset; scheduler/job service; next occurrence; idempotency key; pause/end.
Akzeptanzkriterien: kein doppeltes Erzeugen bei erneutem Joblauf; monatliche/jährliche Regeln funktionieren; Jobfehler werden geloggt.
Tests: scheduler unit tests; idempotency integration.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-005 – Dokument- und Storage-Grundsystem

| **Epic**           | E2 Plattformkern | **Priorität**  | P0               |
|--------------------|------------------|----------------|------------------|
| **Abhängigkeiten** | CORE-002         | **Lastenheft** | INF-005, SEC-007 |

### Ziel

Mandantensichere Dokumentablage mit austauschbarem Speicher schaffen.

### Scope

- `Document`- und `DocumentVersion`-Modelle mit Migrations und Factories

- Laravel Storage-Disk: `local` (Entwicklung/Tests), `s3` (Produktion via Laravel Cloud); Konfiguration in `config/filesystems.php`

- Upload/Download/Delete via `Storage`-Facade; Datei-Keys zufällig (kein rateerbarer Pfad)

- MIME-Typ- und Größen-Whitelist (Validierung im Action/Controller)

- Signed Temporary URLs für Download (kein direkter Public-Pfad)

### Akzeptanzkriterien

- Fremdmandant kann Datei nicht laden (Policy + Signed URL)

- Datei nicht direkt ausführbar (kein Content-Disposition: inline für gefährliche MIME-Typen)

- Metadaten bleiben in DB, Bytes im Storage-Disk

### Pflichttests

- Pest: Upload-Security-Test (MIME-Whitelist, Größe)

- Pest: Cross-Tenant-File-Test (Fremdmandant erhält 403)

### Cursor-Prompt

```text
Implementiere Ticket CORE-005 – Dokument- und Storage-Grundsystem.
Ziel: Mandantensichere Dokumentablage mit austauschbarem Speicher schaffen.
Abhängigkeiten: CORE-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Document/DocumentVersion Modelle; Laravel Storage-Disks (local/s3); zufällige Datei-Keys; MIME/Größen-Whitelist; Signed Temporary URLs für Download.
Akzeptanzkriterien: Fremdmandant → 403; keine direkte Ausführbarkeit; Metadaten in DB, Bytes in Storage.
Tests: Upload-Security-Test; Cross-Tenant-File-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-006 – Dokumentkategorien und Objektverknüpfungen

| **Epic**           | E2 Plattformkern | **Priorität**  | P1                 |
|--------------------|------------------|----------------|--------------------|
| **Abhängigkeiten** | CORE-005         | **Lastenheft** | VER-006, Dokumente |

### Ziel

Dokumente flexibel Personen, Verein, Pferden, Verträgen und Sitzungen zuordnen.

### Scope

- DocumentLink polymorphic/safe relation strategy

- categories

- list by object

- mobile upload

### Akzeptanzkriterien

- Dokument erscheint am verknüpften Objekt

- Berechtigung folgt Objekt/Modul

- Kamera/Dateiauswahl mobil möglich soweit Browser unterstützt

### Pflichttests

- link permission tests

- upload e2e

### Cursor-Prompt

```text
Implementiere Ticket CORE-006 – Dokumentkategorien und Objektverknüpfungen.
Ziel: Dokumente flexibel Personen, Verein, Pferden, Verträgen und Sitzungen zuordnen.
Abhängigkeiten: CORE-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: DocumentLink polymorphic/safe relation strategy; categories; list by object; mobile upload.
Akzeptanzkriterien: Dokument erscheint am verknüpften Objekt; Berechtigung folgt Objekt/Modul; Kamera/Dateiauswahl mobil möglich soweit Browser unterstützt.
Tests: link permission tests; upload e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CORE-007 – Benachrichtigungs-Inbox und E-Mail-Versand

| **Epic**           | E2 Plattformkern  | **Priorität**  | P1                          |
|--------------------|-------------------|----------------|-----------------------------|
| **Abhängigkeiten** | CORE-003, SEC-002 | **Lastenheft** | API-001, Benachrichtigungen |

### Ziel

In-App-Benachrichtigungen und konfigurierbare E-Mail-Zustellung bereitstellen.

### Scope

- Laravel Notifications: `database`-Channel für In-App-Inbox (`notifications`-Tabelle), `mail`-Channel für E-Mail

- Livewire-Benachrichtigungs-Inbox-Komponente (read/unread)

- Mail-Treiber über `MAIL_MAILER` in `.env` konfigurierbar (log für Dev, SMTP/SES für Prod)

- Notification via Queue dispatchen (Job-Bus), damit Mailfehler Fachtransaktion nicht bricht

- Aufgaben-Fälligkeits-Hook als erster konkreter Anwendungsfall

### Akzeptanzkriterien

- Mail-Treiber austauschbar über Config (kein hart codierter Provider)

- Benutzer sieht nur eigene Benachrichtigungen

- Mail-Fehler bricht Fachtransaktion nicht (Queue-Job mit Retry)

### Pflichttests

- Pest: Notification-Dispatch-Test (fakes Queue)

- Pest: Privacy-Test (User sieht nur eigene Notifications)

### Cursor-Prompt

```text
Implementiere Ticket CORE-007 – Benachrichtigungs-Inbox und E-Mail-Versand.
Ziel: In-App-Benachrichtigungen und konfigurierbare E-Mail-Zustellung bereitstellen.
Abhängigkeiten: CORE-003, SEC-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Laravel Notifications (database + mail Channel); Livewire Inbox-Komponente; Mail-Treiber via MAIL_MAILER konfigurierbar; Queue-Dispatch; Aufgaben-Fälligkeits-Hook.
Akzeptanzkriterien: Treiber austauschbar; User sieht nur eigene Notifications; Mailfehler bricht Transaktion nicht.
Tests: Notification-Dispatch-Test; Privacy-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-001 – Mitgliederprofil und Mitgliederliste

| **Epic**           | E3 Vereins-MVP | **Priorität**  | P0               |
|--------------------|----------------|----------------|------------------|
| **Abhängigkeiten** | CORE-002       | **Lastenheft** | VER-001, VER-002 |

### Ziel

Person um vereinsspezifische Mitgliedsdaten erweitern.

### Scope

- MemberProfile

- member number

- entry/exit/status/category

- list/detail UI

- search/filter

- archive

### Akzeptanzkriterien

- Mitglied anlegen/ändern/archivieren

- Nummer innerhalb Mandant eindeutig

- Person bleibt ohne Dublette nutzbar

### Pflichttests

- member CRUD

- unique constraint test

- e2e list/detail

### Cursor-Prompt

```text
Implementiere Ticket CLUB-001 – Mitgliederprofil und Mitgliederliste.
Ziel: Person um vereinsspezifische Mitgliedsdaten erweitern.
Abhängigkeiten: CORE-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: MemberProfile; member number; entry/exit/status/category; list/detail UI; search/filter; archive.
Akzeptanzkriterien: Mitglied anlegen/ändern/archivieren; Nummer innerhalb Mandant eindeutig; Person bleibt ohne Dublette nutzbar.
Tests: member CRUD; unique constraint test; e2e list/detail.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-002 – Beitragsmodelle und Sollbeitrag

| **Epic**           | E3 Vereins-MVP | **Priorität**  | P1      |
|--------------------|----------------|----------------|---------|
| **Abhängigkeiten** | CLUB-001       | **Lastenheft** | VER-003 |

### Ziel

Beitragsarten und berechneten Sollbeitrag je Mitglied abbilden.

### Scope

- ContributionPlan

- billing interval

- discount/manual override

- member assignment

- annual/monthly calculation

### Akzeptanzkriterien

- Sollbeitrag deterministisch berechnet

- Planänderung wirkt nicht rückwirkend ohne explizite Regel

- Beträge als Decimal

### Pflichttests

- money calculation unit tests

- assignment integration

### Cursor-Prompt

```text
Implementiere Ticket CLUB-002 – Beitragsmodelle und Sollbeitrag.
Ziel: Beitragsarten und berechneten Sollbeitrag je Mitglied abbilden.
Abhängigkeiten: CLUB-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: ContributionPlan; billing interval; discount/manual override; member assignment; annual/monthly calculation.
Akzeptanzkriterien: Sollbeitrag deterministisch berechnet; Planänderung wirkt nicht rückwirkend ohne explizite Regel; Beträge als Decimal.
Tests: money calculation unit tests; assignment integration.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-003 – Vorstandspositionen und Amtszeiten

| **Epic**           | E3 Vereins-MVP | **Priorität**  | P0        |
|--------------------|----------------|----------------|-----------|
| **Abhängigkeiten** | CORE-002       | **Lastenheft** | BOARD-001 |

### Ziel

Aktuelle und historische Vorstandsbesetzungen mit Verantwortungen abbilden.

### Scope

- BoardPosition template/custom

- BoardTerm

- start/end

- deputy/responsibilities

- current/history UI

### Akzeptanzkriterien

- historische Amtszeit bleibt erhalten

- eine Position kann Nachfolger erhalten

- Rechte müssen nicht automatisch identisch mit Funktion sein

### Pflichttests

- term overlap rules tests

- history e2e

### Cursor-Prompt

```text
Implementiere Ticket CLUB-003 – Vorstandspositionen und Amtszeiten.
Ziel: Aktuelle und historische Vorstandsbesetzungen mit Verantwortungen abbilden.
Abhängigkeiten: CORE-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: BoardPosition template/custom; BoardTerm; start/end; deputy/responsibilities; current/history UI.
Akzeptanzkriterien: historische Amtszeit bleibt erhalten; eine Position kann Nachfolger erhalten; Rechte müssen nicht automatisch identisch mit Funktion sein.
Tests: term overlap rules tests; history e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-004 – Vorstandssitzungen und Tagesordnung

| **Epic**           | E3 Vereins-MVP     | **Priorität**  | P0        |
|--------------------|--------------------|----------------|-----------|
| **Abhängigkeiten** | CLUB-003, CORE-005 | **Lastenheft** | BOARD-002 |

### Ziel

Sitzungen von Vorbereitung bis Abschluss strukturieren.

### Scope

- Meeting

- participants

- agenda items

- attachments

- draft/planned/completed

- detail UI

### Akzeptanzkriterien

- Agenda sortierbar

- Anhänge berechtigt abrufbar

- Statusübergänge validiert

### Pflichttests

- meeting lifecycle tests

- attachment permission test

### Cursor-Prompt

```text
Implementiere Ticket CLUB-004 – Vorstandssitzungen und Tagesordnung.
Ziel: Sitzungen von Vorbereitung bis Abschluss strukturieren.
Abhängigkeiten: CLUB-003, CORE-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Meeting; participants; agenda items; attachments; draft/planned/completed; detail UI.
Akzeptanzkriterien: Agenda sortierbar; Anhänge berechtigt abrufbar; Statusübergänge validiert.
Tests: meeting lifecycle tests; attachment permission test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-005 – Protokolle und Beschlüsse

| **Epic**           | E3 Vereins-MVP | **Priorität**  | P0                   |
|--------------------|----------------|----------------|----------------------|
| **Abhängigkeiten** | CLUB-004       | **Lastenheft** | BOARD-003, BOARD-004 |

### Ziel

TOPs, Protokolltext und dauerhafte Beschlüsse dokumentieren.

### Scope

- minutes sections

- approval status

- Resolution with number/text/date/vote

- search/list within meeting

- export-ready data

### Akzeptanzkriterien

- Beschluss eindeutig nummeriert je Organisation/Jahr

- später zur Sitzung auffindbar

- Freigabestatus nachvollziehbar

### Pflichttests

- resolution numbering tests

- meeting-resolution e2e

### Cursor-Prompt

```text
Implementiere Ticket CLUB-005 – Protokolle und Beschlüsse.
Ziel: TOPs, Protokolltext und dauerhafte Beschlüsse dokumentieren.
Abhängigkeiten: CLUB-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: minutes sections; approval status; Resolution with number/text/date/vote; search/list within meeting; export-ready data.
Akzeptanzkriterien: Beschluss eindeutig nummeriert je Organisation/Jahr; später zur Sitzung auffindbar; Freigabestatus nachvollziehbar.
Tests: resolution numbering tests; meeting-resolution e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-006 – Aufgabe aus Beschluss

| **Epic**           | E3 Vereins-MVP     | **Priorität**  | P0        |
|--------------------|--------------------|----------------|-----------|
| **Abhängigkeiten** | CLUB-005, CORE-003 | **Lastenheft** | BOARD-005 |

### Ziel

Beschlüsse direkt in verantwortete Aufgaben überführen.

### Scope

- create task from resolution action

- bidirectional link

- assignee/due date prefill

### Akzeptanzkriterien

- Aufgabe und Beschluss bleiben verknüpft

- Löschen/Archivieren zerstört Historie nicht

### Pflichttests

- link integration test

- e2e create-from-resolution

### Cursor-Prompt

```text
Implementiere Ticket CLUB-006 – Aufgabe aus Beschluss.
Ziel: Beschlüsse direkt in verantwortete Aufgaben überführen.
Abhängigkeiten: CLUB-005, CORE-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: create task from resolution action; bidirectional link; assignee/due date prefill.
Akzeptanzkriterien: Aufgabe und Beschluss bleiben verknüpft; Löschen/Archivieren zerstört Historie nicht.
Tests: link integration test; e2e create-from-resolution.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-007 – Geführtes Vereins-Onboarding

| **Epic**           | E3 Vereins-MVP               | **Priorität**  | P0                        |
|--------------------|------------------------------|----------------|---------------------------|
| **Abhängigkeiten** | CLUB-001, CLUB-003, CORE-005 | **Lastenheft** | ONB-001, ONB-002, ONB-003 |

### Ziel

Neue Vereine schrittweise bis zu einem nutzbaren System führen.

### Scope

- wizard progress

- organization basics

- board setup

- upload statutes/regulations

- task template activation

- resume later

### Akzeptanzkriterien

- Fortschritt gespeichert

- Schritte überspring-/fortsetzbar nach Regeln

- nach Abschluss Dashboard nutzbar

### Pflichttests

- wizard state tests

- onboarding e2e

### Cursor-Prompt

```text
Implementiere Ticket CLUB-007 – Geführtes Vereins-Onboarding.
Ziel: Neue Vereine schrittweise bis zu einem nutzbaren System führen.
Abhängigkeiten: CLUB-001, CLUB-003, CORE-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: wizard progress; organization basics; board setup; upload statutes/regulations; task template activation; resume later.
Akzeptanzkriterien: Fortschritt gespeichert; Schritte überspring-/fortsetzbar nach Regeln; nach Abschluss Dashboard nutzbar.
Tests: wizard state tests; onboarding e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-008 – Vorstandswechsel-Workflow

| **Epic**           | E3 Vereins-MVP               | **Priorität**  | P0               |
|--------------------|------------------------------|----------------|------------------|
| **Abhängigkeiten** | CLUB-003, CORE-003, CORE-005 | **Lastenheft** | HAND-001 ff., A2 |

### Ziel

Vorstandswechsel als geführten, nachvollziehbaren Übergabeprozess abbilden.

### Scope

- start handover old/new person/position/date

- checklist sections finance/contracts/access/tasks/contacts

- linked documents/tasks

- progress

- completion report

- audit hooks

### Akzeptanzkriterien

- Status und Prozentfortschritt korrekt

- offene Punkte sichtbar

- Abschluss protokolliert und historisch einsehbar

- keine Passwörter als Klartextfeld

### Pflichttests

- workflow state tests

- permissions

- full A2 e2e

### Cursor-Prompt

```text
Implementiere Ticket CLUB-008 – Vorstandswechsel-Workflow.
Ziel: Vorstandswechsel als geführten, nachvollziehbaren Übergabeprozess abbilden.
Abhängigkeiten: CLUB-003, CORE-003, CORE-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: start handover old/new person/position/date; checklist sections finance/contracts/access/tasks/contacts; linked documents/tasks; progress; completion report; audit hooks.
Akzeptanzkriterien: Status und Prozentfortschritt korrekt; offene Punkte sichtbar; Abschluss protokolliert und historisch einsehbar; keine Passwörter als Klartextfeld.
Tests: workflow state tests; permissions; full A2 e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-009 – Wiederkehrende Vorstandspflichten aus Vorlagen

| **Epic**           | E3 Vereins-MVP     | **Priorität**  | P1        |
|--------------------|--------------------|----------------|-----------|
| **Abhängigkeiten** | CORE-004, CLUB-003 | **Lastenheft** | BOARD-006 |

### Ziel

Typische Aufgaben pro Vorstandsrolle als editierbare Orientierung bereitstellen.

### Scope

- template library seeded

- organization can customize

- activate per position

- recurring tasks generated

- disclaimer metadata

### Akzeptanzkriterien

- Vorlagen sind änderbar

- keine Darstellung als verbindliche Rechts-/Steuerpflicht

- Aufgaben werden korrekt erzeugt

### Pflichttests

- template customization tests

- recurrence tests

### Cursor-Prompt

```text
Implementiere Ticket CLUB-009 – Wiederkehrende Vorstandspflichten aus Vorlagen.
Ziel: Typische Aufgaben pro Vorstandsrolle als editierbare Orientierung bereitstellen.
Abhängigkeiten: CORE-004, CLUB-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: template library seeded; organization can customize; activate per position; recurring tasks generated; disclaimer metadata.
Akzeptanzkriterien: Vorlagen sind änderbar; keine Darstellung als verbindliche Rechts-/Steuerpflicht; Aufgaben werden korrekt erzeugt.
Tests: template customization tests; recurrence tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## CLUB-010 – Orientierungs- und Fachberatungshinweise

| **Epic**           | E3 Vereins-MVP | **Priorität**  | P0               |
|--------------------|----------------|----------------|------------------|
| **Abhängigkeiten** | CORE-001       | **Lastenheft** | GEN-001, GEN-002 |

### Ziel

Rechtliche/steuerliche Funktionen mit klarer Abgrenzung versehen.

### Scope

- central Disclaimer component

- context types legal/tax/general

- persistent finance hint

- workflow hint where sensitive

- admin cannot globally remove mandatory disclaimer

### Akzeptanzkriterien

- Finanzdashboard zeigt Steuerberater-Hinweis

- Vorstands-/rechtliche Checklisten zeigen Orientierungshinweis

- Text behauptet keine verbindliche Prüfung

### Pflichttests

- UI component tests

- snapshot/content checks

### Cursor-Prompt

```text
Implementiere Ticket CLUB-010 – Orientierungs- und Fachberatungshinweise.
Ziel: Rechtliche/steuerliche Funktionen mit klarer Abgrenzung versehen.
Abhängigkeiten: CORE-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: central Disclaimer component; context types legal/tax/general; persistent finance hint; workflow hint where sensitive; admin cannot globally remove mandatory disclaimer.
Akzeptanzkriterien: Finanzdashboard zeigt Steuerberater-Hinweis; Vorstands-/rechtliche Checklisten zeigen Orientierungshinweis; Text behauptet keine verbindliche Prüfung.
Tests: UI component tests; snapshot/content checks.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-001 – Pferdestammdaten und Besitzerbezug

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P0                        |
|--------------------|-----------------------|----------------|---------------------------|
| **Abhängigkeiten** | CORE-002              | **Lastenheft** | HORSE/Pferdeanforderungen |

### Ziel

Pferde als Fachmodul mit Besitzer-/Personenbezug verwalten.

### Scope

- Horse CRUD

- name, birth, sex, breed, life/chip ids optional

- owner relations

- documents link

- archive

### Akzeptanzkriterien

- Pferd anlegbar und Besitzer zuordenbar

- mehrere Besitzer möglich falls modelliert

- archivierte Pferde bleiben historisch referenzierbar

### Pflichttests

- horse CRUD

- owner relation

- tenant isolation

### Cursor-Prompt

```text
Implementiere Ticket EQ-001 – Pferdestammdaten und Besitzerbezug.
Ziel: Pferde als Fachmodul mit Besitzer-/Personenbezug verwalten.
Abhängigkeiten: CORE-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Horse CRUD; name, birth, sex, breed, life/chip ids optional; owner relations; documents link; archive.
Akzeptanzkriterien: Pferd anlegbar und Besitzer zuordenbar; mehrere Besitzer möglich falls modelliert; archivierte Pferde bleiben historisch referenzierbar.
Tests: horse CRUD; owner relation; tenant isolation.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-002 – Stall-, Boxen- und Flächenstruktur

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P0                  |
|--------------------|-----------------------|----------------|---------------------|
| **Abhängigkeiten** | CORE-001              | **Lastenheft** | STALL-Anforderungen |

### Ziel

Physische Stallstruktur innerhalb einer Organisation abbilden.

### Scope

- Stable

- Box

- Area/Paddock optional minimal

- box status free/occupied/reserved/blocked

- mobile list + desktop grid

### Akzeptanzkriterien

- Boxstatus eindeutig

- Stallstruktur mandantensicher

- freie/belegte Boxen schnell filterbar

### Pflichttests

- CRUD/status tests

- responsive UI e2e

### Cursor-Prompt

```text
Implementiere Ticket EQ-002 – Stall-, Boxen- und Flächenstruktur.
Ziel: Physische Stallstruktur innerhalb einer Organisation abbilden.
Abhängigkeiten: CORE-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Stable; Box; Area/Paddock optional minimal; box status free/occupied/reserved/blocked; mobile list + desktop grid.
Akzeptanzkriterien: Boxstatus eindeutig; Stallstruktur mandantensicher; freie/belegte Boxen schnell filterbar.
Tests: CRUD/status tests; responsive UI e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-003 – Zeitbezogene Boxenbelegung

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P0            |
|--------------------|-----------------------|----------------|---------------|
| **Abhängigkeiten** | EQ-001, EQ-002        | **Lastenheft** | Occupancy, A3 |

### Ziel

Pferd und Box über historisierbare Belegung verbinden.

### Scope

- Occupancy start/end

- one active occupancy per horse

- one active occupancy per box

- move horse action

- history

### Akzeptanzkriterien

- keine Doppelbelegung gleichzeitig

- Umzug beendet alte und startet neue Belegung atomar

- Historie bleibt erhalten

### Pflichttests

- overlap constraint tests

- move transaction integration

### Cursor-Prompt

```text
Implementiere Ticket EQ-003 – Zeitbezogene Boxenbelegung.
Ziel: Pferd und Box über historisierbare Belegung verbinden.
Abhängigkeiten: EQ-001, EQ-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Occupancy start/end; one active occupancy per horse; one active occupancy per box; move horse action; history.
Akzeptanzkriterien: keine Doppelbelegung gleichzeitig; Umzug beendet alte und startet neue Belegung atomar; Historie bleibt erhalten.
Tests: overlap constraint tests; move transaction integration.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-004 – Kunden-/Einstellerprofil und Pensionsvertrag

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P0                   |
|--------------------|-----------------------|----------------|----------------------|
| **Abhängigkeiten** | CORE-002, EQ-003      | **Lastenheft** | CustomerContract, A3 |

### Ziel

Einsteller, Pferd, Box und Vertragsdaten verbinden.

### Scope

- CustomerProfile or role relation

- CustomerContract

- start/end

- base service/price

- horse/occupancy links

- status

### Akzeptanzkriterien

- aktiver Vertrag eindeutig sichtbar

- Vertragsende verändert Historie nicht

- Beträge Decimal

### Pflichttests

- contract lifecycle tests

- A3 partial e2e

### Cursor-Prompt

```text
Implementiere Ticket EQ-004 – Kunden-/Einstellerprofil und Pensionsvertrag.
Ziel: Einsteller, Pferd, Box und Vertragsdaten verbinden.
Abhängigkeiten: CORE-002, EQ-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: CustomerProfile or role relation; CustomerContract; start/end; base service/price; horse/occupancy links; status.
Akzeptanzkriterien: aktiver Vertrag eindeutig sichtbar; Vertragsende verändert Historie nicht; Beträge Decimal.
Tests: contract lifecycle tests; A3 partial e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-005 – Leistungskatalog

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P0         |
|--------------------|-----------------------|----------------|------------|
| **Abhängigkeiten** | EQ-004                | **Lastenheft** | Leistungen |

### Ziel

Grund- und Zusatzleistungen wiederverwendbar definieren.

### Scope

- ServiceCatalogItem

- price

- tax metadata informational/configurable only

- recurrence one-time/monthly

- active/archive

### Akzeptanzkriterien

- Leistung kann Verträgen/Kunden zugeordnet werden

- Preisänderung ändert historische Buchungen nicht rückwirkend

### Pflichttests

- service price snapshot tests

- CRUD

### Cursor-Prompt

```text
Implementiere Ticket EQ-005 – Leistungskatalog.
Ziel: Grund- und Zusatzleistungen wiederverwendbar definieren.
Abhängigkeiten: EQ-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: ServiceCatalogItem; price; tax metadata informational/configurable only; recurrence one-time/monthly; active/archive.
Akzeptanzkriterien: Leistung kann Verträgen/Kunden zugeordnet werden; Preisänderung ändert historische Buchungen nicht rückwirkend.
Tests: service price snapshot tests; CRUD.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-006 – Mobile Leistungserfassung

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P0                 |
|--------------------|-----------------------|----------------|--------------------|
| **Abhängigkeiten** | EQ-005, DEV-006       | **Lastenheft** | UX-005, Leistungen |

### Ziel

Zusatzleistungen im Stall mit wenigen Taps erfassen.

### Scope

- quick add by customer/horse

- recent/favorite services

- date/qty/note

- draft/confirmed state

### Akzeptanzkriterien

- Standardleistung in max. 3 Interaktionsschritten nach Bereichsöffnung erfassbar

- keine Doppelbuchung durch Double Tap/Retry

### Pflichttests

- Pest Browser: Mobile Livewire-Flow bei 390px Viewport

- Pest: Idempotency-Test (Double-Submit-Schutz)

### Cursor-Prompt

```text
Implementiere Ticket EQ-006 – Mobile Leistungserfassung.
Ziel: Zusatzleistungen im Stall mit wenigen Taps erfassen.
Abhängigkeiten: EQ-005, DEV-006. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: quick add by customer/horse; recent/favorite services; date/qty/note; draft/confirmed state.
Akzeptanzkriterien: Standardleistung in max. 3 Interaktionsschritten nach Bereichsöffnung erfassbar; keine Doppelbuchung durch Double Tap/Retry.
Tests: Pest Browser Mobile Livewire-Flow bei 390px; Idempotency-Test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## EQ-007 – Fütterungsplan Basis

| **Epic**           | E4 Pferdebetriebs-MVP | **Priorität**  | P1        |
|--------------------|-----------------------|----------------|-----------|
| **Abhängigkeiten** | EQ-001                | **Lastenheft** | Fütterung |

### Ziel

Strukturierten Fütterungsplan pro Pferd hinterlegen.

### Scope

- FeedPlan

- time slots

- feed item + quantity/unit

- notes

- active version

### Akzeptanzkriterien

- aktueller Plan mobil lesbar

- Änderung nachvollziehbar/historisierbar zumindest per timestamp/version

### Pflichttests

- plan CRUD

- mobile read e2e

### Cursor-Prompt

```text
Implementiere Ticket EQ-007 – Fütterungsplan Basis.
Ziel: Strukturierten Fütterungsplan pro Pferd hinterlegen.
Abhängigkeiten: EQ-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: FeedPlan; time slots; feed item + quantity/unit; notes; active version.
Akzeptanzkriterien: aktueller Plan mobil lesbar; Änderung nachvollziehbar/historisierbar zumindest per timestamp/version.
Tests: plan CRUD; mobile read e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-001 – Rechnungsdomäne und Nummernkreise

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0                  |
|--------------------|--------------------------|----------------|---------------------|
| **Abhängigkeiten** | CLUB-002, EQ-005         | **Lastenheft** | Rechnungen, GEN-003 |

### Ziel

Rechnungsentwürfe mit stabilen Geldwerten und mandantenbezogenen Nummernkreisen schaffen.

### Scope

- Invoice/InvoiceItem

- draft/issued/paid/cancelled

- Decimal money

- yearly number sequence per org

- customer snapshot fields

- no full accounting ledger

### Akzeptanzkriterien

- ausgestellte Rechnungsnummer eindeutig und unveränderlich

- Entwurf noch editierbar

- Fremdmandant niemals sichtbar

### Pflichttests

- money/numbering tests

- status transition tests

### Cursor-Prompt

```text
Implementiere Ticket FIN-001 – Rechnungsdomäne und Nummernkreise.
Ziel: Rechnungsentwürfe mit stabilen Geldwerten und mandantenbezogenen Nummernkreisen schaffen.
Abhängigkeiten: CLUB-002, EQ-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Invoice/InvoiceItem; draft/issued/paid/cancelled; Decimal money; yearly number sequence per org; customer snapshot fields; no full accounting ledger.
Akzeptanzkriterien: ausgestellte Rechnungsnummer eindeutig und unveränderlich; Entwurf noch editierbar; Fremdmandant niemals sichtbar.
Tests: money/numbering tests; status transition tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-002 – Monatliche Rechnungsentwürfe aus Verträgen/Leistungen

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0  |
|--------------------|--------------------------|----------------|-----|
| **Abhängigkeiten** | FIN-001, EQ-006          | **Lastenheft** | A4  |

### Ziel

Wiederkehrende Grundpreise und erfasste Zusatzleistungen in Rechnungsentwürfe überführen.

### Scope

- billing run period

- eligible contracts

- service bookings

- preview

- idempotent generation

- manual adjustments with reason

### Akzeptanzkriterien

- zweiter Lauf erzeugt keine Duplikate

- Preview zeigt Quelle jeder Position

- manuelle Anpassung protokolliert

### Pflichttests

- billing idempotency tests

- A4 e2e draft

### Cursor-Prompt

```text
Implementiere Ticket FIN-002 – Monatliche Rechnungsentwürfe aus Verträgen/Leistungen.
Ziel: Wiederkehrende Grundpreise und erfasste Zusatzleistungen in Rechnungsentwürfe überführen.
Abhängigkeiten: FIN-001, EQ-006. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: billing run period; eligible contracts; service bookings; preview; idempotent generation; manual adjustments with reason.
Akzeptanzkriterien: zweiter Lauf erzeugt keine Duplikate; Preview zeigt Quelle jeder Position; manuelle Anpassung protokolliert.
Tests: billing idempotency tests; A4 e2e draft.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-003 – Rechnungsfreigabe, PDF und Zahlstatus

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0  |
|--------------------|--------------------------|----------------|-----|
| **Abhängigkeiten** | FIN-002, CORE-005        | **Lastenheft** | A4  |

### Ziel

Rechnung freigeben, PDF erzeugen und einfachen Zahlstatus verwalten.

### Scope

- Freigabe-Action: Status wechsel auf `issued`, danach nicht mehr still änderbar

- PDF-Generierung: **offene Paketentscheidung** – `barryvdh/laravel-dompdf` oder `spatie/browsershot` oder Blade-to-HTML → Menschliche Freigabe für Dependency nötig. Als Überbrückung: Blade-HTML-Download (Content-Disposition: attachment).

- Generiertes PDF als `Document`/`DocumentVersion` (CORE-005) gespeichert

- `paid`/`unpaid`/`overdue` manueller Status

- Download via Signed Temporary URL

### Akzeptanzkriterien

- PDF enthält Pflicht-Stammdatenfelder soweit konfiguriert ohne steuerliche Prüfung vorzutäuschen

- Ausgestellte Rechnung nicht still änderbar

- Zahlstatus nachvollziehbar

### Pflichttests

- Pest: PDF-Smoke-Test (Response-Code, Content-Type)

- Pest: Status-Permissions-Test

- Pest: A4 e2e Freigabe + Download

### Cursor-Prompt

```text
Implementiere Ticket FIN-003 – Rechnungsfreigabe, PDF und Zahlstatus.
Ziel: Rechnung freigeben, PDF erzeugen und einfachen Zahlstatus verwalten.
Abhängigkeiten: FIN-002, CORE-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: issue action; PDF template; store PDF as document/version; paid/unpaid/overdue manual status; download.
Akzeptanzkriterien: PDF enthält Pflicht-Stammdatenfelder soweit konfiguriert ohne steuerliche Prüfung vorzutäuschen; ausgestellte Rechnung nicht still änderbar; Zahlstatus nachvollziehbar.
Tests: PDF smoke content test; status permissions; A4 e2e complete.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-004 – Finanzimport Upload und Parser-Rahmen

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0                 |
|--------------------|--------------------------|----------------|--------------------|
| **Abhängigkeiten** | CORE-005                 | **Lastenheft** | BWA/XLS Import, A5 |

### Ziel

CSV/XLSX als Finanzdatenimport mit sicherer Vorschau annehmen.

### Scope

- `FinanceImport`-Modell mit Migration und Factory

- CSV/XLSX-Parsing: **offene Paketentscheidung** – `league/csv` (CSV) + `phpoffice/phpspreadsheet` (XLSX) oder `maatwebsite/excel`. Menschliche Freigabe für Dependency nötig. Bis dahin: CSV-only via `str_getcsv()`/`SplFileObject`.

- Datei-Validierung (MIME, Größe) und Column-Detection

- Raw Normalized Row Staging (Staging-Tabelle mit `finance_import_id`)

- Import-Status und strukturierte Fehlerprotokollierung

- Import-Job als queued Laravel-Job

### Akzeptanzkriterien

- Originaldatei bleibt nachvollziehbar (gespeichert als CORE-005 Dokument)

- Fehlerhafte Datei verändert keine Auswertung

- Große/ungültige Datei wird kontrolliert abgelehnt (HTTP 422 mit Meldung)

### Pflichttests

- Pest: Parser-Fixture-Tests (CSV-Testdatei)

- Pest: Malformed-File-Tests (zu groß, falsche MIME, korrupt)

### Cursor-Prompt

```text
Implementiere Ticket FIN-004 – Finanzimport Upload und Parser-Rahmen.
Ziel: CSV/XLSX als Finanzdatenimport mit sicherer Vorschau annehmen.
Abhängigkeiten: CORE-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: FinanceImport entity; CSV/XLSX parsing; file validation; column detection; raw normalized row staging; import status/errors.
Akzeptanzkriterien: Originaldatei bleibt nachvollziehbar; fehlerhafte Datei verändert keine Auswertung; große/ungültige Datei wird kontrolliert abgelehnt.
Tests: parser fixture tests; malformed file tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-005 – Konten- und Spaltenmapping

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0          |
|--------------------|--------------------------|----------------|-------------|
| **Abhängigkeiten** | FIN-004                  | **Lastenheft** | BWA Mapping |

### Ziel

Importspalten und Konten dauerhaft auf Managementkategorien/Kostenstellen abbilden.

### Scope

- mapping UI

- saved mapping profile per org/source

- account/category mapping

- preview before commit

- unknown account workflow

### Akzeptanzkriterien

- zweiter gleichartiger Import schlägt gespeichertes Mapping vor

- Unbekanntes Konto muss sichtbar bleiben

- Import erst nach Bestätigung committed

### Pflichttests

- mapping reuse tests

- preview/commit e2e

### Cursor-Prompt

```text
Implementiere Ticket FIN-005 – Konten- und Spaltenmapping.
Ziel: Importspalten und Konten dauerhaft auf Managementkategorien/Kostenstellen abbilden.
Abhängigkeiten: FIN-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: mapping UI; saved mapping profile per org/source; account/category mapping; preview before commit; unknown account workflow.
Akzeptanzkriterien: zweiter gleichartiger Import schlägt gespeichertes Mapping vor; Unbekanntes Konto muss sichtbar bleiben; Import erst nach Bestätigung committed.
Tests: mapping reuse tests; preview/commit e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-006 – Kostenstellen und Finanzperioden

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0                                    |
|--------------------|--------------------------|----------------|---------------------------------------|
| **Abhängigkeiten** | FIN-005                  | **Lastenheft** | Kostenstellen, Managementauswertungen |

### Ziel

Vereins-/Betriebsbereiche als Kostenstellen und Monatsperioden speichern.

### Scope

- CostCenter

- FinancialPeriod

- FinancialEntry normalized from import

- mapping to cost center

- period lock/version strategy

### Akzeptanzkriterien

- Pension/Reitschule/Verein frei konfigurierbar

- Monatswerte reproduzierbar

- Änderungen auditierbar

### Pflichttests

- period aggregation tests

- audit tests

### Cursor-Prompt

```text
Implementiere Ticket FIN-006 – Kostenstellen und Finanzperioden.
Ziel: Vereins-/Betriebsbereiche als Kostenstellen und Monatsperioden speichern.
Abhängigkeiten: FIN-005. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: CostCenter; FinancialPeriod; FinancialEntry normalized from import; mapping to cost center; period lock/version strategy.
Akzeptanzkriterien: Pension/Reitschule/Verein frei konfigurierbar; Monatswerte reproduzierbar; Änderungen auditierbar.
Tests: period aggregation tests; audit tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-007 – Rollenbezogenes Finanzdashboard

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P0                 |
|--------------------|--------------------------|----------------|--------------------|
| **Abhängigkeiten** | FIN-006, CLUB-010        | **Lastenheft** | Dashboard, NFR-002 |

### Ziel

Umsatz, Kosten, Ergebnis und Bereichsauswertung verständlich darstellen.

### Scope

- current period KPI cards

- previous period comparison

- cost center table

- role gating

- tax advisor disclaimer

- progressive loading if needed

### Akzeptanzkriterien

- Dashboard \< 3s bei realistischem Testdatensatz

- Nicht-Finanzrolle erhält keine sensiblen Daten

- Hinweis Orientierung/keine Steuerberatung sichtbar

### Pflichttests

- aggregation tests

- permission tests

- performance smoke

### Cursor-Prompt

```text
Implementiere Ticket FIN-007 – Rollenbezogenes Finanzdashboard.
Ziel: Umsatz, Kosten, Ergebnis und Bereichsauswertung verständlich darstellen.
Abhängigkeiten: FIN-006, CLUB-010. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: current period KPI cards; previous period comparison; cost center table; role gating; tax advisor disclaimer; progressive loading if needed.
Akzeptanzkriterien: Dashboard \< 3s bei realistischem Testdatensatz; Nicht-Finanzrolle erhält keine sensiblen Daten; Hinweis Orientierung/keine Steuerberatung sichtbar.
Tests: aggregation tests; permission tests; performance smoke.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## FIN-008 – Betriebs-KPIs Boxenauslastung und einfache Szenarien

| **Epic**           | E5 Abrechnung & Finanzen | **Priorität**  | P1                    |
|--------------------|--------------------------|----------------|-----------------------|
| **Abhängigkeiten** | FIN-007, EQ-003          | **Lastenheft** | Auslastung, Szenarien |

### Ziel

Operative Daten und Finanzsicht erstmals verbinden.

### Scope

- box occupancy KPI

- monthly occupancy calculation

- simple price-change scenario

- annualized impact display

- clearly labelled simulation

### Akzeptanzkriterien

- Auslastung aus Belegungshistorie korrekt

- Szenario verändert keine Echtdaten

- Ergebnis als Simulation gekennzeichnet

### Pflichttests

- occupancy unit tests

- scenario pure-function tests

### Cursor-Prompt

```text
Implementiere Ticket FIN-008 – Betriebs-KPIs Boxenauslastung und einfache Szenarien.
Ziel: Operative Daten und Finanzsicht erstmals verbinden.
Abhängigkeiten: FIN-007, EQ-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: box occupancy KPI; monthly occupancy calculation; simple price-change scenario; annualized impact display; clearly labelled simulation.
Akzeptanzkriterien: Auslastung aus Belegungshistorie korrekt; Szenario verändert keine Echtdaten; Ergebnis als Simulation gekennzeichnet.
Tests: occupancy unit tests; scenario pure-function tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-001 – Generisches Import-Framework und Adaptervertrag

| **Epic**           | E6 Migration & Integrationen | **Priorität**  | P0                               |
|--------------------|------------------------------|----------------|----------------------------------|
| **Abhängigkeiten** | CORE-002, SEC-006            | **Lastenheft** | Migration, Import, Integrationen |

### Ziel

Eine wiederverwendbare Importschicht schaffen, damit Datenübernahmen nicht pro Fremdsoftware neu gebaut werden müssen.

### Scope

- ImportJob/ImportSource/ImportAdapter contracts

- staging rows

- validate/map/preview/commit pipeline

- idempotency key

- error/warning model

- tenant-safe processing

### Akzeptanzkriterien

- Import verändert vor Commit keine Fachdaten

- Adapter können CSV/XLSX und später APIs nutzen

- jeder Lauf ist mandantenbezogen und auditierbar

### Pflichttests

- adapter contract tests

- tenant isolation tests

- failed import leaves domain unchanged

### Cursor-Prompt

```text
Implementiere Ticket MIG-001 – Generisches Import-Framework und Adaptervertrag.
Ziel: Eine wiederverwendbare Importschicht schaffen, damit Datenübernahmen nicht pro Fremdsoftware neu gebaut werden müssen.
Abhängigkeiten: CORE-002, SEC-006. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: ImportJob/ImportSource/ImportAdapter contracts; staging rows; validate/map/preview/commit pipeline; idempotency key; error/warning model; tenant-safe processing.
Akzeptanzkriterien: Import verändert vor Commit keine Fachdaten; Adapter können CSV/XLSX und später APIs nutzen; jeder Lauf ist mandantenbezogen und auditierbar.
Tests: adapter contract tests; tenant isolation tests; failed import leaves domain unchanged.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-002 – CSV/XLSX-Import Personen und Mitglieder

| **Epic**           | E6 Migration & Integrationen | **Priorität**  | P0                             |
|--------------------|------------------------------|----------------|--------------------------------|
| **Abhängigkeiten** | MIG-001, CLUB-002            | **Lastenheft** | Migration Mitglieder, Personen |

### Ziel

Mitglieder- und Personenstammdaten aus Altsoftware per CSV/XLSX übernehmen.

### Scope

- file upload

- header detection

- person/member fields

- membership number/status/entry/exit dates

- contact data

- custom unmapped columns retained in staging notes

### Akzeptanzkriterien

- mindestens Name, Mitgliedsnummer, Status und Kontaktdaten importierbar

- fehlerhafte Zeilen werden einzeln ausgewiesen

- kein Teilimport ohne explizite Bestätigung

### Pflichttests

- Pest: CSV-Fixtures (korrekte Daten, fehlerhafte Zeilen, Encoding-Edge-Cases)

- Pest: XLSX-Fixtures (sobald Paket freigegeben; bis dahin CSV-only)

- Pest: Member-Import-Integrations-Test

### Cursor-Prompt

```text
Implementiere Ticket MIG-002 – CSV/XLSX-Import Personen und Mitglieder.
Ziel: Mitglieder- und Personenstammdaten aus Altsoftware per CSV/XLSX übernehmen.
Abhängigkeiten: MIG-001, CLUB-002. Ändere nur Code, der für dieses Ticket erforderlich ist.
Hinweis: CSV/XLSX-Paketentscheidung steht noch aus (Menschliche Freigabe nötig). Bis dahin CSV-only via league/csv oder str_getcsv. XLSX-Adapter als Stub vorsehen.
Scope: file upload; header detection; person/member fields; membership number/status/entry/exit dates; contact data; custom unmapped columns retained in staging notes.
Akzeptanzkriterien: mindestens Name, Mitgliedsnummer, Status und Kontaktdaten importierbar; fehlerhafte Zeilen werden einzeln ausgewiesen; kein Teilimport ohne explizite Bestätigung.
Tests: CSV-Fixtures; date/encoding edge cases; member import e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-003 – CSV/XLSX-Import Pferde, Boxen und Einsteller

| **Epic**           | E6 Migration & Integrationen | **Priorität**  | P0                      |
|--------------------|------------------------------|----------------|-------------------------|
| **Abhängigkeiten** | MIG-001, EQ-003, EQ-004      | **Lastenheft** | Migration Pferdebetrieb |

### Ziel

Pferde- und Stallstammdaten aus Fremdsystemen übernehmen.

### Scope

- horse fields

- owner/customer reference

- stable/box reference

- occupancy status

- contract reference fields

- reference resolution report

### Akzeptanzkriterien

- Pferd kann Besitzer und Box korrekt zugeordnet werden

- unauflösbare Referenzen blockieren nicht unbemerkt

- Vorschau zeigt neu/aktualisiert/übersprungen

### Pflichttests

- horse import fixtures

- reference resolution tests

- cross-tenant negative test

### Cursor-Prompt

```text
Implementiere Ticket MIG-003 – CSV/XLSX-Import Pferde, Boxen und Einsteller.
Ziel: Pferde- und Stallstammdaten aus Fremdsystemen übernehmen.
Abhängigkeiten: MIG-001, EQ-003, EQ-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: horse fields; owner/customer reference; stable/box reference; occupancy status; contract reference fields; reference resolution report.
Akzeptanzkriterien: Pferd kann Besitzer und Box korrekt zugeordnet werden; unauflösbare Referenzen blockieren nicht unbemerkt; Vorschau zeigt neu/aktualisiert/übersprungen.
Tests: horse import fixtures; reference resolution tests; cross-tenant negative test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-004 – Spaltenmapping und wiederverwendbare Importprofile

| **Epic**           | E6 Migration & Integrationen | **Priorität**  | P0                |
|--------------------|------------------------------|----------------|-------------------|
| **Abhängigkeiten** | MIG-002, MIG-003             | **Lastenheft** | Migration Mapping |

### Ziel

Unterschiedliche Exportformate ohne Programmierung auf das interne Datenmodell abbilden.

### Scope

- drag/select column mapping UI

- required field validation

- value transforms for dates/booleans/enums

- saved profile per organization/source

- profile versioning

### Akzeptanzkriterien

- unbekannte Spalten können ignoriert oder dokumentiert werden

- gespeichertes Profil wird bei gleichem Format vorgeschlagen

- Pflichtfelder vor Commit validiert

### Pflichttests

- profile reuse tests

- transform tests

- mapping UI e2e

### Cursor-Prompt

```text
Implementiere Ticket MIG-004 – Spaltenmapping und wiederverwendbare Importprofile.
Ziel: Unterschiedliche Exportformate ohne Programmierung auf das interne Datenmodell abbilden.
Abhängigkeiten: MIG-002, MIG-003. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: drag/select column mapping UI; required field validation; value transforms for dates/booleans/enums; saved profile per organization/source; profile versioning.
Akzeptanzkriterien: unbekannte Spalten können ignoriert oder dokumentiert werden; gespeichertes Profil wird bei gleichem Format vorgeschlagen; Pflichtfelder vor Commit validiert.
Tests: profile reuse tests; transform tests; mapping UI e2e.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-005 – Dublettenprüfung, Importvorschau und Rollback

| **Epic**           | E6 Migration & Integrationen | **Priorität**  | P0                          |
|--------------------|------------------------------|----------------|-----------------------------|
| **Abhängigkeiten** | MIG-004, SEC-006             | **Lastenheft** | Migration Sicherheit, Audit |

### Ziel

Datenübernahmen sicher machen und Fehlimporte kontrolliert zurücknehmen können.

### Scope

- duplicate candidates by stable identifiers and configurable matching

- preview counts

- conflict decisions create/update/skip

- ImportBatch linkage on created records

- rollback only where safe

- audit log

### Akzeptanzkriterien

- keine automatische Zusammenführung unsicherer Dubletten

- Nutzer sieht Änderungen vor Commit

- Rollback löscht nur vom Lauf erzeugte bzw. sicher reversible Änderungen

### Pflichttests

- duplicate matching tests

- preview/commit tests

- rollback relation tests

### Cursor-Prompt

```text
Implementiere Ticket MIG-005 – Dublettenprüfung, Importvorschau und Rollback.
Ziel: Datenübernahmen sicher machen und Fehlimporte kontrolliert zurücknehmen können.
Abhängigkeiten: MIG-004, SEC-006. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: duplicate candidates by stable identifiers and configurable matching; preview counts; conflict decisions create/update/skip; ImportBatch linkage on created records; rollback only where safe; audit log.
Akzeptanzkriterien: keine automatische Zusammenführung unsicherer Dubletten; Nutzer sieht Änderungen vor Commit; Rollback löscht nur vom Lauf erzeugte bzw. sicher reversible Änderungen.
Tests: duplicate matching tests; preview/commit tests; rollback relation tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-006 – Vollständiger Datenexport für Anbieterwechsel und Sicherung

| **Epic**           | E6 Migration & Integrationen        | **Priorität**  | P0                        |
|--------------------|-------------------------------------|----------------|---------------------------|
| **Abhängigkeiten** | CORE-005, CLUB-002, EQ-004, FIN-001 | **Lastenheft** | Datenportabilität, Export |

### Ziel

Kunden sollen ihre wesentlichen Daten in offenen Formaten wieder aus dem System erhalten.

### Scope

- organization export job

- CSV/XLSX exports for people/members/horses/boxes/contracts/invoices where applicable

- document manifest

- ZIP package

- role permission

- audit

### Akzeptanzkriterien

- Export ist ohne proprietäre Software lesbar

- nur berechtigte Rollen können Gesamtexport starten

- Export eines Mandanten enthält keinerlei Fremdmandantendaten

### Pflichttests

- export content tests

- tenant leakage negative test

- large export smoke

### Cursor-Prompt

```text
Implementiere Ticket MIG-006 – Vollständiger Datenexport für Anbieterwechsel und Sicherung.
Ziel: Kunden sollen ihre wesentlichen Daten in offenen Formaten wieder aus dem System erhalten.
Abhängigkeiten: CORE-005, CLUB-002, EQ-004, FIN-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: organization export job; CSV/XLSX exports for people/members/horses/boxes/contracts/invoices where applicable; document manifest; ZIP package; role permission; audit.
Akzeptanzkriterien: Export ist ohne proprietäre Software lesbar; nur berechtigte Rollen können Gesamtexport starten; Export eines Mandanten enthält keinerlei Fremdmandantendaten.
Tests: export content tests; tenant leakage negative test; large export smoke.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## MIG-007 – Connector-SDK für spätere Fremdsoftware- und API-Adapter

| **Epic**           | E6 Migration & Integrationen | **Priorität**  | P1                         |
|--------------------|------------------------------|----------------|----------------------------|
| **Abhängigkeiten** | MIG-001, MIG-004             | **Lastenheft** | Integrationen, API-Adapter |

### Ziel

Direkte Schnittstellen zu häufigen Alt-/Drittsystemen später ergänzen können, ohne den Importkern umzubauen.

### Scope

- Connector interface

- capability declaration

- OAuth/API-key secret reference abstraction

- pull pagination/checkpoint contract

- rate-limit/retry hooks

- mapping into same staging pipeline

- example mock connector

### Akzeptanzkriterien

- neuer Connector benötigt keine Änderung am Fachimport

- Secrets liegen nicht im Quellcode oder Importprofil

- Connector-Daten durchlaufen dieselbe Preview/Commit-Logik

### Pflichttests

- mock connector contract tests

- retry/checkpoint tests

- secret redaction test

### Cursor-Prompt

```text
Implementiere Ticket MIG-007 – Connector-SDK für spätere Fremdsoftware- und API-Adapter.
Ziel: Direkte Schnittstellen zu häufigen Alt-/Drittsystemen später ergänzen können, ohne den Importkern umzubauen.
Abhängigkeiten: MIG-001, MIG-004. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: Connector interface; capability declaration; OAuth/API-key secret reference abstraction; pull pagination/checkpoint contract; rate-limit/retry hooks; mapping into same staging pipeline; example mock connector.
Akzeptanzkriterien: neuer Connector benötigt keine Änderung am Fachimport; Secrets liegen nicht im Quellcode oder Importprofil; Connector-Daten durchlaufen dieselbe Preview/Commit-Logik.
Tests: mock connector contract tests; retry/checkpoint tests; secret redaction test.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

<!-- OPS-001 bis OPS-004 entfallen: Laravel Cloud übernimmt HTTPS, Skalierung, DB-Backups, Storage, Monitoring, Log-Drain und Alerts. Siehe Abschnitt 1 'Betrieb und Deployment (Laravel Cloud)'. -->

## OPS-005 – DSGVO-Basis: Datenexport und Lösch-/Anonymisierungsworkflow

| **Epic**           | E7 Betrieb & Pilot          | **Priorität**  | P0               |
|--------------------|-----------------------------|----------------|------------------|
| **Abhängigkeiten** | CORE-002, CORE-005, SEC-006 | **Lastenheft** | SEC-009, SEC-010 |

### Ziel

Personenbezogene Daten kontrolliert identifizieren, exportieren und entsprechend Aufbewahrung behandeln.

### Scope

- person data inventory service

- export package

- delete/anonymize workflow with blockers

- audit

- admin UI

### Akzeptanzkriterien

- Export enthält zugeordnete personenbezogene Kerndaten

- historisch aufbewahrungspflichtige/verknüpfte Daten werden nicht blind gelöscht

- Vorgang auditierbar

### Pflichttests

- export tests

- anonymization relation tests

### Cursor-Prompt

```text
Implementiere Ticket OPS-005 – DSGVO-Basis: Datenexport und Lösch-/Anonymisierungsworkflow.
Ziel: Personenbezogene Daten kontrolliert identifizieren, exportieren und entsprechend Aufbewahrung behandeln.
Abhängigkeiten: CORE-002, CORE-005, SEC-006. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: person data inventory service; export package; delete/anonymize workflow with blockers; audit; admin UI.
Akzeptanzkriterien: Export enthält zugeordnete personenbezogene Kerndaten; historisch aufbewahrungspflichtige/verknüpfte Daten werden nicht blind gelöscht; Vorgang auditierbar.
Tests: export tests; anonymization relation tests.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## PILOT-001 – Demo-/Pilotdaten und End-to-End-Abnahme A1-A7

| **Epic**           | E7 Betrieb & Pilot                          | **Priorität**  | P0    |
|--------------------|---------------------------------------------|----------------|-------|
| **Abhängigkeiten** | CLUB-010, EQ-006, FIN-007, MIG-006, DEV-007 | **Lastenheft** | A1-A7 |

### Ziel

Einen realistischen Demo-Mandanten und automatisierte Kernflows für Pilotfreigabe schaffen.

### Scope

- `php artisan db:seed --class=PilotSeeder`: Verein und Mischbetrieb als Demo-Mandant

- Pest-Feature-Tests A1-A7 (Kernflows: Mitglieder, Vorstand, Pferd, Leistung, Rechnung, Import, Finance)

- Mobile-Viewport-Flows (Livewire-Component-Tests bei 360px)

- Tenant-Attack-Negative-Flow (Pest: Cross-Tenant-Zugriffsversuch schlägt fehl)

- Finanzimport-Fixture-Datei im Repository

### Akzeptanzkriterien

- A1-A7 Pest-Tests vollständig grün

- Demo ohne manuelle DB-Eingriffe nutzbar (`php artisan db:seed --class=PilotSeeder`)

- Mobile Kernflows funktionieren ab 360px

### Pflichttests

- Pest Feature-Test-Suite A1-A7

- Manuelle Pilot-Checkliste (manuell)

### Cursor-Prompt

```text
Implementiere Ticket PILOT-001 – Demo-/Pilotdaten und End-to-End-Abnahme A1-A7.
Ziel: Einen realistischen Demo-Mandanten und automatisierte Kernflows für Pilotfreigabe schaffen.
Abhängigkeiten: CLUB-010, EQ-006, FIN-007, MIG-006, DEV-007. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: PilotSeeder (Verein + Mischbetrieb); Pest Feature-Tests A1-A7; Mobile Viewport Livewire-Tests; Tenant-Attack-Negative-Flow; Finanzimport-Fixture.
Akzeptanzkriterien: A1-A7 grün; Demo via php artisan db:seed --class=PilotSeeder; mobile Kernflows ab 360px.
Tests: Pest Feature-Test-Suite A1-A7; manuelle Pilot-Checkliste.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

## PILOT-002 – Pilot-Feedback, Feature-Telemetrie und Fehlerkanal

| **Epic**           | E7 Betrieb & Pilot | **Priorität**  | P1           |
|--------------------|--------------------|----------------|--------------|
| **Abhängigkeiten** | PILOT-001          | **Lastenheft** | Pilotbetrieb |

### Ziel

Frühe Nutzeraktivität messen, ohne unnötige personenbezogene Inhalte zu sammeln.

### Scope

- privacy-conscious event schema

- organization-level feature usage counters

- feedback form

- support/error reference ID

- opt/config docs

### Akzeptanzkriterien

- keine Freitext-/Dokumentinhalte in Analytics

- Feature-Nutzung je Mandant aggregierbar

- Feedback enthält Request/Version reference

### Pflichttests

- event allowlist tests

- privacy review

### Cursor-Prompt

```text
Implementiere Ticket PILOT-002 – Pilot-Feedback, Feature-Telemetrie und Fehlerkanal.
Ziel: Frühe Nutzeraktivität messen, ohne unnötige personenbezogene Inhalte zu sammeln.
Abhängigkeiten: PILOT-001. Ändere nur Code, der für dieses Ticket erforderlich ist.
Scope: privacy-conscious event schema; organization-level feature usage counters; feedback form; support/error reference ID; opt/config docs.
Akzeptanzkriterien: keine Freitext-/Dokumentinhalte in Analytics; Feature-Nutzung je Mandant aggregierbar; Feedback enthält Request/Version reference.
Tests: event allowlist tests; privacy review.
Verbindliche Regeln: Lies zuerst AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules. Nutze Boost search-docs vor jeder Code-Änderung. PHP 8.4 strict types. Keine Secrets im Repository. Jeder mandantenbezogene Datenzugriff muss Tenant-Isolation erzwingen (Global Scope); jede sensible Aktion über Policy/Gate absichern. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory. Keine sachfremden Refactorings. Aktualisiere .ai/rules und Docu/architecture nur soweit dieses Ticket es erfordert. Führe vendor/bin/pint --dirty, composer types:check und php artisan test --compact aus. Am Ende liefere: (1) Kurzfassung der Änderung, (2) geänderte Dateien, (3) Migrationen, (4) ausgeführte Tests mit Ergebnis, (5) offene Risiken/Entscheidungen. Deploye nicht in Produktion.
```

# 6. Standardprompt für neue Tickets

Du arbeitest im EquiFlow-Laravel-Projekt (Laravel 13, PHP 8.4, Livewire 4, Flux UI 2). Implementiere ausschließlich das unten genannte Ticket.

TICKET: \<ID – Titel\>  
ZIEL: \<ein Satz\>  
ABHÄNGIGKEITEN: \<IDs\>  
SCOPE: \<konkret\>  
NICHT IM SCOPE: \<konkret\>  
AKZEPTANZKRITERIEN: \<Liste\>  
PFLICHTTESTS: \<Liste\>  

Regeln:  
1. Lies AGENTS.md/CLAUDE.md und alle passenden Dateien unter .ai/rules zuerst.  
2. Nutze Boost search-docs vor jeder Code-Änderung.  
3. PHP 8.4 strict types; keine Abweichungen ohne begründete ADR.  
4. Tenant-Isolation (Global Scope) und Policies/Gates bei jedem Datenzugriff und jeder Aktion explizit prüfen und testen.  
5. DB-Schemaänderungen nur als Laravel-Migration mit zugehöriger Factory und ggf. Seeder.  
6. Keine Secrets und keine sensiblen Nutzdaten in Logs/Testfixtures.  
7. Keine sachfremden Refactorings oder Abhängigkeits-Upgrades.  
8. Durable Regeln per Boost record-rule in .ai/rules festhalten.  
9. vendor/bin/pint --dirty, composer types:check und php artisan test --compact lokal/CI-fähig ausführen.  
10. Nicht autonom in Produktion deployen.  

Abschlussbericht:  
- Was wurde umgesetzt?  
- Welche Dateien wurden geändert?  
- Welche Migrationen (+ Factories) entstanden?  
- Welche Tests liefen mit welchem Ergebnis?  
- Welche Risiken oder offenen Produktentscheidungen bleiben?

# 7. Menschliche Review-Checkliste je Ticket

- Diff enthält nur Ticket-relevante Änderungen.

- Akzeptanzkriterien lassen sich manuell nachvollziehen.

- Tenant-Isolation geprüft; kein IDOR/Fremdmandantenzugriff.

- Rollen-/Permission-Prüfung serverseitig vorhanden.

- Keine Secrets, Passwörter, vollständigen Zahlungsdaten oder Dokumentinhalte in Logs.

- Geldwerte werden nicht als Float verarbeitet.

- DB-Änderungen besitzen Migration und sinnvolle Constraints/Indizes.

- Fehlerzustände und leere Zustände sind in der UI berücksichtigt.

- Mobile Kernansicht bei 360–430 px geprüft.

- vendor/bin/pint, composer types:check und php artisan test --compact grün.

- .ai/rules und Docu/architecture bei relevanter Änderung aktualisiert (via Boost record-rule).

- Keine unbeabsichtigte Rechts-/Steuerberatung in UI-Texten.

# 8. Pilot-Freigabegate

Vor Aufnahme der ersten echten Vereine und Betriebe müssen mindestens folgende Bedingungen erfüllt sein:

- Alle P0-Tickets bis einschließlich PILOT-001 abgeschlossen.

- A1–A7 End-to-End-Abnahmen grün.

- Mandantentrennung zusätzlich manuell gegen manipulierte IDs geprüft.

- Laravel-Cloud-Staging-Environment getestet; Restore aus Laravel-Cloud-Backup verifiziert.

- TLS (via Laravel Cloud), Rate Limiting, Cookie-/Session-Sicherheit und Upload-Schutz geprüft.

- DSGVO-Basisworkflow dokumentiert; AVV/Datenschutztexte separat fachlich prüfen lassen.

- Rechts-/Steuerberatungshinweise im Produkt vorhanden.

- Mobile PWA (Livewire + Blade Manifest) auf mindestens iOS Safari und Android Chrome manuell getestet.

- Pilot-Support- und Incident-Verantwortung festgelegt; Laravel Cloud Alerts konfiguriert.

# 9. Was bewusst nach dem Pilot folgt

Der Pilot soll die Kernprobleme beweisen, nicht die komplette Vision ausliefern. Nach dem Pilot werden Funktionen ausschließlich über die Stage-Gates freigegeben.

**Stufe B – nach 10 Kunden:** Newsletter, Volltextsuche, Vereinsjahres-Workflows, Portal-/Formularbibliothek, Kursanmeldung, PayPal, Lizenzwarnungen, Steuerberater-Paket sowie die bereits vorgesehenen Abrechnungs-/Kommunikationsausbauten.

**Stufe C – nach 50 Kunden:** Präsentationsgenerator, Personal-/Übungsleiter-Einsatzplanung, Beraterportal, Organisationsgedächtnis, Health Score und Nachfolgefähigkeits-Score.

**Stufe D – später:** direkte Fremdsoftware-Connectoren nach realer Nachfrage, Forecast/Digital Twin, anonymisiertes Benchmarking, Verbandsportal, weitere Sport-/Vereinsmodule sowie native iOS-/Android-Apps nur bei nachgewiesenem Bedarf.

Weitere mögliche Ausbaupunkte wie 2FA-Pflicht je Rolle, SEPA-/Bankintegration und native Push-Benachrichtigungen werden ebenfalls anhand echter Kundenanforderungen priorisiert.

Empfohlener erster Auftrag: DEV-001. Erst wenn Laravel-Projektbasis, Qualitätsregeln und CI-Grundlage sauber stehen, DEV-003 und die folgenden Tickets beginnen.


# 10. Detaillierte neue Tickets v1.3

Die folgenden Tickets ergänzen die 61 Tickets aus v1.1. Sie werden nach den Stage-Gates freigegeben.

## OFF-001 – Eine strukturierte digitale Vereins-/Betriebsakte schaffen, die Dokumente nicht nur speichert, sondern fachlich einordnet

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | A / P0 |
| **Abhängigkeiten** | CORE-005, SEC-006 |

### Ziel

Eine strukturierte digitale Vereins-/Betriebsakte schaffen, die Dokumente nicht nur speichert, sondern fachlich einordnet.

### Scope

- Dokument-Metadaten: Kategorie, Zeitraum, Status, Tags, Verantwortlicher, Fachobjektbezug
- Ablagesichten für Vorstand, Finanzen, Personal/Übungsleiter, Verträge, Versicherungen, Veranstaltungen und Betrieb
- Tenant-Isolation und RBAC auf Dokumentebene
- Keine starre Ordnerstruktur als einziges Ordnungsprinzip; Metadaten und Relationen sind primär

### Akzeptanzkriterien

- Dokument kann mehreren fachlichen Kontexten zugeordnet werden
- Unberechtigte Nutzer erhalten keinen Zugriff
- Metadaten sind filter- und API-seitig abfragbar
- Audit-Log protokolliert relevante Änderungen

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-001.

Ziel: Eine strukturierte digitale Vereins-/Betriebsakte schaffen, die Dokumente nicht nur speichert, sondern fachlich einordnet.

Abhängigkeiten: CORE-005, SEC-006.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Dokument kann mehreren fachlichen Kontexten zugeordnet werden
- Unberechtigte Nutzer erhalten keinen Zugriff
- Metadaten sind filter- und API-seitig abfragbar
- Audit-Log protokolliert relevante Änderungen

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## OFF-002 – Dokumenthistorie und langfristig stabile Verknüpfungen ermöglichen

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | A / P0 |
| **Abhängigkeiten** | OFF-001 |

### Ziel

Dokumenthistorie und langfristig stabile Verknüpfungen ermöglichen.

### Scope

- Versionen eines Dokuments mit immutable Version-ID
- Verknüpfungen zu Person, Aufgabe, Beschluss, Workflow, Event und Finanzperiode
- Soft-delete/Archivierung statt unkontrolliertem Verlust
- Anzeige: wer hat wann welche Version ersetzt/ergänzt

### Akzeptanzkriterien

- Alte Versionen bleiben nachvollziehbar
- Verknüpfungen brechen bei neuer Version nicht
- Historie ist tenant- und rechtegesichert
- Tests für Versionierung und Relationen vorhanden

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-002.

Ziel: Dokumenthistorie und langfristig stabile Verknüpfungen ermöglichen.

Abhängigkeiten: OFF-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Alte Versionen bleiben nachvollziehbar
- Verknüpfungen brechen bei neuer Version nicht
- Historie ist tenant- und rechtegesichert
- Tests für Versionierung und Relationen vorhanden

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## WF-001 – Eine generische Workflow-Template-Engine aufbauen, damit Vereinsprozesse nicht als Spezialcode pro Seite entstehen

| Feld | Wert |
|---|---|
| **Produktbereich** | Workflows |
| **Stufe / Priorität** | A / P0 |
| **Abhängigkeiten** | CORE-003, CORE-005 |

### Ziel

Eine generische Workflow-Template-Engine aufbauen, damit Vereinsprozesse nicht als Spezialcode pro Seite entstehen.

### Scope

- WorkflowTemplate, WorkflowStepTemplate, WorkflowInstance, WorkflowStepInstance
- Versionierbare Vorlagen
- Schritte mit Rolle, Fälligkeit, Pflicht/optional, Dokument-/Linkanforderung
- Template-Instanz erzeugt reproduzierbare Prozessschritte

### Akzeptanzkriterien

- Neue Vorlage kann ohne Codeänderung gespeichert werden
- Instanz friert verwendete Template-Version ein
- Schritte können Rollen statt konkrete Personen referenzieren
- Tenant-Isolation und Audit-Tests vorhanden

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket WF-001.

Ziel: Eine generische Workflow-Template-Engine aufbauen, damit Vereinsprozesse nicht als Spezialcode pro Seite entstehen.

Abhängigkeiten: CORE-003, CORE-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Neue Vorlage kann ohne Codeänderung gespeichert werden
- Instanz friert verwendete Template-Version ein
- Schritte können Rollen statt konkrete Personen referenzieren
- Tenant-Isolation und Audit-Tests vorhanden

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## WF-002 – Geführte Prozesse im UI ausführbar machen

| Feld | Wert |
|---|---|
| **Produktbereich** | Workflows |
| **Stufe / Priorität** | A / P0 |
| **Abhängigkeiten** | WF-001 |

### Ziel

Geführte Prozesse im UI ausführbar machen.

### Scope

- Schritt-für-Schritt-Ansicht
- Status offen/in Arbeit/erledigt/nicht relevant
- Verantwortliche Person und Frist
- Fortschrittsanzeige
- Pilotvorlagen: Vorstandswechsel und Vereins-Onboarding

### Akzeptanzkriterien

- Nutzer sieht jederzeit nächsten sinnvollen Schritt
- Fortschritt wird korrekt berechnet
- Pflichtschritte verhindern versehentlichen Abschluss
- Mobile Bedienung funktioniert

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket WF-002.

Ziel: Geführte Prozesse im UI ausführbar machen.

Abhängigkeiten: WF-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Nutzer sieht jederzeit nächsten sinnvollen Schritt
- Fortschritt wird korrekt berechnet
- Pflichtschritte verhindern versehentlichen Abschluss
- Mobile Bedienung funktioniert

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## EVT-001 – Domänenmodell für Kurse, Lehrgänge und Veranstaltungen vorbereiten

| Feld | Wert |
|---|---|
| **Produktbereich** | Events |
| **Stufe / Priorität** | A / P1 |
| **Abhängigkeiten** | CORE-002 |

### Ziel

Domänenmodell für Kurse, Lehrgänge und Veranstaltungen vorbereiten.

### Scope

- Event, Session/Termin, Ticket/Teilnahmeart, Registration, Participant
- Kapazität, Preis, interner/externer Teilnehmer
- Status Entwurf/veröffentlicht/geschlossen/abgesagt
- Noch kein Payment-Zwang im Pilot

### Akzeptanzkriterien

- Event kann mit Kapazität und Preis angelegt werden
- Registrierungen sind tenant-sicher
- Modell unterstützt spätere Warteliste/Payment
- Keine Zahlungsanbieterlogik im Event-Kern

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket EVT-001.

Ziel: Domänenmodell für Kurse, Lehrgänge und Veranstaltungen vorbereiten.

Abhängigkeiten: CORE-002.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Event kann mit Kapazität und Preis angelegt werden
- Registrierungen sind tenant-sicher
- Modell unterstützt spätere Warteliste/Payment
- Keine Zahlungsanbieterlogik im Event-Kern

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## EVT-003 – Zahlungsanbieter technisch abstrahieren, damit PayPal nicht fest in die Domäne eingebaut wird

| Feld | Wert |
|---|---|
| **Produktbereich** | Events |
| **Stufe / Priorität** | A / P0 |
| **Abhängigkeiten** | DEV-004, SEC-006 |

### Ziel

Zahlungsanbieter technisch abstrahieren, damit PayPal nicht fest in die Domäne eingebaut wird.

### Scope

- PaymentProvider Interface
- PaymentIntent/PaymentTransaction Domäne
- Webhook-Verifikation als Provider-Verantwortung
- Idempotency-Key und Reconciliation-Status
- MockProvider für Tests

### Akzeptanzkriterien

- Event-/Invoice-Domäne kennt keinen PayPal-spezifischen Typ
- Doppelte Webhooks erzeugen keine Doppelbuchung
- Provider kann später ersetzt/ergänzt werden
- Audit- und Security-Tests vorhanden

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket EVT-003.

Ziel: Zahlungsanbieter technisch abstrahieren, damit PayPal nicht fest in die Domäne eingebaut wird.

Abhängigkeiten: DEV-004, SEC-006.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Event-/Invoice-Domäne kennt keinen PayPal-spezifischen Typ
- Doppelte Webhooks erzeugen keine Doppelbuchung
- Provider kann später ersetzt/ergänzt werden
- Audit- und Security-Tests vorhanden

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## PEO-001 – Personal, Trainer und Übungsleiter als Rollen derselben Personenbasis modellieren

| Feld | Wert |
|---|---|
| **Produktbereich** | People |
| **Stufe / Priorität** | A / P1 |
| **Abhängigkeiten** | CORE-002, CORE-005 |

### Ziel

Personal, Trainer und Übungsleiter als Rollen derselben Personenbasis modellieren.

### Scope

- Employment/Engagement Relation zur Person
- Funktion, Start/Ende, Ansprechpartner, Status
- Dokumentbezüge für Vertrag/Nachweise
- Rollen: Mitarbeiter, Trainer, Übungsleiter, Ehrenamtliche

### Akzeptanzkriterien

- Eine Person kann mehrere Engagements besitzen
- Historische Engagements bleiben erhalten
- Dokumente sind verknüpfbar
- Keine Lohnabrechnung im Pilot

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket PEO-001.

Ziel: Personal, Trainer und Übungsleiter als Rollen derselben Personenbasis modellieren.

Abhängigkeiten: CORE-002, CORE-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Eine Person kann mehrere Engagements besitzen
- Historische Engagements bleiben erhalten
- Dokumente sind verknüpfbar
- Keine Lohnabrechnung im Pilot

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-001 – Datenbasis für ein späteres Organisationsgedächtnis schaffen

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | A / P0 |
| **Abhängigkeiten** | SEC-006, OFF-002, CLUB-005 |

### Ziel

Datenbasis für ein späteres Organisationsgedächtnis schaffen.

### Scope

- Generische EntityRelation oder klar definierte Relationstabellen
- Zeitliche Gültigkeit/createdAt/updatedAt und Actor-Information
- Beschluss→Aufgabe→Dokument→Person→Workflow nachvollziehbar
- Keine KI-Abfrage im Pilot erforderlich

### Akzeptanzkriterien

- Mindestens Beschlüsse, Aufgaben, Dokumente und Workflows sind verknüpfbar
- Historische Kette bleibt nach Änderungen nachvollziehbar
- Relationen sind mandantensicher
- API liefert Chronologie für einen Fachvorgang

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-001.

Ziel: Datenbasis für ein späteres Organisationsgedächtnis schaffen.

Abhängigkeiten: SEC-006, OFF-002, CLUB-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Mindestens Beschlüsse, Aufgaben, Dokumente und Workflows sind verknüpfbar
- Historische Kette bleibt nach Änderungen nachvollziehbar
- Relationen sind mandantensicher
- API liefert Chronologie für einen Fachvorgang

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-006 – Kennzahlen so definieren, dass spätere Health Scores und Benchmarks konsistent möglich sind

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | A / P1 |
| **Abhängigkeiten** | FIN-006, FIN-007 |

### Ziel

Kennzahlen so definieren, dass spätere Health Scores und Benchmarks konsistent möglich sind.

### Scope

- KPI Definition mit key, version, formula/handler, unit, period
- Berechnete KPI-Werte mit Quelle und Zeitraum
- Keine organisationenübergreifende Auswertung im Pilot
- Dokumentation der KPI-Semantik

### Akzeptanzkriterien

- KPI-Versionen sind nachvollziehbar
- Werte lassen sich reproduzieren
- Keine Cross-Tenant-Abfrage möglich
- Mindestens Umsatz, Kosten, Ergebnis und Auslastung modelliert

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-006.

Ziel: Kennzahlen so definieren, dass spätere Health Scores und Benchmarks konsistent möglich sind.

Abhängigkeiten: FIN-006, FIN-007.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- KPI-Versionen sind nachvollziehbar
- Werte lassen sich reproduzieren
- Keine Cross-Tenant-Abfrage möglich
- Mindestens Umsatz, Kosten, Ergebnis und Auslastung modelliert

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## OFF-003 – Schnelle Suche in Vereinswissen ermöglichen

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | OFF-002 |

### Ziel

Schnelle Suche in Vereinswissen ermöglichen.

### Scope

- Volltextsuche über Titel, Metadaten und extrahierbaren Text soweit technisch sicher
- Filter nach Jahr, Kategorie, Person, Beschluss, Workflow
- Suchrechte entsprechen Dokumentrechten
- Provider-/DB-Lösung reversibel halten

### Akzeptanzkriterien

- Suchergebnisse respektieren RBAC
- Filter kombinierbar
- Historische Versionen optional auffindbar
- Performance-Test mit realistischem Pilotdatenvolumen

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-003.

Ziel: Schnelle Suche in Vereinswissen ermöglichen.

Abhängigkeiten: OFF-002.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Suchergebnisse respektieren RBAC
- Filter kombinierbar
- Historische Versionen optional auffindbar
- Performance-Test mit realistischem Pilotdatenvolumen

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## OFF-004 – Gemeinsame Template-Engine für Newsletter, Berichte, Präsentationsinhalte und Beraterpakete schaffen

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | OFF-002 |

### Ziel

Gemeinsame Template-Engine für Newsletter, Berichte, Präsentationsinhalte und Beraterpakete schaffen.

### Scope

- Template mit Variablen/Sections
- Versionierung
- Renderer-Abstraktion für HTML/E-Mail/Dokumente
- Preview vor Generierung

### Akzeptanzkriterien

- Vorlagen sind ohne Codeänderung anpassbar
- Fehlende Variablen werden validiert
- Renderer sind austauschbar
- Template-Version eines erzeugten Dokuments ist nachvollziehbar

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-004.

Ziel: Gemeinsame Template-Engine für Newsletter, Berichte, Präsentationsinhalte und Beraterpakete schaffen.

Abhängigkeiten: OFF-002.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Vorlagen sind ohne Codeänderung anpassbar
- Fehlende Variablen werden validiert
- Renderer sind austauschbar
- Template-Version eines erzeugten Dokuments ist nachvollziehbar

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## OFF-005 – Newsletter-Arbeit aus dem System heraus ermöglichen

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | OFF-004, CORE-007, CLUB-001 |

### Ziel

Newsletter-Arbeit aus dem System heraus ermöglichen.

### Scope

- Segmentierung nach Mitglieds-/Personenmerkmalen
- Editor mit Vorlagen und Vorschau
- Testversand und finaler Versand über E-Mail-Adapter
- Abmeldung/Kommunikationspräferenzen berücksichtigen
- Versandhistorie

### Akzeptanzkriterien

- Nur zulässige Empfänger werden angeschrieben
- Segment wird vor Versand als Anzahl/Vorschau gezeigt
- Fehlerhafte Adressen stoppen nicht gesamten Versand
- Versand ist auditierbar

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-005.

Ziel: Newsletter-Arbeit aus dem System heraus ermöglichen.

Abhängigkeiten: OFF-004, CORE-007, CLUB-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Nur zulässige Empfänger werden angeschrieben
- Segment wird vor Versand als Anzahl/Vorschau gezeigt
- Fehlerhafte Adressen stoppen nicht gesamten Versand
- Versand ist auditierbar

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## OFF-007 – Nützliche externe Portale, Formulare und Links kontextbezogen bereitstellen

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | WF-001 |

### Ziel

Nützliche externe Portale, Formulare und Links kontextbezogen bereitstellen.

### Scope

- PortalResource mit Titel, URL, zuständiger Ebene/Region, Fachthema, Gültigkeitsdatum
- Verknüpfung zu Workflow-Schritten
- Redaktioneller Status und Review-Datum
- Hinweis: externe Inhalte können sich ändern

### Akzeptanzkriterien

- Links können ohne Deployment aktualisiert werden
- Abgelaufene Review-Daten werden intern markiert
- Workflow kann passende Ressourcen anzeigen
- Kein Anspruch auf Rechts-/Steuerberatung wird erzeugt

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-007.

Ziel: Nützliche externe Portale, Formulare und Links kontextbezogen bereitstellen.

Abhängigkeiten: WF-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Links können ohne Deployment aktualisiert werden
- Abgelaufene Review-Daten werden intern markiert
- Workflow kann passende Ressourcen anzeigen
- Kein Anspruch auf Rechts-/Steuerberatung wird erzeugt

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## WF-003 – Typische wiederkehrende Vereinsabläufe als Vereinsjahr abbilden

| Feld | Wert |
|---|---|
| **Produktbereich** | Workflows |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | WF-001, CORE-004 |

### Ziel

Typische wiederkehrende Vereinsabläufe als Vereinsjahr abbilden.

### Scope

- Jahreskalender aus Workflow-Vorlagen
- relative Fälligkeiten je Geschäftsjahr
- Verein kann Vorlagen aktivieren/deaktivieren/anpassen
- Beispiele: Beitragslauf, Kassenprüfung, Mitgliederversammlung, Budgetplanung

### Akzeptanzkriterien

- Jahreswechsel erzeugt keine Duplikate
- angepasste Vereinsvorlage bleibt organisationsspezifisch
- Fristen erscheinen in Aufgaben/Inbox
- Vorlagen sind versioniert

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket WF-003.

Ziel: Typische wiederkehrende Vereinsabläufe als Vereinsjahr abbilden.

Abhängigkeiten: WF-001, CORE-004.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Jahreswechsel erzeugt keine Duplikate
- angepasste Vereinsvorlage bleibt organisationsspezifisch
- Fristen erscheinen in Aufgaben/Inbox
- Vorlagen sind versioniert

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## WF-004 – Zu jedem Arbeitsschritt die benötigten Unterlagen, Nachweise und Portale anzeigen

| Feld | Wert |
|---|---|
| **Produktbereich** | Workflows |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | WF-002, OFF-001, OFF-007 |

### Ziel

Zu jedem Arbeitsschritt die benötigten Unterlagen, Nachweise und Portale anzeigen.

### Scope

- Step Requirements: Dokumenttyp, Vorlage, externer Link, Freitext-Hinweis
- Upload/Zuordnung direkt im Schritt
- Pflichtnachweise optional konfigurierbar
- Orientierungs-Hinweis bei rechtlich/steuerlich sensiblen Schritten

### Akzeptanzkriterien

- Schritt zeigt vollständig benötigte Ressourcen
- Nachweis wird in Akte und Workflow verknüpft
- Abschlussregeln funktionieren
- sensible Hinweise sind konsistent

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket WF-004.

Ziel: Zu jedem Arbeitsschritt die benötigten Unterlagen, Nachweise und Portale anzeigen.

Abhängigkeiten: WF-002, OFF-001, OFF-007.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Schritt zeigt vollständig benötigte Ressourcen
- Nachweis wird in Akte und Workflow verknüpft
- Abschlussregeln funktionieren
- sensible Hinweise sind konsistent

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## WF-005 – Fristen und überfällige Prozesse aktiv managen

| Feld | Wert |
|---|---|
| **Produktbereich** | Workflows |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | WF-002, CORE-007 |

### Ziel

Fristen und überfällige Prozesse aktiv managen.

### Scope

- Reminder-Regeln relativ zur Fälligkeit
- Eskalation an Rolle/Stellvertretung
- Digest statt Benachrichtigungs-Spam
- Snooze mit Audit

### Akzeptanzkriterien

- Fällige Schritte erzeugen korrekt terminierte Hinweise
- Eskalationen respektieren Rollen
- Nutzer kann Präferenzen steuern
- Keine Mehrfachsendung durch Retry

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket WF-005.

Ziel: Fristen und überfällige Prozesse aktiv managen.

Abhängigkeiten: WF-002, CORE-007.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Fällige Schritte erzeugen korrekt terminierte Hinweise
- Eskalationen respektieren Rollen
- Nutzer kann Präferenzen steuern
- Keine Mehrfachsendung durch Retry

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## EVT-002 – Öffentliche Kursanmeldung ohne manuelle Excel-Listen ermöglichen

| Feld | Wert |
|---|---|
| **Produktbereich** | Events |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | EVT-001 |

### Ziel

Öffentliche Kursanmeldung ohne manuelle Excel-Listen ermöglichen.

### Scope

- Public registration page mit Token/Slug
- Kapazitätsprüfung atomar
- Warteliste mit Reihenfolge
- Pflichtfelder/Einwilligungen konfigurierbar
- interne Teilnehmerübersicht

### Akzeptanzkriterien

- Keine Überbuchung bei parallelen Anmeldungen
- Warteliste arbeitet deterministisch
- Datenschutztexte versioniert speicherbar
- Mobile Anmeldung funktioniert

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket EVT-002.

Ziel: Öffentliche Kursanmeldung ohne manuelle Excel-Listen ermöglichen.

Abhängigkeiten: EVT-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Keine Überbuchung bei parallelen Anmeldungen
- Warteliste arbeitet deterministisch
- Datenschutztexte versioniert speicherbar
- Mobile Anmeldung funktioniert

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## EVT-004 – PayPal als ersten echten Zahlungsanbieter anbinden

| Feld | Wert |
|---|---|
| **Produktbereich** | Events |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | EVT-003, EVT-002 |

### Ziel

PayPal als ersten echten Zahlungsanbieter anbinden.

### Scope

- PayPal Checkout über offiziellen aktuellen API-Weg
- Webhook-Signaturprüfung
- PaymentIntent zu Registration
- Status paid/failed/refunded/pending
- Reconciliation UI für Unklarheiten

### Akzeptanzkriterien

- Anmeldung kann direkt bezahlt werden
- Webhook ist idempotent
- manuelle Zuordnung ist auditierbar
- keine Secrets im Frontend oder Log

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket EVT-004.

Ziel: PayPal als ersten echten Zahlungsanbieter anbinden.

Abhängigkeiten: EVT-003, EVT-002.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Anmeldung kann direkt bezahlt werden
- Webhook ist idempotent
- manuelle Zuordnung ist auditierbar
- keine Secrets im Frontend oder Log

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## EVT-005 – Kommunikation rund um Kurse automatisieren

| Feld | Wert |
|---|---|
| **Produktbereich** | Events |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | EVT-002, EVT-004, CORE-007 |

### Ziel

Kommunikation rund um Kurse automatisieren.

### Scope

- Bestätigung nach Anmeldung/Zahlung
- Erinnerung vor Termin
- Wartelisten-Nachrücken
- Storno-/Refund-Workflow mit menschlicher Freigabe wo nötig
- Teilnehmerexport

### Akzeptanzkriterien

- Kommunikation folgt Statusmaschine
- keine Mail bei bereits storniertem Teilnehmer
- Refund-Status ist nachvollziehbar
- Vorlagen editierbar

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket EVT-005.

Ziel: Kommunikation rund um Kurse automatisieren.

Abhängigkeiten: EVT-002, EVT-004, CORE-007.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Kommunikation folgt Statusmaschine
- keine Mail bei bereits storniertem Teilnehmer
- Refund-Status ist nachvollziehbar
- Vorlagen editierbar

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## PEO-002 – Qualifikationen und Lizenzen von Trainern/Übungsleitern überwachen

| Feld | Wert |
|---|---|
| **Produktbereich** | People |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | PEO-001, CORE-007 |

### Ziel

Qualifikationen und Lizenzen von Trainern/Übungsleitern überwachen.

### Scope

- Qualification mit Typ, Nummer, Aussteller, gültig von/bis
- Dokumentnachweis
- Warnfristen
- Filter: läuft bald ab/abgelaufen

### Akzeptanzkriterien

- Ablaufwarnungen sind konfigurierbar
- abgelaufene Qualifikation wird sichtbar markiert
- Dokument ist direkt erreichbar
- Historie bleibt erhalten

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket PEO-002.

Ziel: Qualifikationen und Lizenzen von Trainern/Übungsleitern überwachen.

Abhängigkeiten: PEO-001, CORE-007.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Ablaufwarnungen sind konfigurierbar
- abgelaufene Qualifikation wird sichtbar markiert
- Dokument ist direkt erreichbar
- Historie bleibt erhalten

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## ADV-001 – Unterlagen für Steuerberater strukturiert vorbereiten, ohne Beratung zu simulieren

| Feld | Wert |
|---|---|
| **Produktbereich** | Finance/Advisor |
| **Stufe / Priorität** | B / P1 |
| **Abhängigkeiten** | FIN-007, OFF-001, FIN-003 |

### Ziel

Unterlagen für Steuerberater strukturiert vorbereiten, ohne Beratung zu simulieren.

### Scope

- Zeitraumauswahl
- Paketinhalt: Finanzberichte, Rechnungen/Belege soweit vorhanden, Kostenstellenübersicht, offene Fragen, relevante Vertrags-/Förderdokumente
- Manifest/Indexdatei
- ZIP/PDF-Index Export
- deutlicher Hinweis: Vorbereitung, keine Steuerberatung

### Akzeptanzkriterien

- Export ist reproduzierbar
- jede Datei ist im Manifest aufgeführt
- Berechtigungsprüfung vor Export
- keine fachliche Steuerentscheidung wird automatisch behauptet

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket ADV-001.

Ziel: Unterlagen für Steuerberater strukturiert vorbereiten, ohne Beratung zu simulieren.

Abhängigkeiten: FIN-007, OFF-001, FIN-003.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Export ist reproduzierbar
- jede Datei ist im Manifest aufgeführt
- Berechtigungsprüfung vor Export
- keine fachliche Steuerentscheidung wird automatisch behauptet

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## OFF-006 – Jahresberichte und Präsentationsinhalte aus bestehenden Vereinsdaten vorbereiten

| Feld | Wert |
|---|---|
| **Produktbereich** | Office |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | OFF-004, FIN-007, CLUB-005 |

### Ziel

Jahresberichte und Präsentationsinhalte aus bestehenden Vereinsdaten vorbereiten.

### Scope

- Template für Mitgliederversammlung/Jahresbericht
- Datenblöcke: Mitgliederentwicklung, Finanzen, Beschlüsse, Projekte, Termine
- Export zunächst als strukturierter PPTX-/DOCX-Generator oder kompatibler Renderer
- Preview und manuelle Bearbeitung vor Veröffentlichung

### Akzeptanzkriterien

- Zahlen enthalten Quelle/Zeitraum
- keine automatische Veröffentlichung
- Vorlagen versionierbar
- Export bleibt editierbar

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket OFF-006.

Ziel: Jahresberichte und Präsentationsinhalte aus bestehenden Vereinsdaten vorbereiten.

Abhängigkeiten: OFF-004, FIN-007, CLUB-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Zahlen enthalten Quelle/Zeitraum
- keine automatische Veröffentlichung
- Vorlagen versionierbar
- Export bleibt editierbar

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## PEO-003 – Einsatzplanung für Personal, Trainer und Übungsleiter ergänzen

| Feld | Wert |
|---|---|
| **Produktbereich** | People |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | PEO-001 |

### Ziel

Einsatzplanung für Personal, Trainer und Übungsleiter ergänzen.

### Scope

- Availability, Assignment, Shift/Session
- Vertretungsbezug
- Konfliktprüfung
- mobile Ansicht

### Akzeptanzkriterien

- Doppelbelegung wird erkannt
- Vertretungen sind nachvollziehbar
- Nutzer sieht eigene Einsätze
- Historische Einsätze bleiben erhalten

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket PEO-003.

Ziel: Einsatzplanung für Personal, Trainer und Übungsleiter ergänzen.

Abhängigkeiten: PEO-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Doppelbelegung wird erkannt
- Vertretungen sind nachvollziehbar
- Nutzer sieht eigene Einsätze
- Historische Einsätze bleiben erhalten

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## PEO-004 – Vergütungsgrundlagen strukturiert erfassen und exportieren, ohne Lohnabrechnung nachzubauen

| Feld | Wert |
|---|---|
| **Produktbereich** | People |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | PEO-003, FIN-001 |

### Ziel

Vergütungsgrundlagen strukturiert erfassen und exportieren, ohne Lohnabrechnung nachzubauen.

### Scope

- Vergütungsregel/Rate
- freigegebene Einsatz-/Stundenbasis
- Monatsübersicht
- Export für Buchhaltung/Steuerberater
- keine Steuer-/Lohnabrechnungslogik im Kern

### Akzeptanzkriterien

- Summen sind reproduzierbar
- Änderungen nach Freigabe werden auditierbar
- Exportformat dokumentiert
- Hinweis auf externe Lohn-/Steuerprozesse

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket PEO-004.

Ziel: Vergütungsgrundlagen strukturiert erfassen und exportieren, ohne Lohnabrechnung nachzubauen.

Abhängigkeiten: PEO-003, FIN-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Summen sind reproduzierbar
- Änderungen nach Freigabe werden auditierbar
- Exportformat dokumentiert
- Hinweis auf externe Lohn-/Steuerprozesse

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## ADV-002 – Externe Berater kontrolliert in die Zusammenarbeit einbinden

| Feld | Wert |
|---|---|
| **Produktbereich** | Finance/Advisor |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | ADV-001, SEC-004, SEC-005 |

### Ziel

Externe Berater kontrolliert in die Zusammenarbeit einbinden.

### Scope

- Beraterrolle mit minimalen Rechten
- zeitlich begrenzte Einladung
- Zugriff nur auf freigegebene Bereiche/Pakete
- Fragen-/Kommentarworkflow
- Audit

### Akzeptanzkriterien

- Berater sieht niemals nicht freigegebene Daten
- Zugriff kann sofort widerrufen werden
- Kommentare sind historisiert
- Tenant-Isolation getestet

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket ADV-002.

Ziel: Externe Berater kontrolliert in die Zusammenarbeit einbinden.

Abhängigkeiten: ADV-001, SEC-004, SEC-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Berater sieht niemals nicht freigegebene Daten
- Zugriff kann sofort widerrufen werden
- Kommentare sind historisiert
- Tenant-Isolation getestet

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-002 – Das Organisationsgedächtnis nutzbar machen

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | INT-001, OFF-003 |

### Ziel

Das Organisationsgedächtnis nutzbar machen.

### Scope

- Chronologie zu Vorgängen
- kontextuelle Suche: Beschlüsse, Aufgaben, Dokumente, Personen, Workflows
- Antworten müssen Quellen im System verlinken
- bei KI-Einsatz Retrieval nur im Tenant und nur mit Nutzerrechten

### Akzeptanzkriterien

- Antwort/Ergebnis zeigt zugrunde liegende interne Quellen
- keine Cross-Tenant-Daten
- ungeklärte Sachverhalte werden als solche markiert
- Such-/Retrieval-Tests vorhanden

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-002.

Ziel: Das Organisationsgedächtnis nutzbar machen.

Abhängigkeiten: INT-001, OFF-003.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Antwort/Ergebnis zeigt zugrunde liegende interne Quellen
- keine Cross-Tenant-Daten
- ungeklärte Sachverhalte werden als solche markiert
- Such-/Retrieval-Tests vorhanden

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-003 – Health Score für Organisation/Betrieb als transparentes Regelwerk umsetzen

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | INT-006, WF-005 |

### Ziel

Health Score für Organisation/Betrieb als transparentes Regelwerk umsetzen.

### Scope

- Score-Dimensionen z. B. Organisation, Dokumentation, Finanzen, Fristen, Auslastung
- versionierte Gewichtung
- jede Teilnote mit begründbaren Signalen
- konkrete Maßnahmen statt Blackbox-Wert

### Akzeptanzkriterien

- Score ist reproduzierbar
- Nutzer kann Ursachen sehen
- kein juristisches/steuerliches Urteil
- Score-Version wird gespeichert

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-003.

Ziel: Health Score für Organisation/Betrieb als transparentes Regelwerk umsetzen.

Abhängigkeiten: INT-006, WF-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Score ist reproduzierbar
- Nutzer kann Ursachen sehen
- kein juristisches/steuerliches Urteil
- Score-Version wird gespeichert

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-004 – Nachfolgefähigkeit und Übergabesicherheit messbar machen

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | C / P2 |
| **Abhängigkeiten** | INT-003, CLUB-008, INT-001 |

### Ziel

Nachfolgefähigkeit und Übergabesicherheit messbar machen.

### Scope

- Signale: Single-Owner-Aufgaben, fehlende Stellvertretung, unvollständige Übergaben, fehlende Dokumentation, überfällige Amtsübergaben
- Score + Maßnahmenliste
- Trend über Zeit

### Akzeptanzkriterien

- Score erklärt jede Abwertung
- Maßnahmen führen zu konkreten Fachobjekten
- Verbesserungen wirken nachvollziehbar auf Score
- keine personenbezogene Leistungsbewertung von Ehrenamtlichen

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-004.

Ziel: Nachfolgefähigkeit und Übergabesicherheit messbar machen.

Abhängigkeiten: INT-003, CLUB-008, INT-001.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Score erklärt jede Abwertung
- Maßnahmen führen zu konkreten Fachobjekten
- Verbesserungen wirken nachvollziehbar auf Score
- keine personenbezogene Leistungsbewertung von Ehrenamtlichen

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-005 – Forecast-/Digital-Twin-Modell für wirtschaftliche Szenarien entwickeln

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | D / P2 |
| **Abhängigkeiten** | FIN-008, INT-006 |

### Ziel

Forecast-/Digital-Twin-Modell für wirtschaftliche Szenarien entwickeln.

### Scope

- Szenario mit veränderbaren Treibern
- Baseline aus geprüften Finanz-/Betriebsdaten
- Ergebnisvergleich und Sensitivität
- klarer Hinweis auf Modellannahmen

### Akzeptanzkriterien

- Berechnung ist deterministisch
- Annahmen werden angezeigt
- Szenarien verändern keine Ist-Daten
- Export/Share als Bericht möglich

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-005.

Ziel: Forecast-/Digital-Twin-Modell für wirtschaftliche Szenarien entwickeln.

Abhängigkeiten: FIN-008, INT-006.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Berechnung ist deterministisch
- Annahmen werden angezeigt
- Szenarien verändern keine Ist-Daten
- Export/Share als Bericht möglich

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## INT-007 – Datenschutzkonformes Branchenbenchmarking ermöglichen

| Feld | Wert |
|---|---|
| **Produktbereich** | Intelligence |
| **Stufe / Priorität** | D / P2 |
| **Abhängigkeiten** | INT-006, OPS-005 |

### Ziel

Datenschutzkonformes Branchenbenchmarking ermöglichen.

### Scope

- separates Einwilligungs-/Rechtsgrundlagenkonzept
- Mindestgruppengröße und Aggregationsregeln
- keine Rückführbarkeit auf einzelne Organisationen
- Benchmark-Kohorten nach zulässigen Merkmalen
- Widerruf/Exclusion berücksichtigt

### Akzeptanzkriterien

- keine Benchmarkausgabe unter Mindestgruppengröße
- Tenant-Rohdaten niemals direkt sichtbar
- Einwilligungsstatus wird technisch erzwungen
- Datenschutzreview vor Produktivaktivierung

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket INT-007.

Ziel: Datenschutzkonformes Branchenbenchmarking ermöglichen.

Abhängigkeiten: INT-006, OPS-005.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- keine Benchmarkausgabe unter Mindestgruppengröße
- Tenant-Rohdaten niemals direkt sichtbar
- Einwilligungsstatus wird technisch erzwungen
- Datenschutzreview vor Produktivaktivierung

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## PART-001 – Verbände/Partner können freigegebene Vorlagen und Ressourcen bereitstellen, ohne Mandantendaten automatisch zu sehen

| Feld | Wert |
|---|---|
| **Produktbereich** | Partner |
| **Stufe / Priorität** | D / P2 |
| **Abhängigkeiten** | WF-001, SEC-004 |

### Ziel

Verbände/Partner können freigegebene Vorlagen und Ressourcen bereitstellen, ohne Mandantendaten automatisch zu sehen.

### Scope

- PartnerOrganization
- publizierbare Workflow-/Dokument-/Link-Vorlagen
- Opt-in pro Verein
- kein automatischer Datenzugriff
- Versionierung und Publisher-Identität

### Akzeptanzkriterien

- Verein entscheidet über Aktivierung
- Partner sieht ohne separate Freigabe keine Vereinsdaten
- Updates sind nachvollziehbar
- Vorlage kann organisationsspezifisch kopiert/angepasst werden

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket PART-001.

Ziel: Verbände/Partner können freigegebene Vorlagen und Ressourcen bereitstellen, ohne Mandantendaten automatisch zu sehen.

Abhängigkeiten: WF-001, SEC-004.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Verein entscheidet über Aktivierung
- Partner sieht ohne separate Freigabe keine Vereinsdaten
- Updates sind nachvollziehbar
- Vorlage kann organisationsspezifisch kopiert/angepasst werden

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```

## MIG-008 – Direkte Fremdsoftware-Connectoren nur nach realer Nachfrage umsetzen

| Feld | Wert |
|---|---|
| **Produktbereich** | Migration |
| **Stufe / Priorität** | D / P2 |
| **Abhängigkeiten** | MIG-007 |

### Ziel

Direkte Fremdsoftware-Connectoren nur nach realer Nachfrage umsetzen.

### Scope

- Connector priorisiert nach Anzahl Wechselkunden und Datenqualität
- Read-only Import zuerst
- Mapping auf bestehende Import-Staging-Pipeline
- kein Screen-Scraping ohne ausdrückliche technische/rechtliche Prüfung

### Akzeptanzkriterien

- Connector nutzt MIG-Framework
- Importvorschau und Rollback bleiben erhalten
- Fehler werden quarantänisiert
- Herstelleränderungen sind isoliert im Adapter

### Pflichttests

- Happy Path und mindestens ein Fehler-/Edge-Case
- Tenant-Isolation, sofern Fachdatensatz mandantenbezogen ist
- RBAC/Berechtigungsprüfung für geschützte Aktionen
- Validierung in Livewire-Komponente bzw. Action und passende UI-Zustände
- Regressionstest für relevante bestehende Funktion

### Cursor-Prompt

```text
Du arbeitest im EquiFlow-Laravel-Projekt. Implementiere ausschließlich Ticket MIG-008.

Ziel: Direkte Fremdsoftware-Connectoren nur nach realer Nachfrage umsetzen.

Abhängigkeiten: MIG-007.

Verbindlich: PHP 8.4 strict types, bestehende Architektur respektieren, Tenant-Isolation (Global Scope)/Policies beachten, keine sachfremden Refactorings. Nutze Boost search-docs vor Änderungen und lies .ai/rules. Ergänze erforderliche Laravel-Migrationen mit Factory, Livewire-Validierung, Policies/Gates, Pest-Tests und relevante .ai/rules-Einträge via record-rule. Bei unklarer Architektur die kleinste reversible Lösung wählen und als ADR unter Docu/architecture dokumentieren.

Akzeptanzkriterien:
- Connector nutzt MIG-Framework
- Importvorschau und Rollback bleiben erhalten
- Fehler werden quarantänisiert
- Herstelleränderungen sind isoliert im Adapter

Liefere am Ende: geänderte Dateien, Migrationen (+ Factory falls neu), ausgeführte Tests mit Ergebnis, manuelle Prüfschritte und bekannte Restpunkte.
```


# 11. Produktentscheidungen, die nicht ohne menschliche Freigabe geändert werden dürfen

1. EquiFlow ersetzt keine Rechts- oder Steuerberatung; Hinweise dienen der Orientierung und Vorbereitung.
2. Der Kern bleibt generisch für spätere Vereinsarten; Pferdesportlogik liegt in Fachmodulen.
3. Datenportabilität bleibt erhalten; Export und Migration werden nicht künstlich erschwert.
4. Benchmarking wird erst nach Datenschutzprüfung und ausreichender Datenbasis aktiviert.
5. Zahlungsanbieter werden über Adapter integriert; keine Providerabhängigkeit im Fachkern.
6. Dokumente, Beschlüsse, Aufgaben und Workflows müssen historisch nachvollziehbar bleiben.
7. Premium-/Moat-Funktionen werden nicht nur wegen Wettbewerb entwickelt, sondern nach nachgewiesenem Kundennutzen.

# 12. Empfohlener nächster Claude-Start

Wenn noch kein Code existiert, weiterhin mit **DEV-001** beginnen. Wenn v1.1 bereits teilweise umgesetzt ist, zuerst einen Repository-Abgleich gegen die Ticket-IDs durchführen. Danach nur fehlende Tickets der Stufe A umsetzen.

Für die neuen Differenzierungsgrundlagen ist die empfohlene Reihenfolge innerhalb Stufe A:

`OFF-001 → OFF-002 → WF-001 → WF-002 → EVT-003 → PEO-001 → INT-001 → INT-006`

Damit werden Historie, Prozessführung, Payment-Abstraktion und spätere Intelligence vorbereitet, ohne die sichtbare MVP-Oberfläche unnötig aufzublähen.

# Neues Modul: EquiFlow Time

**Ziel:** Arbeitsdienste, Mitarbeiter-Arbeitszeiten sowie Übungsleiter-/Trainerstunden direkt im System erfassen und mit Mitgliedern, Personen, Aufgaben, Veranstaltungen, Projekten, Abrechnung und Controlling verknüpfen.

EquiFlow Time basiert auf einer gemeinsamen Zeitbuchungs-Engine:

`Person + Zeit + Tätigkeit + Kontext + Nachweis + Freigabe + Auswertung`

Die Engine muss von Beginn an mandantenfähig, auditierbar und für spätere Erweiterungen offen sein.

## Datenmodell – architektonisch bereits im Pilot vorbereiten

Mindestens folgende Entitäten bzw. Konzepte vorsehen:

- `TimeEntry`
- `TimeEntryType`
- `TimeActivity`
- `TimeProject`
- `TimeApproval`
- `TimeCorrection`
- `QrTimeToken`
- `WorkDutyRequirement`
- Verknüpfungen zu `Person`, `Membership`, `Employee`, `Instructor`, `Event`, `Task`, `CostCenter`, `Organization`

Wichtige Felder eines Zeitdatensatzes:

- Organisation / Mandant
- Person
- Typ: Mitgliedsarbeitsdienst / Mitarbeiterarbeitszeit / Übungsleiterstunde / Helferdienst / Projektzeit
- Startzeit
- Endzeit
- Dauer
- Tätigkeit
- Projekt / Veranstaltung / Kurs
- Kostenstelle
- Erfassungsquelle: QR / Mobile / manuell / Import
- Erfasst durch
- Freigabestatus
- Korrekturhistorie
- optionaler Kommentar / Nachweis

## A – Pilot-MVP

### TIME-001 – Gemeinsames Zeitbuchungs-Datenmodell
**Priorität:** MUSS

**Akzeptanzkriterien**
- Zeitbuchungen sind mandantengetrennt.
- Jede Buchung ist eindeutig einer Person und einem Typ zugeordnet.
- Start, Ende und berechnete Dauer werden gespeichert.
- Buchungen können mit Aufgabe, Projekt, Veranstaltung und Kostenstelle verknüpft werden.
- Änderungen werden auditierbar protokolliert.

**Cursor-Prompt**
```text
Implementiere TIME-001 für EquiFlow.

Baue ein generisches, mandantenfähiges Datenmodell für Zeitbuchungen mit Eloquent/Laravel-Migrationen (PostgreSQL auf Laravel Cloud, SQLite lokal).
Nutze php artisan make:model TimeEntry --migration --factory für alle Entitäten.
Unterstütze mindestens MEMBERSHIP_DUTY, EMPLOYEE_TIME, INSTRUCTOR_TIME, EVENT_HELP und PROJECT_TIME.
Berücksichtige Tenant-Isolation (Global Scope), Policies, Audit-Logging via Observer, Validierung und Pest-Tests.
Noch keine komplexe Lohn- oder Arbeitszeitlogik implementieren.
```

### TIME-002 – Mobile Start-/Stop-Zeiterfassung
**Priorität:** MUSS  
**Abhängigkeit:** TIME-001

**Akzeptanzkriterien**
- Start/Stop ist mobil mit großen Touch-Flächen möglich.
- Aktive Buchung ist jederzeit sichtbar.
- Tätigkeit/Kontext kann vor Start ausgewählt werden.
- unbeabsichtigte Doppelbuchungen werden verhindert.

**Cursor-Prompt**
```text
Implementiere TIME-002.

Erstelle eine mobile-first Start-/Stop-Oberfläche als Livewire-Komponente.
Der Nutzer wählt Tätigkeit/Kontext und startet bzw. stoppt mit einem Tap (großes Touch-Target ≥ 44px).
Nutze bestehende Auth-, Tenant- und Policy-Strukturen und schreibe Livewire-Komponenten-Tests sowie Pest-Tests.
```

### TIME-003 – QR-Code Check-in / Check-out
**Priorität:** MUSS  
**Abhängigkeit:** TIME-001, TIME-002

**Akzeptanzkriterien**
- QR-Code kann Veranstaltung, Arbeitsdienst, Projekt oder Trainingskontext referenzieren.
- Scan öffnet direkt die mobile Erfassung.
- Start/Stop wird nach Authentifizierung eindeutig der Person zugeordnet.
- QR-Tokens sind widerrufbar und sicher.
- Jeder Scan ist auditierbar.

**Cursor-Prompt**
```text
Implementiere TIME-003.

Baue eine sichere QR-Code-basierte Start-/Stop-Zeiterfassung.
QR-Codes verwenden sichere, widerrufbare Tokens statt frei manipulierbarer IDs.
Berücksichtige Tenant-Isolation (Global Scope), Rate Limiting via RateLimiter-Facade, Audit-Log via Observer, mobile UX (Livewire) und Sicherheitstests.
```

### TIME-004 – Mitglieder-Arbeitsdienstkonto
**Priorität:** MUSS

**Akzeptanzkriterien**
- Pro Mitglied und Zeitraum kann ein Soll hinterlegt werden.
- Freigegebene Arbeitsdienste werden automatisch angerechnet.
- Anzeige von Soll, Ist und Rest.
- Export/Auswertung pro Zeitraum.

**Cursor-Prompt**
```text
Implementiere TIME-004.

Ergänze ein Arbeitsdienstkonto für Vereinsmitglieder.
Pro Mitglied und Geschäftsjahr bzw. Zeitraum wird ein Sollwert hinterlegt.
Freigegebene MEMBERSHIP_DUTY-Buchungen werden automatisch angerechnet.
Zeige Soll, Ist, Rest und Historie.
```

### TIME-005 – Übungsleiter-/Trainerstunden
**Priorität:** SOLL im Pilot, MUSS direkt danach

**Akzeptanzkriterien**
- Trainer kann Stunden mobil oder per QR erfassen.
- Zuordnung zu Kurs, Veranstaltung, Tätigkeit und Kostenstelle.
- Monatsübersicht je Trainer.
- Freigabestatus.
- Export für organisatorische Abrechnungsvorbereitung.

**Cursor-Prompt**
```text
Implementiere TIME-005.

Erweitere EquiFlow Time um Übungsleiter- und Trainerstunden.
Buchungen müssen Kursen, Veranstaltungen, Tätigkeiten und Kostenstellen zugeordnet werden können.
Erstelle Monatsübersicht, Freigabestatus und Export.
Keine eigene Lohnabrechnung implementieren.
```

## B – nach 10 Kunden

### TIME-006 – Zeitfreigabe und Korrekturworkflow
- Freigeben / ablehnen
- Korrektur nur mit Begründung
- Originalwert bleibt nachvollziehbar
- Sammelfreigabe
- Nutzer sieht Status

### TIME-007 – Veranstaltungs- und Helferdienste
- QR-Code pro Helferdienst oder Einsatzort
- automatische Anrechnung auf Arbeitsdienstkonto
- Besetzungs- und Stundenübersicht für Veranstaltungsleitung

### TIME-008 – Auswertungen & Exporte
- Filter nach Person, Typ, Tätigkeit, Projekt, Veranstaltung, Kostenstelle, Zeitraum
- CSV/XLSX-Export
- Summen pro Person/Kategorie
- Übergabe an Finance und Steuerberater-Paket

## C – nach 50 Kunden

### TIME-009 – Mitarbeiter-Arbeitszeit erweitert
Mögliche Funktionen:
- Pausen
- Sollzeiten
- Abwesenheiten
- Überstunden
- Freigaben
- Dienst-/Schichtbezug

**Hinweis:** Vor diesem Ausbau müssen die arbeitsrechtlichen Anforderungen je Zielmarkt fachlich geprüft werden. EquiFlow gibt hierzu nur organisatorische Unterstützung und keine Rechtsberatung.

### TIME-010 – Finance- und Controlling-Verknüpfung
Beispiele:
- Trainerstunden pro Kurs
- Personalkosten je Unterrichtsstunde
- Arbeitsstunden je Veranstaltung
- Aufwand je Projekt
- Umsatz je Trainerstunde
- Deckungsbeitrag je Kurs

## D – später

### TIME-011 – Externe Lohn-/Zeitwirtschafts-Connectoren
Direkte Übergabe an externe Systeme über API/Adapter.

### TIME-012 – Zeitbasierte Intelligence
Anomalien und Managementhinweise auf Basis aggregierter Zeitdaten.

# Ergänzungen zu bestehenden Modulen

## Verein
- Arbeitsdienst-Soll/Ist im Mitgliedsprofil
- offene Arbeitsstunden im Vereinsdashboard
- automatische Anrechnung aus Veranstaltungen

## Events
- Helferdienste und Trainerstunden direkt einem Event zuordnen
- QR-Code je Helferstation oder Dienst

## People
- Mitglieder, Mitarbeiter und Übungsleiter nutzen dieselbe Personengrundlage
- keine Personenduplikate für Zeitbuchungen

## Finance
- freigegebene Stunden als Grundlage für organisatorische Abrechnungsvorbereitung
- Kostenstellenbezug
- Export in Steuerberater-Paket
- keine eigene Lohn- oder Steuerberatung

## Intelligence
- spätere KPIs zu Stundenaufwand je Kurs, Veranstaltung und Betriebsbereich

# Zusätzlicher USP

EquiFlow verbindet die digitale Verwaltung mit der realen Arbeit vor Ort:

> **QR-Code scannen, Arbeit erledigen, Zeit automatisch im richtigen Kontext dokumentieren.**

Damit entfallen manuelle Arbeitsdienstlisten, nachträgliche Excel-Erfassung und das Zusammensuchen von Übungsleiterstunden.





