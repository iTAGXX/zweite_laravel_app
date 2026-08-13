---
paths:
  - 'app/Providers/*.php'
---

# Providers

## Password reset link requests are enumeration-safe
Failed Fortify password-reset link requests must look like success (EnumerationSafeFailedPasswordResetLinkRequestResponse bound to FailedPasswordResetLinkRequestResponse). Do not reveal whether the email exists. MAIL_MAILER=log in local/dev.
