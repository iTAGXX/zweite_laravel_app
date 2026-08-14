---
paths:
  - 'database/migrations/*.php'
---

# Migrations

## ALTER TABLE Migrationen auf MySQL/Cloud idempotent schreiben
MySQL-DDL ist nicht transaktional. Wenn ein Deploy nach einem ALTER TABLE abbricht, steht die Spalte schon in der DB, die Migration aber nicht in der migrations-Tabelle. Beim nächsten Deploy läuft dieselbe Datei erneut und scheitert mit 'Duplicate column'. Spalten in Schema::table-Migrationen daher immer mit Schema::hasColumn prüfen, bevor sie angelegt oder gedroppt werden.

## JSON-Spalten ohne DB-Default anlegen, Zeilen per UPDATE befüllen
MySQL verbietet String-Literale als DEFAULT auf JSON-Spalten (Error 1101). JSON-Spalten immer mit ->nullable() ohne ->default() anlegen. Bestehende Zeilen danach sofort per DB::table()->whereNull()->update() befüllen. Neue Model-Instanzen erhalten den Default über Organization::$attributes, nicht über den DB-Default.
