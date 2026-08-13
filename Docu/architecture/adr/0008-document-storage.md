# ADR-0008: Dokumentablage über Storage-Disk und signierte Download-URLs

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** CORE-005
- **Supersedes:** —

## Kontext

EquiFlow braucht eine mandantensichere Dokumentablage, bevor Fachmodule Dateien anhängen (CORE-006 bewusst später). Die Leitplanke verbietet Binärdateien in der Datenbank. Kickoff: Disk `local` in Dev/Tests, `s3` über Env in der Cloud; kein Public-Pfad; keine ausführbaren Downloads. Cross-Tenant-Dateizugriff soll 403 liefern (Policy + Signed URL), nicht nur 404 über den Global Scope.

## Entscheidung

- `Document` und `DocumentVersion` sind UUID-Fachaggregate mit `organization_id` NOT NULL und `BelongsToOrganization`. Metadaten (Titel, MIME, Größe, Originalname, zufälliger Storage-Key) liegen in der DB; Bytes auf der konfigurierten Disk (`config('documents.disk')`, Default `local`, Cloud `s3`).
- Storage-Keys sind `{organization_id}/{uuid}` ohne Originaldateiname und ohne ausführbare Endung. MIME- und Größen-Whitelist stehen in `config/documents.php`.
- Downloads laufen über `URL::temporarySignedRoute` (`documents.download`) und `Storage::download()` mit `Content-Disposition: attachment`. Kein Public-Disk, kein `inline` für Downloads. Fremdmandant oder fehlende Signatur: HTTP 403.
- Rechte: Permission `documents.manage` (Admin). `DocumentPolicy` für view/create/update/delete.

## Konsequenzen

- Positiv: Disk ist per Env tauschbar; Policy gilt auch beim Download; Dateien sind ohne Ratebarkeit und ohne Public-URL nicht ausführbar.
- Negativ / Follow-up: Download-Traffic läuft über die App (kein direktes S3-GET). Polymorphe Verknüpfungen und Kategorien erst CORE-006. PHP/Webserver-Upload-Limits bei großen Dateien separat setzen.

## Alternativen

1. `Storage::temporaryUrl()` direkt auf S3 — umgeht die Policy nach URL-Ausgabe; lokal ohne `serve` nicht einheitlich; Content-Disposition schwerer zu erzwingen.
2. Public-Disk `/storage` — rätbare Pfade und direkte Ausführbarkeit.
3. Bytes in der Datenbank — widerspricht der Leitplanke und skaliert nicht.
