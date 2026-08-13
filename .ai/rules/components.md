---
paths:
  - resources/views/components/offline-notice.blade.php
---

# Components

## Reuse x-offline-notice for connectivity
Show connectivity with x-offline-notice (x-ui.error-state). Skip it on pwa.offline. Livewire write failures toast via the pwa-offline-write-* meta tags in partials/head — do not invent a second offline UI.
