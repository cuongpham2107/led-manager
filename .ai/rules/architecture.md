# Architecture & File Map

> Read before touching any file in `app/`, `mobile/`, `database/`, `routes/`, or `tests/`.

## Backend (`app/`)

```
app/
├── Enums/                # TitleCase PHP enums, HasLabel/HasColor/HasIcon
├── Models/               # Eloquent + Spatie HasRoles
│                          # Relation names: e.g. receivedByUser (not receivedBy)
├── Services/             # Business logic (ProductionBatchService, LedCalculationService)
├── Filament/Resources/   # Admin panel — standard v5 structure:
│   └── {Model}/
│       ├── {Model}Resource.php
│       ├── Actions/      # Action classes (CreateProductionBatchAction etc.)
│       ├── Pages/        # ListPage, CreatePage, EditPage
│       │                  #   ListPage.getHeaderActions() for page-level actions
│       └── Tables/       # {Model}Table.configure(Table $t)
│                          #   recordActions() for row-level, toolbarActions for bulk
├── Http/
│   ├── Controllers/Api/V1/  # *ApiController — PDA/mobile API
│   └── Resources/Api/V1/    # Eloquent API resources
└── Console/Commands/        # Artisan commands (AppBootstrapCommand etc.)
```

## Database (`database/`)

```
database/
├── migrations/           # Sequential, in-place edited (don't add new for old tables)
├── seeders/
│   ├── DatabaseSeeder.php          # calls LedOsDataSeeder
│   └── LedOsDataSeeder.php         # 1844+ lines: roles → warehouses → users → business
└── factories/
```

## API (`routes/api.php`)

- All PDA/mobile routes under `auth:sanctum` middleware
- Prefix: `/api/v1/...`
- Controller naming: `{Resource}ApiController`
- Resource key: `{resource}` (e.g. `Route::apiResource('checkin-batches', ...)`)

## Mobile (`mobile/`)

```
mobile/
├── App.tsx                  # Root — switch-based nav, AuthProvider, ScreenName union
├── package.json
└── src/
    ├── components/
    │   ├── ScannerModal.tsx     # REUSABLE — uses CameraView (Expo 57)
    │   ├── StatusBadge.tsx
    │   └── (others)
    ├── context/
    │   └── AuthContext.tsx      # useAuth() hook, selectedWarehouseId
    ├── screens/
    │   ├── LoginScreen.tsx
    │   ├── HomeScreen.tsx       # 4 action cards (Nhập, Xuất, Trả, Tra cứu)
    │   ├── CheckoutBatchesScreen.tsx + CheckoutDetailScreen.tsx
    │   ├── ReturnBatchesScreen.tsx   + ReturnDetailScreen.tsx
    │   ├── CheckinBatchesScreen.tsx  + CheckinDetailScreen.tsx   ← Phase 3
    │   └── AssetLookupScreen.tsx
    ├── services/
    │   └── apiClient.ts         # axios, baseURL ${apiBaseUrl}/api/v1
    └── types/
        └── index.ts             # All TS interfaces (Asset, CheckoutBatch, CheckinBatch, etc.)
```

## Tests (`tests/`)

```
tests/
├── Feature/               # Pest feature tests, RefreshDatabase wraps in transaction
│   ├── Api/                 # Mobile API tests
│   ├── CheckinBatchProductionTest.php
│   ├── ContractAndPaymentTest.php
│   ├── AppBootstrapCommandTest.php  ← new
│   └── ... (other)
├── Unit/
└── Pest.php                # Default TestCase = Tests\TestCase + RefreshDatabase
```

**Important**: SQLite `:memory:` cannot run `VACUUM` inside a transaction. Any test calling `migrate:fresh` (e.g. `app:bootstrap` command) will fail. Test components in isolation, not full CLI commands.
