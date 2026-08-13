---
paths:
  - 'app/Http/Controllers/*.php'
---

# Controllers

## Document downloads are signed attachments
DownloadDocumentController streams via Storage::download (Content-Disposition: attachment). Always attachment, never inline. Invalid/expired signature and foreign-tenant IDs return 403. Do not use implicit Document binding for this route.
