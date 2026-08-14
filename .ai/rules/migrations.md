---
paths:
  - 'database/migrations/*.php'
---

# Migrations

## ALTER TABLE Migrationen auf MySQL/Cloud idempotent schreiben
MySQL-DDL ist nicht transaktional. Wenn ein Deploy nach einem ALTER TABLE abbricht, steht die Spalte schon in der DB, die Migration aber nicht in der migrations-Tabelle. Beim nächsten Deploy läuft dieselbe Datei erneut und scheitert mit 'Duplicate column'. Spalten in Schema::table-Migrationen daher immer mit Schema::hasColumn prüfen, bevor sie angelegt oder gedroppt werden.
