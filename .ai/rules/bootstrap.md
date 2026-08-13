---
paths:
  - bootstrap/app.php
---

# Bootstrap

## Tenant middleware before route binding
Keep EnsureActiveOrganization before SubstituteBindings via prependToPriorityList. Otherwise implicit binding loads tenant models before the org context exists and every ID becomes 404.
