# Bootstrap & Seeding

> Read when working on `app/Console/Commands/`, `database/seeders/`, or `database/migrations/`.

## One-shot bootstrap: `php artisan app:bootstrap`

Re-initializes the dev environment from scratch in a single command.

```bash
php artisan app:bootstrap

# With custom admin credentials
php artisan app:bootstrap --email=root@x.com --name=Root --password=secret123

# Fast re-bootstrap (DB already at right schema, only re-seed + re-ensure user)
php artisan app:bootstrap --skip-migrate --skip-permissions
```

### Options

| Option | Default | Effect |
|---|---|---|
| `--email` | `admin@admin.com` | Super admin email |
| `--name` | `admin` | Super admin name |
| `--password` | `password` | Super admin password (min 8 chars) |
| `--skip-migrate` | false | Skip `migrate:fresh` |
| `--skip-seed` | false | Skip `db:seed` |
| `--skip-permissions` | false | Skip Shield permission generation |

### What it does

1. **`migrate:fresh --force`** — truncates + recreates schema
2. **`shield:setup --force --starred`** — ensures Shield config
3. **Generate permissions via `Utils::createPermission()`** — 278 permissions across 22 resources + pages + widgets (see `traps.md` for why we don't use `shield:generate` directly)
4. **`db:seed`** — `LedOsDataSeeder` runs, creates roles + syncs permissions to roles
5. **Ensure super admin** — `Role::firstOrCreate('super_admin')` + `User::updateOrCreate($email)` + `syncRoles`

### Login after bootstrap

- URL: `/admin`
- Email: `admin@admin.com`
- Password: `password`
- Role: `super_admin` (278 perms = full access)

## Idempotency

`app:bootstrap` is **safe to run repeatedly**:
- `migrate:fresh` always produces same state
- `Role::firstOrCreate` and `User::updateOrCreate` are idempotent
- Permission generation uses `firstOrCreate` internally

## Demo users (created by `LedOsDataSeeder`)

| Email | Role | Password |
|---|---|---|
| `admin@ledmanager.com` | `super_admin` | `password` |
| `sales1@ledmanager.com` | `sales_executive` | `password` |
| `sales2@ledmanager.com` | `sales_executive` | `password` |
| `wh@ledmanager.com` | `warehouse_manager` | `password` |
| `tech@ledmanager.com` | `technician` | `password` |
| `accountant@ledmanager.com` | `accountant` | `password` |

Plus the `admin@admin.com` user created by `app:bootstrap` itself (also super_admin).

## Seeding workflow

```bash
# After editing a migration in-place
php artisan app:bootstrap

# Or, faster: just re-seed without migrate
php artisan app:bootstrap --skip-migrate
```

**Never** add a new migration to alter an existing table — edit the original `create_*` migration and run `app:bootstrap` (or `migrate:fresh --seed`).
