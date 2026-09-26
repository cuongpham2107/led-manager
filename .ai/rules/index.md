# .ai/rules — Project Knowledge Base

> Persistent memory for agents working on the LED Manager project.
> Read this index first, then read the rule files whose globs cover the paths you're touching.

## File registry

| File | Glob (when to read) | What's inside |
| --- | --- | --- |
| `project.md` | `**/*` (always) | Stack, branches, user prefs, conventions overview |
| `architecture.md` | `app/`, `mobile/`, `database/`, `routes/`, `tests/` | File map, architectural patterns, key modules |
| `conventions.md` | `**/*.php`, `**/*.ts`, `**/*.tsx`, `**/*.blade.php` | Coding style, naming, structure rules |
| `traps.md` | `**/*` (always) | Known bugs, gotchas, things to NEVER do |
| `decisions.md` | `**/*` | Phase history, key design decisions, git state |
| `bootstrap.md` | `app/Console/Commands/**`, `database/seeders/**` | `app:bootstrap` command, seeding workflow |
| `e2e.md` | `scripts/e2e/**` | browser-use E2E: DeepSeek thinking mode vs tool_choice, server prerequisites |
| `tests.md` | `tests/**` | Pest pitfalls — e.g. bare `actingAs()` must be imported |
| `services.md` | `database/seeders/**`, `tests/**`, `app/Services/**` | Availability/stock traps caused by seed data |
| `app.md` | `app/**/*Return*` | Return flows must reuse an open RepairLog (no duplicate repair tickets) |

## How to use

1. **Before any non-trivial task**: read `project.md` + `traps.md`
2. **Before editing files**: read the rule file whose glob matches your path
3. **When discovering something new**: update the relevant rule file
4. **For app bootstrap / seed questions**: read `bootstrap.md`

## Update discipline

- One topic per file. If a rule file grows past ~300 lines, split it.
- Always update via `edit` (preserve git diff-ability); avoid full rewrites.
- When a phase completes, append to `decisions.md` rather than rewriting history.
