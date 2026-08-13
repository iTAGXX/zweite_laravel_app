---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Flux validation errors and x-ui state components
Livewire field errors: flux:input with label (wraps flux:field) or explicit flux:error. Form-level summary: x-ui.form-errors. Empty/error/loading: x-ui.empty-state, x-ui.error-state, x-ui.loading-state. Do not invent a second error UI.
