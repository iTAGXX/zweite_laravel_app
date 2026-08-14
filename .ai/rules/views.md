---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Flux validation errors and x-ui state components
Livewire field errors: flux:input with label (wraps flux:field) or explicit flux:error. Form-level summary: x-ui.form-errors. Empty/error/loading: x-ui.empty-state, x-ui.error-state, x-ui.loading-state. Do not invent a second error UI.

## Reuse x-ui states
Reuse x-ui.empty-state, x-ui.error-state, and x-ui.loading-state. Touch-targets in the app shell use min-h-11 (44px).

## Member invites go through Livewire members
Invite members with livewire:invite-members on the members page. @can remains UX only; sendInvitation authorizes InvitationPolicy create. Empty pending list uses x-ui.empty-state.

## Audit Livewire component is audit-log
The audit admin page is resources/views/audit.blade.php wrapping livewire:audit-log. Do not name the Livewire component audit; that collides with the page view (same trap as members vs invite-members).

## Organization user list lives in invite-members
/members lists login users (User + OrganizationMembership) in livewire:invite-members, not a component named members (collides with the page view) and not Person/CLUB-001. Query memberships by the session organization_id (join table has no global scope), eager-load user and role, and authorize users.manage. Empty list uses x-ui.empty-state.
