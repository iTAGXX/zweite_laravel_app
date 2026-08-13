# ADR-0006: Organisationstyp als Enum, Module als JSON auf organizations

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** CORE-001
- **Supersedes:** —

## Kontext

Mandanten müssen Verein, Betrieb oder Mischform sein. Fachmodule (club/stable) sollen per Feature Flag ein- und ausgeschaltet werden. Kickoff: kleinste reversible Lösung; Katalog klein halten. Livewire-first, keine REST-API — „API blockieren“ heißt Route/Action des Moduls liefert 403.

## Entscheidung

`organizations.type` ist ein String mit Eloquent-Enum-Cast (`OrganizationType`: club, stable, mixed). Aktive Module liegen als JSON-Spalte `enabled_modules` auf derselben Tabelle, gecastet mit `AsEnumCollection` von `ModuleName` (club, stable). Defaults: club → `[club]`, stable → `[stable]`, mixed → `[club, stable]`.

Modul-Routen nutzen Middleware `EnsureModuleEnabled` (Alias `module:club`). Navigation blendet deaktivierte Module nur aus; die Serverprüfung bleibt. Organisationseinstellungen gelten ausschließlich für die aktive Session-Organisation (`OrganizationPolicy` + `users.manage`).

## Konsequenzen

- Positiv: keine Extra-Tabelle; Typ und Flags sind am Mandanten; bestehende Tests bleiben grün (Factory-Default mixed).
- Negativ / Follow-up: JSON ist nicht relational. Eine eigene `organization_modules`-Tabelle nur, wenn Abfragen über alle Mandanten nach Modul nötig werden. Finance bleibt Gate-only (`finance.view`), kein Typ-Modul.

## Alternativen

1. Eigene Feature-Flag-Tabelle — reversibel später, für zwei Flags unnötig.
2. Typ impliziert Module ohne Override — zu starr für Mischform mit abgeschaltetem Einzelmodul.
