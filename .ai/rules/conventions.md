# Coding Conventions

> Read before writing/editing code.

## PHP

- **Version**: 8.4 — use modern syntax: typed properties, constructor promotion, readonly classes, enums
- **Type hints**: explicit return types on every method
- **Comments**: prefer PHPDoc blocks over inline comments; only use inline for genuinely complex logic
- **Control structures**: always use curly braces, even for single-line bodies
- **Strings**: prefer single quotes unless interpolation is needed
- **Imports**: ordered (Pint will enforce), fully qualified in PHPDoc only

### Enums

```php
enum ContractStatus: string
{
    case Draft = 'draft';
    case Signed = 'signed';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string { /* HasLabel */ }
    public function getColor(): string { /* HasColor */ }
    public function getIcon(): string { /* HasIcon */ }
}
```

- **TitleCase keys** (e.g. `BestCustomer`, not `BEST_CUSTOMER`)

### Models

- **Relations**: name after the related model, e.g. `receivedByUser()` not `receivedBy()` (the actual relation on `CheckinBatchItem`)
- **Fillable**: explicit array, no `$guarded = []`
- **Casts**: array-style `$casts`, prefer enum casts `protected $casts = ['status' => ContractStatus::class]`
- **Spatie**: use `HasRoles` trait; roles go in `web` guard

### Filament v5 Resources

- **Page-level actions** → `Pages/{List,X}{Model}.php::getHeaderActions()`
- **Row-level actions** → `Tables/{Model}Table.php::recordActions()` with `RecordActionsPosition::BeforeCells`
- **Bulk actions** → `toolbarActions([BulkActionGroup::make([...])])`
- **Table header actions** → `headerActions([...])` only when action operates ON the table's data; otherwise use the ListPage header
- **Enums in filters/columns** → use `options(EnumClass::class)`

### API Controllers

```php
// Standard response shape
return response()->json([
    'success' => true,
    'data' => $resource,
    'pagination' => [...],  // when paginated
    'message' => '...',     // optional
]);
```

- **Validation**: use `$request->validate()` or FormRequest
- **Authorization**: `Gate::authorize()` or `$this->authorize()` in constructor
- **Idempotent endpoints** (scans, status updates): 422 if already in target state, 404 if not found

## Mobile (TypeScript / React Native / Expo 57)

- **Navigation**: switch in `App.tsx` with `ScreenName` union, **NO React Navigation**
- **Scanner**: `CameraView` + `useCameraPermissions` from `expo-camera` (NEVER `BarCodeScanner` — deprecated)
- **Haptics**: `import * as Haptics from 'expo-haptics'` for scan feedback
- **State**: `useState`/`useEffect` per-screen, no global state library
- **API**: `apiClient` from `src/services/apiClient.ts`, all calls return `{data: {success, data, ...}}`
- **Auth**: `useAuth()` hook from `src/context/AuthContext.tsx`
- **Types**: ALL interfaces in `src/types/index.ts`, add to this file when introducing new entities
- **Icons**: `lucide-react-native` (not `react-native-vector-icons`)
- **Styles**: `StyleSheet.create()` per component, no Tailwind/styled-components

## Code style enforcement

- **PHP**: `vendor/bin/pint --dirty --format agent` (run before every commit)
- **TypeScript**: no formatter configured — just be consistent with existing files (Prettier-like 2-space indent, double quotes, semicolons, trailing commas)
