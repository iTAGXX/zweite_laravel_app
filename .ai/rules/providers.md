---
paths:
  - 'app/Providers/*.php'
---

# Providers

## Password reset link requests are enumeration-safe
Failed Fortify password-reset link requests must look like success (EnumerationSafeFailedPasswordResetLinkRequestResponse bound to FailedPasswordResetLinkRequestResponse). Do not reveal whether the email exists. MAIL_MAILER=log in local/dev.

## Register permission gates from enum
Register one Gate per PermissionName case in AppServiceProvider. Do not add spatie/laravel-permission without a new ADR and dependency approval.
