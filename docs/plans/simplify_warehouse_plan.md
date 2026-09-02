# 📦 Kế Hoạch: Đơn Giản Hoá LED OS — Sửa Gap "Flow Nhập Kho"

> **Phương pháp**: Đối chiếu `docs/*.docx` + review code hiện tại (42 tests pass, branch clean, commit `8745c8b feat: complete all phases (0-5)`).
> **Ngày**: 2026-08-29
> **Trạng thái**: ⏳ Chưa bắt đầu — chờ user phê duyệt để implement Phase 1.
> **Quy ước**: Sửa migration in-place + `migrate:fresh --seed` (theo `docs/plans/implementation_plan.md`).

---

## 🎯 Tóm tắt vấn đề (theo phản ánh của sếp)

> *"Phức tạp rồi — ở đây là công ty họ sản xuất ra cái thiết bị này để kinh doanh cho thuê thôi. Khi sản xuất xong thì họ tạo mã rồi tạo đợt nhập để nhập kho thôi. Còn xuất thì khi có khách thuê thì sẽ tạo đơn và đợt xuất kho."*

**Vấn đề chính** (xác minh từ code):
- `checkin_batches` table chỉ có 3 columns (`code`, `warehouse_id`, `created_by`) — rất tối giản
- `CheckinBatchResource` **không có action nào** tự động tạo Asset hàng loạt
- Hiện tại user phải: tạo Asset thủ công → tạo CheckinBatch riêng → add từng CheckinBatchItem
- Sếp muốn: **1 thao tác** = nhập số lượng → tự sinh mã → nhập kho → xong.

**Vấn đề phụ**:
- Mobile app (React Native) có `CheckoutBatchesScreen` + `ReturnBatchesScreen` nhưng **KHÔNG có `CheckinBatchesScreen`** — thiếu PDA scan cho nhập kho.

---

## 📊 Tổng quan 3 Phases

| Phase | Mục tiêu | Priority | Thời gian ước tính | Phụ thuộc |
|---|---|---|---|---|
| **1** | Flow "Sản xuất → Tạo mã → Nhập kho" | 🔴 **Cao nhất** | 2 commits | — |
| **2** | Ẩn module thừa + review UX | 🟡 Trung bình | 1 commit | Phase 1 |
| **3** | PDA scan cho nhập kho (mobile + API) | 🟢 Thấp | 2 commits | Phase 1 |

---

## ✅ Phase 1 — Flow "Sản xuất → Tạo mã → Nhập kho"

### 1.1 Tổng quan

Thêm **1 action duy nhất** trên trang `ListCheckinBatches`:

> **"Tạo đợt nhập kho từ sản xuất"**

Form fields:
- `ProductLine` (dòng sản phẩm LED) — required, searchable
- `DeviceType` (loại thiết bị cabinet / processor / accessory) — required
- `Quantity` (số lượng sản xuất) — required, min 1, max 500
- `Warehouse` (kho nhập vào) — required, chỉ hiển thị warehouse active
- `Size` (kích thước cabinet, VD: "500x500mm") — optional
- `Serial prefix` — auto-suggest theo ProductLine code + YYMMDD, cho phép override

Submit → **1 DB transaction**:
1. Sinh N `serial_no` theo pattern `{prefix}-{STT 3 số}` (VD: `P3.91-260829-001` đến `P3.91-260829-010`)
2. Tạo N records `Asset` với:
   - `serial_no` = mã đã sinh
   - `qr_code` = cùng `serial_no`
   - `device_type_id` = đã chọn
   - `product_line_id` = đã chọn
   - `current_warehouse_id` = đã chọn
   - `current_status` = `AssetStatus::Ready`
   - `size` = đã nhập
3. Tạo 1 record `CheckinBatch` với `status = 'completed'`, `completed_at = now()`
4. Tạo N records `CheckinBatchItem` gắn `asset_id` + `batch_id`, `is_received = true`, `received_at = now()`
5. Tạo N records `AssetStatusLog` ghi nhận `Ready` (from: null hoặc `Manufactured`)

### 1.2 Files thay đổi

#### [MODIFY] `database/migrations/2026_01_01_000012_create_checkin_batches_table.php`
Thêm columns:
```php
$table->string('batch_type')->default('production'); // production | purchase | transfer
$table->foreignId('product_line_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('device_type_id')->nullable()->constrained()->nullOnDelete();
$table->unsignedInteger('quantity')->nullable();
$table->text('production_note')->nullable();
$table->dateTime('completed_at')->nullable();
```

#### [MODIFY] `app/Models/CheckinBatch.php`
Thêm vào `$fillable`: `batch_type`, `product_line_id`, `device_type_id`, `quantity`, `production_note`, `completed_at`
Thêm casts: `completed_at => datetime`
Thêm relations: `productLine()`, `deviceType()`

#### [NEW] `app/Enums/CheckinBatchType.php`
```php
enum CheckinBatchType: string
{
    case Production = 'production';  // Nhập từ sản xuất
    case Purchase = 'purchase';       // Mua ngoài
    case Transfer = 'transfer';       // Chuyển kho nội bộ

    public function getLabel(): ?string { /* ... */ }
    public function getColor(): string|array|null { /* ... */ }
    public function getIcon(): ?string { /* ... */ }
}
```

#### [NEW] `app/Services/ProductionBatchService.php`
Service xử lý logic chính:
```php
class ProductionBatchService
{
    public function createFromProduction(
        ProductLine $productLine,
        DeviceType $deviceType,
        int $quantity,
        Warehouse $warehouse,
        ?string $size,
        string $serialPrefix,
        ?string $note,
        ?int $createdBy = null,
    ): CheckinBatch {
        return DB::transaction(function () use (...) {
            // 1. Sinh batch code (CodeGeneratorService::generate('IN', 'checkin_batches'))
            // 2. Tạo CheckinBatch
            // 3. Loop N lần: tạo Asset + CheckinBatchItem + AssetStatusLog
            // 4. Update CheckinBatch: status = 'completed', completed_at = now()
        });
    }
}
```

#### [NEW] `app/Filament/Resources/CheckinBatches/Actions/CreateProductionBatchAction.php`
Action class — modal với form, submit gọi `ProductionBatchService`. Hiển thị notification kết quả.

#### [MODIFY] `app/Filament/Resources/CheckinBatches/Tables/CheckinBatchesTable.php`
Đăng ký `CreateProductionBatchAction` ở vị trí header (phía trên table).

#### [MODIFY] `app/Filament/Resources/CheckinBatches/Schemas/CheckinBatchForm.php`
Thêm fields: `batch_type` (Select với CheckinBatchType), `product_line_id`, `device_type_id`, `quantity`, `production_note`. Ẩn/hiện theo `batch_type` (chỉ hiện khi `production`).

### 1.3 Pattern sinh serial — đề xuất chọn

**Chọn Option A: Auto-suggest theo `{ProductLine.code}-{YYMMDD}-{STT 3 số}`**

Lý do:
- Tự động, ít sai sót
- Pattern khớp với `CodeGeneratorService` đã có (chỉ khác format: YYMMDD thay vì YYMM)
- User có thể override prefix nếu cần

Default: `{ProductLine.code}-{now()->format('ymd')}-{001..N}`

### 1.4 Tests cần thêm

**File**: `tests/Feature/CheckinBatchProductionTest.php` (mới)

Test cases:
1. **Tạo đợt sản xuất 10 thiết bị** — assert:
   - 1 `CheckinBatch` với `batch_type = 'production'`, `quantity = 10`, `status = 'completed'`
   - 10 `Asset` với `serial_no` tuần tự, `current_status = Ready`, đúng `warehouse_id`/`device_type_id`/`product_line_id`
   - 10 `CheckinBatchItem` với `is_received = true`, gắn đúng asset
   - 10 `AssetStatusLog` ghi nhận transition
2. **Validation** — `quantity = 0` → bị reject; `quantity = 501` → bị reject
3. **Override prefix** — user nhập prefix khác → serial bắt đầu bằng prefix đó
4. **Idempotency / Serial collision** — nếu trùng serial → throw exception (transaction rollback)

### 1.5 Commit strategy

- **Commit 1**: Migration + Model + Enum
- **Commit 2**: `ProductionBatchService` + Action + Registration + Test

Sau mỗi commit: `vendor/bin/pint --dirty --format agent` + `php artisan test --compact`.

---

## 🟡 Phase 2 — Ẩn module thừa + Review UX

### 2.1 Ẩn navigation (nhanh, ~5 phút)

**Đã xác minh**: `EventAssignment`, `EventMilestone`, `InventoryReservation` **không có Filament Resource** (grep trả về MISSING). Chúng đã ẩn tự nhiên.

**Hành động**: Không cần làm gì. Chỉ cần grep xác nhận không có resource ở chỗ khác (đã làm).

**Optional**: Thêm comment `// Hidden from nav intentionally` ở đầu model nếu chưa có, để rõ ý đồ.

### 2.2 Đơn giản hoá Contract (cân nhắc, không bắt buộc)

`ContractStatus` hiện có **9 trạng thái** (`Draft, Sent, SentForApproval, Approved, Signed, DepositReceived, Active, Completed, Cancelled`) — quá nhiều cho nghiệp vụ "cho thuê LED".

Doc sếp gốc nói "convert to contract" đơn giản. Có thể đơn giản về **4 trạng thái cốt lõi**:
- `Draft` (đang soạn)
- `Signed` (khách đã ký)
- `Active` (đang hiệu lực — có thể merge với Signed)
- `Completed` (hoàn tất)

**Khuyến nghị**: Hỏi sếp trước khi đơn giản hoá. Phase 2.2 chỉ làm nếu sếp đồng ý. Nếu sếp OK với approval flow hiện tại thì giữ nguyên.

### 2.3 Review UX OrderForm (cân nhắc)

`OrderForm` đang có nhiều sections (Order info, Event details, Customer, Items, Milestones, Assignments). Có thể gộp/ẩn một số section không cần thiết lúc tạo đơn.

**Khuyến nghị**: Hỏi sếp cụ thể section nào thừa trước khi refactor.

---

## 🟢 Phase 3 — PDA Scan Cho Nhập Kho (Mobile + API)

### 3.1 Backend API

#### [NEW] `app/Http/Controllers/Api/V1/CheckinBatchApiController.php`
Endpoints:
- `GET /api/v1/checkin-batches` — list batches (filterable by status, warehouse)
- `GET /api/v1/checkin-batches/{id}` — chi tiết batch
- `POST /api/v1/checkin-batches/{id}/scan` — quét serial nhập kho
- `POST /api/v1/checkin-batches/{id}/complete` — hoàn tất batch

Logic `scan()`:
- Nhận `code` (serial / QR)
- Tìm Asset theo `serial_no` hoặc `qr_code`
- Validate Asset chưa ở `Ready` (tránh quét trùng) hoặc cho phép idempotent
- Tạo/update `CheckinBatchItem` với `is_received = true`, `received_by`, `received_at`
- Update Asset `current_status = Ready`, `current_warehouse_id = batch.warehouse_id`
- Tạo `AssetStatusLog`

### 3.2 Mobile screens

#### [NEW] `mobile/src/screens/CheckinBatchesScreen.tsx`
- List các đợt nhập kho (filter: status pending/completed)
- Hiển thị progress (X/Y items received)
- Nút "Quét thiết bị" mở `ScannerModal`

#### [NEW] `mobile/src/screens/CheckinDetailScreen.tsx`
- Chi tiết batch
- Danh sách items
- Realtime update khi scan

#### [MODIFY] `mobile/App.tsx` hoặc navigation config
- Thêm route `CheckinBatches`, `CheckinDetail`
- Thêm menu item "Nhập kho" trong `HomeScreen`

### 3.3 Tests cần thêm

**Backend**: `tests/Feature/Api/CheckinBatchApiTest.php`
- Tạo batch → scan 1 serial → assert item is_received, asset status Ready
- Scan serial không tồn tại → 404
- Scan serial đã ở Ready (ở warehouse khác) → business rule?

**Mobile**: Có thể bỏ qua test cho mobile, tập trung test backend.

### 3.4 Commit strategy

- **Commit 1**: Backend API + Tests
- **Commit 2**: Mobile screens + Navigation

---

## ⚠️ Rủi ro & Cân nhắc

### Rủi ro 1: Xung đột với Antigravity
- Branch hiện tại: clean, đã có commit `8745c8b feat: complete all phases (0-5)` của Antigravity
- File chưa commit duy nhất: `ConvertToOrderAction.php` (sửa hôm nay)
- **Hành động**: Trước khi bắt đầu, `git diff main` để xác nhận không có pending changes khác

### Rủi ro 2: Mobile app cần `npm run build` sau khi sửa
- Theo AGENTS.md: *"If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them."*
- Sau Phase 3, cần nhắc user chạy `npm run build` để mobile build nhận code mới

### Rủi ro 3: `migrate:fresh --seed` sẽ xoá data
- Đây là convention hiện tại của project (theo `docs/plans/implementation_plan.md`)
- Backup: nếu user có data quan trọng, cần export trước
- **Hành động**: Confirm với user trước khi refresh

### Rủi ro 4: Số lượng test tăng
- 42 tests hiện tại → sau Phase 1: ~46 tests, sau Phase 3: ~50 tests
- Thời gian chạy test tăng từ 11.8s → ~15s
- Chấp nhận được

---

## 📋 Checklist thực hiện

### Phase 1
- [ ] Verify git status clean
- [ ] Sửa migration `2026_01_01_000012_create_checkin_batches_table.php` (thêm columns)
- [ ] Sửa `app/Models/CheckinBatch.php` (fillable, casts, relations)
- [ ] Tạo `app/Enums/CheckinBatchType.php`
- [ ] Tạo `app/Services/ProductionBatchService.php`
- [ ] Tạo `app/Filament/Resources/CheckinBatches/Actions/CreateProductionBatchAction.php`
- [ ] Sửa `app/Filament/Resources/CheckinBatches/Tables/CheckinBatchesTable.php` (đăng ký action)
- [ ] Sửa `app/Filament/Resources/CheckinBatches/Schemas/CheckinBatchForm.php` (thêm fields)
- [ ] Tạo `tests/Feature/CheckinBatchProductionTest.php` (4 test cases)
- [ ] Chạy `migrate:fresh --seed` để apply migration mới
- [ ] Chạy `vendor/bin/pint --dirty --format agent`
- [ ] Chạy `php artisan test --compact` — expect 46 tests pass
- [ ] Commit 1: migration + model + enum
- [ ] Commit 2: service + action + registration + tests

### Phase 2
- [ ] (Optional) Hỏi sếp về đơn giản hoá Contract
- [ ] (Optional) Hỏi sếp về đơn giản hoá OrderForm sections

### Phase 3
- [ ] Tạo `app/Http/Controllers/Api/V1/CheckinBatchApiController.php`
- [ ] Đăng ký routes trong `routes/api.php`
- [ ] Tạo `tests/Feature/Api/CheckinBatchApiTest.php`
- [ ] Tạo `mobile/src/screens/CheckinBatchesScreen.tsx`
- [ ] Tạo `mobile/src/screens/CheckinDetailScreen.tsx`
- [ ] Sửa `mobile/App.tsx` (navigation)
- [ ] Nhắc user chạy `npm run build` cho mobile
- [ ] Chạy `php artisan test --compact` — expect ~50 tests pass
- [ ] Commit 1: backend
- [ ] Commit 2: mobile

---

## 🎯 Quyết định cần user xác nhận trước khi bắt đầu

> **1. Pattern sinh serial**: Chọn **Option A** (auto `{ProductLine.code}-{YYMMDD}-{STT 3 số}`) — đề xuất mặc định. OK?

> **2. Có làm Phase 2.2 (đơn giản hoá Contract) không?** Doc sếp gốc nói đơn giản, nhưng hiện tại có 9 trạng thái Contract + approval flow 2 cấp. Có cần giảm bớt không?

> **3. Phase 3 có cần làm không?** Mobile app hiện KHÔNG có màn hình Nhập kho. Có cần thêm không, hay tạm thời bỏ qua (vì ưu tiên cao nhất là Phase 1)?

> **4. Branch strategy**: Tạo branch mới `feature/simplify-warehouse-flow` hay làm trực tiếp trên branch hiện tại? (Hiện tại branch clean, chỉ có 1 file chưa commit.)

---

## 📊 Tiến độ

| Phase | Trạng thái | Ghi chú |
|---|---|---|
| **0** — Plan & đối chiếu | ✅ Hoàn thành | Đánh giá của tôi (đã gửi user) |
| **1** — CreateProductionBatchAction | ⏳ Chờ phê duyệt | 2 commits, ~4 files mới, 1 test file |
| **2** — Ẩn module + review UX | ⏳ Chờ phê duyệt | Module thừa đã ẩn tự nhiên. Contract review cần user confirm |
| **3** — PDA scan nhập kho | ⏳ Chờ phê duyệt | 1 API controller + 2 mobile screens + tests |
