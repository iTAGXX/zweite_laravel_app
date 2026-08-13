---
paths:
  - 'resources/views/layouts/**/*.blade.php'
---

# Layouts

## Desktop sidebar, mobile bottom nav
Keep the Flux sidebar for desktop. Add Livewire `mobile-navigation` as the mobile bottom bar (lg:hidden, min-h-11 / 44px touch targets). Do not rebuild the starter-kit shell.

## Org switcher is Livewire not a placeholder
Use Livewire organization-switcher in the app shell. It calls SwitchOrganization, which 403s unless the user has a membership. Do not bring back the disabled placeholder. Keep min-h-11 touch targets.
