# Traps — Things That Will Bite You

> Read always. These are non-obvious gotchas discovered through bug-hunting.

## ❌ Database

### `condition` enum is `['ok', 'fault']` ONLY
On `checkin_batches`, `checkout_batches`, `return_batches` items table, the `condition` column accepts only `'ok'` or `'fault'`. There is a CHECK constraint. **Never use `'new'`** — the migration does not allow it.

```php
// ❌ Will throw "CHECK constraint failed: condition"
CheckinBatchItem::create([..., 'condition' => 'new']);

// ✅ Use 'ok' for new/received items
CheckinBatchItem::create([..., 'condition' => 'ok']);
```

### Don't add new migrations to alter existing tables
The project convention is **edit migrations in-place** + `migrate:fresh --seed`. Adding a new migration to add a column to an existing table violates this convention and breaks the workflow. If you need a new column, find the original `create_*` migration and add it there.

## ❌ Models & Relations

### `CheckinBatchItem::receivedByUser` (NOT `receivedBy`)
The actual relation name on `CheckinBatchItem` is `receivedByUser` (model = `User::class`). If you write `$item->receivedBy` it will silently return null. Look at `app/Models/CheckinBatchItem.php` line 53-56 for the canonical name.

### Checkin batches are NEW asset creation, NOT asset selection
**This is the most important business-logic trap.** `CheckinBatch` (Nhập kho) means **producing / acquiring new devices and putting them in the warehouse**. Items are pre-created by `ProductionBatchService::createFromProduction()` (1 batch + N fresh `Asset` records + N `CheckinBatchItem` rows).

In the form Repeater:
- ❌ **NEVER use `Select::make('asset_id')->relationship('asset', 'serial_no')`** — that pattern is for `CheckoutBatchForm` and `ReturnBatchForm` where the user picks an existing asset to dispatch/return
- ✅ Use `Hidden::make('asset_id')` to preserve the relationship, plus `TextEntry::make('asset_serial')->state(fn ($record) => $record?->asset?->serial_no)` to display the serial read-only
- ✅ The user only edits `condition` and `condition_note` (post-creation quality check)
- ✅ `->addable(false)->deletable(false)` — items are owned by the batch-creation flow, not the form

`is_received`, `received_by`, `received_at` are **scan-time fields** set by `POST /api/v1/checkin-batches/{id}/scan` from the mobile app. **Do not expose them in the form.**

If the user is "bringing back" a device from an event, that's a `ReturnBatch` (with a source `CheckoutBatch`), NOT a `CheckinBatch`. Confusing these two is a fundamental logic error.

## ❌ Filament Shield

### `php artisan shield:generate --all` silently rolls back in this env
The command reports success ("# Permissions generated: 0") but **no rows are actually inserted** into the `permissions` table. This is a known issue in this environment.

**Workaround**: call `BezhanSalleh\FilamentShield\Support\Utils::createPermission($name)` directly. The Utils API uses the same `Permission::firstOrCreate` internally and persists correctly. See `AppBootstrapCommand::generateShieldPermissions()` for the working pattern.

Resource permissions have nested structure (not flat):
```php
// ❌ Gives you lowercase action names like "view", "create", ...
array_keys($resource['permissions'])

// ✅ Gives you real permission names like "ViewAny:Order", "Create:Order"
array_map(fn($p) => $p['key'], $resource['permissions'])
```

Page/Widget permissions are flat (key = permission name):
```php
// ✅ Works
array_keys($page['permissions'])
```

## ❌ Mobile (Expo 57)

### `BarCodeScanner` is deprecated — use `CameraView`
```tsx
// ❌ Deprecated, throws warnings
import { BarCodeScanner } from 'expo-camera';

// ✅ Current API
import { CameraView, useCameraPermissions } from 'expo-camera';
const [permission, requestPermission] = useCameraPermissions();
```

The `ScannerModal.tsx` component is the canonical reference for the right pattern.

### Navigation is NOT React Navigation
Don't add `@react-navigation/native` or any routing libs. The project uses a switch statement in `App.tsx` with state. Adding new screens requires:
1. Add screen name to `ScreenName` union
2. Add state for any IDs the screen needs (e.g. `selectedCheckinBatchId`)
3. Add a `case` in the switch
4. Add an action card to `HomeScreen.tsx` if it's a top-level screen

## ❌ Testing

### `migrate:fresh` cannot run in Pest tests
SQLite `:memory:` does not allow `VACUUM` (which `migrate:fresh` runs) inside a transaction. The `RefreshDatabase` trait wraps every test in a transaction → `migrate:fresh` will throw `cannot VACUUM from within a transaction`.

**Solution**: don't test the `app:bootstrap` command end-to-end. Test its components in isolation:
- Test the user creation contract (`User::updateOrCreate` + `Role::firstOrCreate`)
- Test `Utils::createPermission` persists
- Test role seeding logic
- Run the full command manually as a smoke test (it works fine in dev)

### `npx tsc --noEmit` crashes on this project
TypeScript 6.0.3 has a known stack-overflow bug with certain code patterns. The crash is in the compiler, not your code. Skip `tsc --noEmit` and validate mobile via `npx expo export -p web` instead (faster + more useful).
