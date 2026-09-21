---
paths:
  - 'tests/**'
---

# Tests

## Bare actingAs() must be imported from Pest\Laravel
actingAs() lives in namespace Pest\Laravel and is NOT a global function. Test files calling bare `actingAs($user)` must add:
    use function Pest\Laravel\actingAs;
Without the import the test dies at runtime with "Call to undefined function actingAs()".
The method form `$this->actingAs($user)` needs no import and works at runtime, but intelephense reports "Undefined method 'actingAs'" on it project-wide — that one IS a static-analysis false positive (intelephense cannot bind $this inside Pest closures), so do not chase it.
