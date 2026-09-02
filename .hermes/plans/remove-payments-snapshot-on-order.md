# Plan: Loại bỏ Resource Payments — gộp tiền vào Order (snapshot)

## Mục tiêu
- Bỏ toàn bộ UI + model + bảng `payments`.
- Trên `orders` lưu 3 cột tiền: `deposit_paid`, `total_paid`, `paid_at`.
- Nhánh "Bán hàng & Dự án" chỉ còn 3 Resources (Quotations / Orders / Contracts).
- Tất cả code dùng `Payment`/`payments` được thay bằng truy vấn trực tiếp trên cột `orders`.

## Cách đánh giá đã làm
Đã đọc: `PaymentResource*`, `OrderResource`, `OrdersTable`, `OrderForm`, `ContractsTable`, `ContractForm`, `ContractResource`, `Contract.php`, `Order.php`, `Payment.php`, `OrdersTable` (đã tham chiếu `deposit_paid` accessor trên Order), seeder `LedOsDataSeeder` (5 payment với 2 loại Deposit/Final). Xác nhận các nơi dùng `App\Models\Payment`: 16 file, trong đó các chỗ quan trọng ngoài Resource Payments:
- `app/Filament/Widgets/ExecutiveKpiWidget.php:44` — `Payment::sum('amount')`
- `app/Filament/Widgets/StatsOverviewWidget.php:28` — `Payment::where('type','!=',Refund)->sum('amount')`
- `app/Policies/PaymentPolicy.php` — toàn bộ
- `tests/Feature/ContractAndPaymentTest.php` — toàn bộ
- `database/seeders/LedOsDataSeeder.php:1667-1780` — 5 payment
- `app/Models/Order.php:158-161` — `payments()` relation + accessor `getDepositPaidAttribute/getTotalPaidAttribute`
- `app/Models/Contract.php:93-96` — `payments()` relation + accessor `getTotalPaidAttribute/getRemainingDebtAttribute`
- `app/Filament/Resources/Orders/Tables/OrdersTable.php:80,82,108,131,158` — dùng `deposit_paid`, `total_paid`
- `app/Filament/Resources/Orders/Tables/OrdersTable.php:246-252` — filter `deposit_filter` query `whereHas('payments')`

---

## Thay đổi

### 1. Database
- **Xoá file** `database/migrations/2026_01_01_000022_create_payments_table.php` (sau khi đã có dữ liệu mới trên DB).
- **Sửa migration orders** `2026_01_01_000009_create_orders_table.php` — thêm 3 cột cuối bảng (chỉ chạy trên DB mới):
  ```php
  $table->decimal('deposit_paid', 14, 2)->default(0)->after('value');
  $table->decimal('total_paid', 14, 2)->default(0)->after('deposit_paid');
  $table->timestamp('paid_at')->nullable()->after('total_paid');
  ```
- **Gộp migration update trùng** (nếu đã có migration `add_payment_*` ở local) → gộp luôn vào migration `create_orders_table` rồi `migrate:fresh`.
- Không cần FK vì cùng bảng.

### 2. Models
- **Xoá** `app/Models/Payment.php`.
- **Sửa** `app/Models/Order.php`:
  - Bỏ `use App\Enums\PaymentType`.
  - Bỏ method `payments(): HasMany`.
  - Thêm 3 cột vào `$fillable`: `deposit_paid`, `total_paid`, `paid_at`.
  - Thêm vào `$casts`: `paid_at => 'datetime'`.
  - **Xoá** 2 accessor `getDepositPaidAttribute` / `getTotalPaidAttribute` (đã có cột thật).
- **Sửa** `app/Models/Contract.php`:
  - Bỏ method `payments(): HasMany`.
  - Bỏ accessor `getTotalPaidAttribute` / `getRemainingDebtAttribute`. (Contract không lưu tiền nữa; nếu nơi nào đang dùng → chuyển sang đọc từ Order liên kết.)

### 3. Enums
- **Giữ nguyên** `App\Enums\PaymentMethod` và `App\Enums\PaymentType` nếu còn dùng ở widget / seeder. Sau khi sửa widget & seeder, **xoá** nếu không còn ai dùng.
- Trong plan này: xoá sau khi đã thay hết tham chiếu (kiểm tra bằng grep).

### 4. Filament Resources
- **Xoá toàn bộ** `app/Filament/Resources/Payments/` (7 file):
  - `PaymentResource.php`
  - `Pages/ListPayments.php`, `CreatePayment.php`, `EditPayment.php`
  - `Schemas/PaymentForm.php`
  - `Tables/PaymentsTable.php`
- **Sửa** `app/Providers/Filament/AdminPanelProvider.php`: bỏ dòng navigationGroup `'Bán hàng & Dự án'` sẽ tự co lại (Resource tự đăng ký khi có class), không cần đổi `navigationGroups`. Nếu Resource không tự đăng ký thì OK, group rỗng vẫn không hiện trong sidebar.

### 5. Contracts Resource
- **Sửa** `app/Filament/Resources/Contracts/ContractResource.php`:
  - Bỏ `use App\Filament\Resources\Contracts\RelationManagers\PaymentsRelationManager;` và khỏi `getRelations()`.
- **Xoá** `app/Filament/Resources/Contracts/RelationManagers/PaymentsRelationManager.php`.
- **Xoá** `app/Filament/Resources/Contracts/Actions/CreateDepositPaymentAction.php`.
- **Xoá** `app/Filament/Resources/Contracts/Actions/CreatePartialPaymentAction.php`.
- Cập nhật `ContractsTable` (nếu có cột liên quan payment) — xem nhanh trước khi sửa.

### 6. Orders Resource
- **Sửa** `app/Filament/Resources/Orders/Schemas/OrderForm.php`:
  - Thêm Section mới "Thông tin thanh toán" (collapsible) trong left column (5 cols), đặt sau section "Chi tiết sự kiện & Giá trị đơn hàng":
    ```php
    Section::make('Thông tin thanh toán')
        ->description('Số tiền cọc đã thu, tổng tiền đã thu và ngày thu gần nhất')
        ->collapsible()
        ->schema([
            Grid::make(2)->schema([
                TextInput::make('deposit_paid')
                    ->label('Tiền cọc đã thu')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->default(0)
                    ->columnSpan(1),
                TextInput::make('total_paid')
                    ->label('Tổng tiền đã thu')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->default(0)
                    ->columnSpan(1),
            ]),
            DateTimePicker::make('paid_at')
                ->label('Ngày thu gần nhất')
                ->native(false),
        ]),
    ```
- **Sửa** `app/Filament/Resources/Orders/Tables/OrdersTable.php`:
  - Bỏ use `PaymentType`.
  - Bỏ `contracts.payments` và `payments` khỏi `with()` eager load (giảm query).
  - Trong 3 callback `state/description/color/icon` của cột `deposit_status` — đổi `$record->deposit_paid` và `$record->total_paid` thành đọc cột trực tiếp (đã là accessor → giờ là cột, code vẫn chạy nhưng nhẹ hơn). **Quan trọng:** logic vẫn tham chiếu `$contract = $record->contracts->first()` + `$contract->deposit_amount`. OK — vẫn hợp lệ.
  - **Sửa filter** `deposit_filter`:
    ```php
    ->query(function ($query, array $data) {
        if (($data['value'] ?? null) === 'deposited') {
            $query->where('deposit_paid', '>', 0);
        } elseif (($data['value'] ?? null) === 'not_deposited') {
            $query->where(fn ($q) => $q->whereNull('deposit_paid')->orWhere('deposit_paid', 0));
        }
    })
    ```

### 7. Widgets
- **Sửa** `app/Filament/Widgets/ExecutiveKpiWidget.php`:
  - Bỏ `use App\Models\Payment`.
  - Đổi `$totalReceived = (float) Payment::sum('amount')` →
    `$totalReceived = (float) Order::sum('total_paid')`.
- **Sửa** `app/Filament/Widgets/StatsOverviewWidget.php`:
  - Bỏ `use App\Models\Payment`, `use App\Enums\PaymentType`.
  - Đổi → `Order::sum('total_paid')`.

### 8. Seeder
- **Sửa** `database/seeders/LedOsDataSeeder.php`:
  - Bỏ `use App\Models\Payment`, `use App\Enums\PaymentMethod`, `use App\Enums\PaymentType`.
  - Với 5 payment cũ (mỗi contract 1–2 payment) → set thẳng `deposit_paid` + `total_paid` lên bản ghi Order tương ứng (xem bảng map):

    | Payment code | Order (qua contract.order_id) | deposit_paid mới | total_paid mới |
    |---|---|---|---|
    | PAY-2608-01 | order1 | contract1.deposit_amount | contract1.deposit_amount |
    | PAY-2608-02 | order2 | contract2.deposit_amount | contract2.deposit_amount |
    | PAY-2608-03 | order3 | contract3.deposit_amount | contract3.deposit_amount |
    | PAY-2608-04-1 + PAY-2608-04-2 | order4 | contract4.deposit_amount (= 50% giá trị) | contract4.contract_value (= 100%) |

  - `paid_at` = `payment_date` của payment cuối cùng trên Order đó.

  Code tham khảo (chèn ngay sau khi tạo `contract4`):
    ```php
    $order1->update([
        'deposit_paid' => $contract1->deposit_amount,
        'total_paid' => $contract1->deposit_amount,
        'paid_at' => now()->subDays(1),
    ]);
    // ... tương tự order2, order3 ...
    $order4->update([
        'deposit_paid' => $contract4->deposit_amount,
        'total_paid' => $contract4->contract_value,
        'paid_at' => now()->subDays(4),
    ]);
    ```

### 9. Policies
- **Xoá** `app/Policies/PaymentPolicy.php`.

### 10. Tests
- **Sửa** `tests/Feature/ContractAndPaymentTest.php`:
  - Đổi mục tiêu: xoá phần tạo `Payment` ở runtime, chỉ giữ assert về `contract.total_paid`/`remaining_debt`. **Tuy nhiên** Contract không còn lưu `total_paid`/`remaining_debt` accessor → phải đổi sang đọc `order.total_paid`:
    ```php
    test('orders track deposit and total paid correctly', function () {
        (new LedOsDataSeeder)->run();
        $order = Order::where('order_no', 'ORD-2608-01')->first();
        expect($order)->not->toBeNull()
            ->and($order->deposit_paid)->toBeGreaterThan(0)
            ->and($order->total_paid)->toBeGreaterThanOrEqual($order->deposit_paid);
    });
    ```

### 11. Các nơi còn lại dùng `payment`/`payments`
Sau khi sửa xong, grep toàn project:
```bash
rg -i 'App\\Models\\Payment|App\\Enums\\PaymentType|App\\Enums\\PaymentMethod' app/ database/ tests/ resources/
```
Nếu còn sót → fix từng chỗ.

### 12. Refresh DB
- `php artisan migrate:fresh --seed`
- Chạy test: `php artisan test --filter=ContractAndPaymentTest`
- (Sau cùng) `vendor/bin/pint --dirty --format agent`

---

## File Touch-list

**Xoá (12 file):**
- `database/migrations/2026_01_01_000022_create_payments_table.php`
- `app/Models/Payment.php`
- `app/Policies/PaymentPolicy.php`
- `app/Filament/Resources/Payments/PaymentResource.php`
- `app/Filament/Resources/Payments/Pages/ListPayments.php`
- `app/Filament/Resources/Payments/Pages/CreatePayment.php`
- `app/Filament/Resources/Payments/Pages/EditPayment.php`
- `app/Filament/Resources/Payments/Schemas/PaymentForm.php`
- `app/Filament/Resources/Payments/Tables/PaymentsTable.php`
- `app/Filament/Resources/Contracts/RelationManagers/PaymentsRelationManager.php`
- `app/Filament/Resources/Contracts/Actions/CreateDepositPaymentAction.php`
- `app/Filament/Resources/Contracts/Actions/CreatePartialPaymentAction.php`

**Sửa (9 file):**
- `database/migrations/2026_01_01_000009_create_orders_table.php` (thêm 3 cột)
- `app/Models/Order.php`
- `app/Models/Contract.php`
- `app/Filament/Resources/Contracts/ContractResource.php` (bỏ RelationManager)
- `app/Filament/Resources/Contracts/Tables/ContractsTable.php` (kiểm tra & sửa cột payment)
- `app/Filament/Resources/Orders/Schemas/OrderForm.php` (thêm section tiền)
- `app/Filament/Resources/Orders/Tables/OrdersTable.php` (bỏ use PaymentType, sửa filter)
- `app/Filament/Widgets/ExecutiveKpiWidget.php`
- `app/Filament/Widgets/StatsOverviewWidget.php`
- `database/seeders/LedOsDataSeeder.php`
- `tests/Feature/ContractAndPaymentTest.php`

**Tùy chọn xoá (nếu không còn ai dùng):**
- `app/Enums/PaymentMethod.php`
- `app/Enums/PaymentType.php`

---

## Verification
1. `php artisan migrate:fresh --seed` → không lỗi, đầy đủ data.
2. Sidebar chỉ còn 3 Resource trong "Bán hàng & Dự án" (Quotation / Order / Contract).
3. Mở 1 Order → form có section "Thông tin thanh toán" với 3 cột.
4. Mở Order list → cột "Đặt cọc" vẫn render badge với 5 trạng thái y như cũ.
5. Filter "Tình trạng Đặt cọc" → Đã/Chưa đặt cọc vẫn hoạt động.
6. Dashboard widget vẫn hiển thị đúng tổng tiền đã thu.
7. `php artisan test --compact` → tất cả pass.

## Lưu ý / Rủi ro
- Contracts hiện `deposit_amount`/`contract_value` vẫn còn — KHÔNG động. Order `total_paid`/`deposit_paid` độc lập. Nếu muốn đồng bộ 2 chiều thì làm sau.
- Seeder gốc tạo 5 payment ứng với 4 Order. Khi refresh, cần đảm bảo các `Order::update` chạy **sau** khi tạo `Contract` (vì lấy `deposit_amount`/`contract_value` từ Contract).
- OrdersTable giảm N+1 query vì bỏ `with('payments', 'contracts.payments')` — chỉ còn `contracts`.