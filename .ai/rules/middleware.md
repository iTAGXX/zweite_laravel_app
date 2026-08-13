---
paths:
  - 'app/Http/Middleware/*.php'
---

# Middleware

## Security headers on every response
Keep SecurityHeaders on the global stack (bootstrap/app.php append). Responses must send X-Content-Type-Options: nosniff, X-Frame-Options: DENY, and Referrer-Policy: strict-origin-when-cross-origin. Do not drop these headers or switch Frame-Options to SAMEORIGIN without an ADR.

## Organization context lives in the session
Org context is the session key active_organization_id (ADR-0003), mirrored in Context via EnsureActiveOrganization::set/id/forget. Do not switch to URL segments without a new ADR.
