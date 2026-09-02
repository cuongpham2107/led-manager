# Decisions & History

> Read for context on WHY things are the way they are. Append on each new phase.

## Git state (most recent first)

```
e892aa3  refactor(checkin): open Edit as slide-over modal instead of page
57062bb  fix(checkin): stop letting user select existing asset in CheckinBatchForm
cfcd3b1  refactor(checkin): revert Edit back to dedicated route, drop modal
189c2c3  refactor(checkin): clean up Edit modal layout (Repeater table + 4/8 split)
b6dd1bc  feat(checkin): add 'Danh sách thiết bị thực tế nhập kho' Repeater in form
a3879ae  feat(checkin): table shows quantity + items + scan progress
fd6d1e8  refactor(checkin): move action from table header to ListPage header
3be281c  feat(console): one-shot 'app:bootstrap' command
9681837  feat(mobile): check-in batches screens + Home tile
c86ac12  feat(api): check-in batches PDA endpoints
c2bd25b  feat(contracts): simplify ContractStatus — 5 cases
6e5ab9e  feat(warehouse): CreateProductionBatchAction
8ecbd40  feat(warehouse): add batch_type + production metadata
8745c8b  feat: complete all phases (0-5) for LED manager system (Antigravity)
```

## Phase 1 — Production Batch Intake (`8ecbd40` + `6e5ab9e`)

**Goal**: replace multi-step production intake with 1 action that creates a batch + assets in one go.

**Schema** (added 5 columns to `checkin_batches`):
- `batch_type` (enum: production/purchase/transfer, default 'production')
- `product_line_id` (FK, nullable)
- `device_type_id` (FK, nullable)
- `quantity` (unsigned int, nullable)
- `production_note` (text, nullable)

**New files**:
- `app/Enums/CheckinBatchType.php` (with HasLabel/HasColor/HasIcon)
- `app/Services/ProductionBatchService.php` — single DB transaction, validates 1≤qty≤500, throws on serial collision
- `app/Filament/Resources/CheckinBatches/Actions/CreateProductionBatchAction.php` — header action modal with live prefix suggestion
- `tests/Feature/CheckinBatchProductionTest.php` — 5 tests

**Constraint**: `condition = 'ok'` not 'new' (CHECK constraint).

## Phase 2 — Contract Simplification (`c2bd25b`)

**Goal**: reduce ContractStatus complexity. 9 → 5 cases.

**Removed cases**: `Sent`, `SentForApproval`, `Approved`, `DepositReceived`

**Kept**: `Draft`, `Signed`, `Active`, `Completed`, `Cancelled`

**Files changed**:
- `app/Enums/ContractStatus.php` — reduced cases
- `database/migrations/2026_01_01_000021_create_contracts_table.php` — removed 'sent' from enum
- DELETED: `SendForApprovalAction.php`, `ApproveContractAction.php`
- `EditContract.php` — removed both action imports
- `CreateDepositPaymentAction.php` + `CreatePartialPaymentAction.php` — set `Active` (was `DepositReceived`) on deposit received

## Phase 3 — Mobile/PDA Scan for Check-in (`c86ac12` + `9681837`)

**Goal**: let warehouse staff scan serials into an inbound batch from the mobile app, mirroring the existing checkout/return flows.

**Backend** (4 new endpoints):
- `GET /api/v1/checkin-batches` — list + filter
- `GET /api/v1/checkin-batches/{id}` — detail
- `POST /api/v1/checkin-batches/{id}/scan` — mark serial received (updates asset to Ready, creates AssetStatusLog, moves batch Pending→InProgress)
- `POST /api/v1/checkin-batches/{id}/complete` — mark batch Completed with `completed_at`

**New files**:
- `app/Http/Controllers/Api/V1/CheckinBatchApiController.php`
- `app/Http/Resources/Api/V1/CheckinBatchResource.php`
- `app/Http/Resources/Api/V1/CheckinBatchItemResource.php`
- `tests/Feature/Api/CheckinBatchApiTest.php` — 9 tests

**Mobile**:
- `mobile/src/screens/CheckinBatchesScreen.tsx` — list with progress bar
- `mobile/src/screens/CheckinDetailScreen.tsx` — scan + complete
- `mobile/src/types/index.ts` — added `CheckinBatch`, `CheckinBatchItem`, `CheckinBatchTypeValue`
- `mobile/App.tsx` — added 2 screen states
- `mobile/src/screens/HomeScreen.tsx` — added "Nhập Kho Sản Xuất" tile

## Refactor — Action placement (`fd6d1e8`)

**User feedback**: "Tạo đợt nhập kho từ sản xuất cho action đó vào trong header của ListPage ý chứ không phải trong table"

**Change**: moved `CreateProductionBatchAction` from `CheckinBatchesTable::headerActions()` to `ListCheckinBatches::getHeaderActions()`. The action is page-level (creates new batches), not table-level (no records in current list).

## Refactor — Edit as modal (`e892aa3` + `907b888`)

**User feedback (round 1)**: "checkin-batches editform chuyển thành modal cho tôi"
**User feedback (round 2)**: "phẩi ẩn route trong resource đi thì mới được xoá ->slideOver đi modal() thôi"

**Change**: `CheckinBatchesTable` `EditAction` opens a centered modal (not a slide-over drawer). The dedicated `EditCheckinBatch` page class and its `/checkin-batches/{record}/edit` route are deleted; the form lives only in the modal. `->schema(fn ($s) => CheckinBatchResource::form($s))` reuses the resource's form schema.

## `app:bootstrap` command (`3be281c`)

**User feedback**: "khi seed lại tôi bị mất user và role hãy sử dụng lệnh php artisan filament:user admin admin@admin.com password và lệnh php artisan shield:setup và tạo các quyền tương ứng rồi chạy lại seed quyền, tôi muốn 1 command làm tất cả việc đó"

**Design**:
```bash
php artisan app:bootstrap
# Options: --email, --name, --password, --skip-migrate, --skip-seed, --skip-permissions
```

**Steps**:
1. `migrate:fresh --force`
2. `shield:setup --force --starred`
3. Generate permissions via `Utils::createPermission` (NOT `shield:generate`, see traps)
4. `db:seed` (LedOsDataSeeder syncs permissions to roles)
5. Ensure `super_admin` role + admin user with requested credentials

**Result**: 278 permissions, 5 roles (super_admin: 278, sales_executive: 86, warehouse_manager: 155, technician: 42, accountant: 48), 11 demo users + the admin user.

**Testing note**: only the components are tested (5 Pest cases in `AppBootstrapCommandTest.php`); the full command is smoke-tested manually because `migrate:fresh` can't run in Pest's transaction wrapper.

## Pending / Uncommitted

- `app/Filament/Resources/Quotations/Actions/ConvertToOrderAction.php` — unrelated availability block edit (modified, not staged) — ask user before committing
- `docs/plans/simplify_warehouse_plan.md` — planning doc, untracked
