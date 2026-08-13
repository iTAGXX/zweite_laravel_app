---
paths:
  - public/sw.js
---

# Public

## Service worker caches GET shell only
The PWA service worker at /sw.js only handles GET. Livewire updates are POST and must fail visibly when offline — never cache writes. Navigation misses fall back to /offline.
