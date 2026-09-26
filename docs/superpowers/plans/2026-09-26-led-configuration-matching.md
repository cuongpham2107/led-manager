# LED Configuration Matching Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tấm LED có "Cấu hình LED" (card nhận, kiểu quét, đầu phát); khi xuất kho hệ thống gợi ý đúng cấu hình + số tấm theo rộng × cao và chặn xuất lệch bộ.

**Architecture:** Bảng danh mục `led_configurations` dưới `product_lines`; tấm, đợt nhập, dòng BOM đơn hàng tham chiếu tới nó. `PanelSuggestionService` tính số tấm và gợi ý cấu hình/serial. `LedSetGuard` là điểm kiểm tra duy nhất cho quy tắc "cùng dòng ⇒ cùng cấu hình" trong một phiếu xuất, được gọi từ mọi nơi thêm thiết bị vào phiếu.

**Tech Stack:** Laravel 12, PHP 8.4, Filament v5, Pest 3, SQLite, Expo (mobile web build).

**Spec:** `docs/superpowers/specs/2026-09-26-led-configuration-matching-design.md`

## Global Constraints
- Sửa migration `create_*` tại chỗ, không thêm migration alter bảng cũ; bảng mới được tạo migration mới. Reset: `php artisan app:bootstrap`.
- Enum: PHP backed, TitleCase, implement `HasLabel` (+ `HasColor` khi hiển thị badge).
- Chuỗi hiển thị tiếng Việt.
- Giá/bảng giá/báo giá không đổi.
- Chạy `vendor/bin/pint --dirty --format agent` trước mỗi commit; test bằng `php artisan test --compact <file>`.
- Test suite hiện có 5 fail + 1 error có sẵn (AssetExcelImportExportTest, AssetTableAndWorkingHistoryTest, InventoryStockWarehouseScopingTest×2, OrderLifecycleAndReturnTest×2) — không được tăng thêm.

---

### Task 1: Dữ liệu — enum, migration, model, factory

**Files:**
- Create: `app/Enums/LedScanMode.php`, `app/Enums/ProductLineType.php`
- Create: `database/migrations/2026_01_01_000004_create_led_configurations_table.php` (chạy sau product_lines 000001, trước assets 000005)
- Modify: `database/migrations/2026_01_01_000001_create_product_lines_table.php` (cột `type`)
- Modify: `database/migrations/2026_01_01_000005_create_assets_table.php` (`led_configuration_id`)
- Modify: migration `create_checkin_batches_table` (`led_configuration_id`)
- Modify: `database/migrations/2026_01_01_000011_create_order_items_table.php` (`led_configuration_id`)
- Create: `app/Models/LedConfiguration.php`, `database/factories/LedConfigurationFactory.php`, `database/factories/ProductLineFactory.php` (nếu chưa có)
- Modify: `app/Models/{ProductLine,Asset,CheckinBatch,OrderItem}.php`
- Test: `tests/Feature/LedConfigurationModelTest.php`

**Interfaces — Produces:**
- `LedScanMode` cases: `Static='static'`, `Quarter='1/4'`, `Eighth='1/8'`, `Sixteenth='1/16'`, `ThirtySecond='1/32'`.
- `ProductLineType`: `Panel='panel'`, `Controller='controller'`.
- `LedConfiguration` fillable: `product_line_id, name, receiving_card, scan_mode, controller_model, note, is_active`; relations `productLine()`, `assets()`; accessor `label` = `"{name} · {receiving_card} · {scan_mode label} · {controller_model}"`.
- `Asset::ledConfiguration()`, `ProductLine::ledConfigurations()`, `CheckinBatch::ledConfiguration()`, `OrderItem::ledConfiguration()`.

- [ ] Step 1: Viết test `LedConfigurationModelTest`: tạo ProductLine + 1 LedConfiguration; asset gắn cấu hình → `$asset->ledConfiguration->is($config)`; unique (dòng, card, scan, đầu phát) ném `QueryException`; `ProductLine` mặc định `type === ProductLineType::Panel`.
- [ ] Step 2: Chạy test → FAIL (class not found).
- [ ] Step 3: Tạo enum, migration, model, quan hệ, fillable/casts, factory.
- [ ] Step 4: `php artisan migrate:fresh` (dev) + chạy test → PASS.
- [ ] Step 5: Commit `feat: add LED configuration data model`.

### Task 2: PanelSuggestionService

**Files:**
- Create: `app/Services/PanelSuggestionService.php`
- Test: `tests/Feature/PanelSuggestionServiceTest.php`

**Interfaces:**
- Consumes: Task 1 models.
- Produces:
  - `gridFor(ProductLine $line, float $widthM, float $heightM): array{cols:int, rows:int, required:int, area_m2:float}`
  - `suggest(ProductLine $line, int $warehouseId, int $required, array $filters = []): Collection<int, array{configuration: LedConfiguration, available:int, is_enough:bool, lots: array<int|string,int>}>` — `$filters` keys: `receiving_card`, `scan_mode`, `controller_model` (null/'' bỏ qua).
  - `pickAssets(LedConfiguration $config, int $warehouseId, int $qty, array $excludeIds = []): array{assets: Collection<int, Asset>, mixed_lots: bool}`
  - `static availableQuery(int $warehouseId): Builder` — Asset Ready trong kho, không bị giữ bởi phiếu xuất đang mở (trích nguyên điều kiện `whereDoesntHave('checkoutBatchItems', …)` từ `CreateCheckoutBatchAction`).
- Lô của tấm: `checkin_batch_id` của `checkin_batch_items` mới nhất (subquery `max(checkin_batch_id)`), null → key `'none'`.

- [ ] Step 1: Test: grid 1.5×0.5 m với module 500×500 ⇒ 3×1=3; 1.6 m làm tròn lên 4 cột; module null ⇒ 500×500.
- [ ] Step 2: Test suggest: 2 cấu hình (A: 5 tấm Ready, B: 2 tấm) required 3 ⇒ A đứng đầu `is_enough=true`, B `false`; filter `scan_mode='1/32'` chỉ còn cấu hình khớp; tấm đã nằm trong phiếu xuất chưa dispatch không được đếm.
- [ ] Step 3: Test pickAssets: lô L1 có 2 tấm, L2 có 4 tấm, cần 3 ⇒ lấy 3 từ L2, `mixed_lots=false`; cần 5 ⇒ `mixed_lots=true`, đủ 5.
- [ ] Step 4: Chạy → FAIL. Step 5: Implement. Step 6: PASS. Step 7: Commit `feat: add panel suggestion service`.

### Task 3: LedSetGuard + chặn lệch bộ ở mọi điểm thêm thiết bị vào phiếu xuất

**Files:**
- Create: `app/Services/LedSetGuard.php`
- Modify: `app/Http/Controllers/CheckoutAssetSearchController.php` (`index` nhận `led_configuration_id`; `dispatchItem` gọi guard → 422)
- Modify: `app/Http/Controllers/Api/V1/CheckoutBatchApiController.php` (`scan` gọi guard → 422)
- Modify: `app/Filament/Resources/CheckoutBatches/Pages/ListCheckoutBatches.php`, `.../Tables/CheckoutBatchesTable.php` (trong `before()`: notify + halt), `.../Pages/CreateCheckoutBatch.php` (`handleRecordCreation`: throw `ValidationException`)
- Test: `tests/Feature/LedSetGuardTest.php`

**Interfaces — Produces:**
- `LedSetGuard::conflict(iterable<Asset> $assets): ?string` — null nếu hợp lệ; ngược lại thông báo: `"Thiết bị {serial} thuộc cấu hình \"{label B}\", khác cấu hình \"{label A}\" của dòng {tên dòng} trong phiếu. Các tấm cùng dòng phải cùng cấu hình để lắp đúng bộ."`. Bỏ qua asset không có cấu hình.
- `LedSetGuard::conflictForBatch(CheckoutBatch $batch, Asset $candidate): ?string` — gộp asset hiện có trong phiếu + candidate rồi gọi `conflict`.

- [ ] Step 1: Test `conflict`: cùng dòng khác cấu hình ⇒ chuỗi chứa serial; khác dòng khác cấu hình ⇒ null; asset không cấu hình ⇒ null.
- [ ] Step 2: Test API: phiếu đã có tấm cấu hình A, quét tấm cấu hình B cùng dòng ⇒ 422 + message; quét tấm A ⇒ 200. Test `filament.checkout-dispatch-item` (route name xem `routes/web.php`) tương tự.
- [ ] Step 3: FAIL → implement → PASS.
- [ ] Step 4: Commit `feat: block mixed LED configurations in a checkout batch`.

### Task 4: Tạo Đợt Xuất Kho theo cấu hình (sửa lỗi lấy bù)

**Files:**
- Modify: `app/Filament/Resources/Orders/Actions/CreateCheckoutBatchAction.php`
- Test: `tests/Feature/CreateCheckoutBatchConfigurationTest.php`

Hành vi:
- Form thêm `Repeater::make('lines')` (không thêm/xoá được) mỗi phần tử ứng với một order item loại tấm: `order_item_id` (Hidden), tiêu đề dòng SP + SL, bộ lọc `receiving_card` / `scan_mode` / `controller_model` (Select, options lấy từ các cấu hình của dòng), `width_m`, `height_m` (tuỳ chọn; nhập thì cập nhật `quantity` theo `gridFor`), `quantity`, `led_configuration_id` (Select, options = `suggest()` hiển thị "label — còn X tấm ✓/thiếu", mặc định = `order_item->led_configuration_id` hoặc option đầu tiên đủ).
- Action: với mỗi line → `pickAssets(config, warehouse, qty)`; ghi `order_items.led_configuration_id`; dòng loại controller hoặc không có cấu hình nào → gán theo `product_line_id` như cũ.
- **Xoá khối "Bổ sung các thiết bị còn thiếu"** (lấy bù khác dòng).
- Thông báo cảnh báo khi `mixed_lots` hoặc thiếu tấm.

- [ ] Step 1: Test (Livewire `callAction` trên `EditOrder`): đơn P2.6 SL 3, kho có 2 tấm cấu hình A + 5 tấm cấu hình B + 4 tấm dòng khác ⇒ chọn B: 3 tấm đều cấu hình B; chọn A: chỉ gán 2 tấm (không lấy bù dòng khác/cấu hình khác), order item lưu `led_configuration_id`.
- [ ] Step 2: FAIL → implement → PASS. Chạy lại `tests/Feature/OrderLifecycleAndReturnTest.php`, `AgencyWorkflowTest.php` (không tăng fail).
- [ ] Step 3: Commit `feat: configuration-aware checkout creation from orders`.

### Task 5: Filament UI — Cấu hình LED, Dòng SP, Thiết bị, Nhập kho, Đơn hàng

**Files:**
- Create: `app/Filament/Resources/LedConfigurations/{LedConfigurationResource.php, Pages/ListLedConfigurations.php, Schemas/LedConfigurationForm.php, Tables/LedConfigurationsTable.php}` (theo mẫu `ProductLines/`, nhóm "Hệ thống", sort 2, modal create/edit)
- Modify: `ProductLines/Schemas/ProductLineForm.php`, `ProductLines/Tables/ProductLinesTable.php` (Loại)
- Modify: `Assets/Schemas/AssetForm.php`, `Assets/Tables/AssetsTable.php` (cột + filter cấu hình), `Assets/Pages/ListAssets.php` + `app/Exports/AssetTemplateExport.php` (cột Excel "Cấu hình LED", khớp theo `name`)
- Modify: `CheckinBatches/Schemas/CheckinBatchForm.php` (Select `led_configuration_id`), `CheckinBatches/Pages/{CreateCheckinBatch,ListCheckinBatches}.php`, `CheckinBatches/Tables/CheckinBatchesTable.php` — gọi `LedSetGuard::applyBatchConfiguration(?int $configId, array $assetIds): ?string` (set cấu hình cho asset chưa có; asset đã có cấu hình khác ⇒ trả thông báo lỗi để halt)
- Modify: `Orders/Schemas/OrderForm.php` (Select cấu hình trong repeater BOM, lọc theo `product_line_id` của dòng)
- Modify: `database/seeders/LedOsDataSeeder.php` (quyền `%:LedConfiguration%` cho thủ kho, `View%:LedConfiguration%` cho các vai trò có `View%:ProductLine%`)
- Create: `database/seeders/LedConfigurationSeeder.php` (2 cấu hình / dòng tấm; gán cấu hình A cho mọi tấm, cấu hình B cho ~20% tấm Ready chưa nằm trong phiếu xuất nào; dòng "Đầu phát Novastar VX600" type controller + 2 thiết bị/kho tổng), gọi trong `DatabaseSeeder` sau `LedOsDataSeeder`
- Test: `tests/Feature/LedConfigurationResourceTest.php`

- [ ] Step 1: Test: trang list Cấu hình LED 200 cho super_admin; tạo cấu hình qua `CreateAction` modal; `applyBatchConfiguration` gán cho asset null và trả lỗi với asset khác cấu hình; tạo đợt nhập với cấu hình ⇒ asset được gán.
- [ ] Step 2: FAIL → implement → PASS.
- [ ] Step 3: `php artisan app:bootstrap` chạy không lỗi.
- [ ] Step 4: Commit `feat: LED configuration admin UI, asset/checkin/order fields, seed data`.

### Task 6: API resource + Mobile

**Files:**
- Modify: `app/Http/Resources/Api/AssetResource.php` (`led_configuration: {id, label, receiving_card, scan_mode, controller_model} | null`)
- Modify: `mobile/src/types/index.ts`, `mobile/src/screens/CheckoutDetailScreen.tsx` (hiện cấu hình dưới tên thiết bị), `mobile/src/screens/AssetLookupScreen.tsx` (dòng "Cấu hình")
- Test: thêm assertion vào `tests/Feature/Api/…` hiện có cho `led_configuration` trong response.

- [ ] Step 1: Test API show checkout batch trả `led_configuration.label` cho item.
- [ ] Step 2: FAIL → implement → PASS.
- [ ] Step 3: `cd mobile && npm run build:web` thành công (lỗi quét lệch đã hiển thị sẵn qua `message` của API).
- [ ] Step 4: Commit `feat: show LED configuration on mobile`.

### Task 7: Kiểm tra cuối
- [ ] Chạy full suite `php -d memory_limit=1G vendor/bin/pest --compact` — chỉ còn các fail có sẵn.
- [ ] Mở web kiểm tra: tạo cấu hình, tạo đơn, Tạo Đợt Xuất Kho thấy bảng gợi ý; quét lệch trên /mobile bị chặn.
- [ ] Ghi rule vào `.ai/rules` (qua sửa file, không để record-rule ghi đè index).
