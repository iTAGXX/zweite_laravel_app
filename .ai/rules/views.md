---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Flux validation errors and x-ui state components
Livewire field errors: flux:input with label (wraps flux:field) or explicit flux:error. Form-level summary: x-ui.form-errors. Empty/error/loading: x-ui.empty-state, x-ui.error-state, x-ui.loading-state. Do not invent a second error UI.

## Reuse x-ui states and org switcher placeholder
Reuse x-ui.empty-state, x-ui.error-state, and x-ui.loading-state. The org switcher is x-organization-switcher-placeholder until SEC-005. Touch-targets in the app shell use min-h-11 (44px).
