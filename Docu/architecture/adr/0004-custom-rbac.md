# ADR-0004: Eigenes RBAC statt Spatie Permission

- **Status:** accepted
- **Datum:** 2026-08-13
- **Ticket:** SEC-004
- **Supersedes:** —

## Kontext

Rollen und Permissions müssen je Organisation gelten. Kickoff legt die Rollennamen fest (admin, treasurer, member, staff). Eine Extra-Dependency (z. B. spatie/laravel-permission) braucht menschliche Freigabe und ist für den R0-Katalog unnötig.

## Entscheidung

Eigenes Katalog-Modell: `roles`, `permissions`, Pivot `permission_role` (`RolePermission`). Integer-PKs wie bei Memberships. Rollen sind global (Slug), nicht mandantenbezogen. Die aktive Rolle kommt aus `organization_memberships.role_id` in der Session-Organisation (ADR-0003).

Gates heißen wie die Permission-Slugs (`users.manage`, `finance.view`). Policies (z. B. `InvitationPolicy`) prüfen `User::hasPermission()`. Fehlt die Permission, ist die Antwort 403. UI-`@can` blendet nur aus.

## Konsequenzen

- Positiv: kein neues Composer-Paket; Treasurer kann Finanzen ohne Benutzerverwaltung; Staff sieht Finanzmodule weder in der Nav noch per URL.
- Negativ / Follow-up: Permission-Katalog wächst mit Fachmodulen. Spatie später nur mit neuem ADR und Freigabe.

## Alternativen

1. spatie/laravel-permission — Teams/Org-Kontext möglich, aber Extra-Dependency.
2. Rolle als String auf der Membership ohne Permission-Tabelle — Treasurer vs. Admin nicht feingranular.
