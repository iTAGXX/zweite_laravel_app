---
paths:
  - 'app/Http/Middleware/*.php'
---

# Middleware

## Security headers on every response
Keep SecurityHeaders on the global stack (bootstrap/app.php append). Responses must send X-Content-Type-Options: nosniff, X-Frame-Options: DENY, and Referrer-Policy: strict-origin-when-cross-origin. Do not drop these headers or switch Frame-Options to SAMEORIGIN without an ADR.
