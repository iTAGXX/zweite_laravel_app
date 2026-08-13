---
paths:
  - resources/views/partials/head.blade.php
---

# Partials

## PWA meta belongs in the shared head
Manifest link, theme-color, Apple standalone meta, and offline-write toast copy live in partials/head so every app/auth/error layout stays installable. Do not add a second PWA head.
