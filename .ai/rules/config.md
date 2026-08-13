---
paths:
  - config/session.php
---

# Config

## Session cookie HttpOnly and SameSite Lax
Session cookie stays HttpOnly and SameSite=Lax. Do not turn http_only off or change same_site away from lax. Secure is env-driven: set SESSION_SECURE_COOKIE=true on HTTPS staging/production, leave unset for local HTTP.
