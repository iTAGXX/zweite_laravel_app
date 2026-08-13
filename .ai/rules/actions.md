---
paths:
  - 'app/Actions/**/*.php'
---

# Actions

## Actions use handle except Fortify contracts
New application Actions use a single handle() method. Fortify actions under app/Actions/Fortify keep their contract methods (create, reset, …) and must not be rewritten to handle().
