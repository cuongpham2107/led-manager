<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssignmentRole;
use App\Enums\BatchStatus;
use App\Enums\ContractStatus;
use App\Enums\CustomerType;
use App\Enums\DeviceUnit;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Enums\ProductEnvironment;
use App\Enums\QuotationStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\DeviceType;
use App\Models\EventAssignment;
use App\Models\EventMilestone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class LedOsDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $salesRole = Role::firstOrCreate(['name' => 'sales_executive', 'guard_name' => 'web']);
        $whRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $techRole = Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'web']);

        // 2. Warehouses
        $whHn = Warehouse::updateOrCreate(['code' => 'WH-HN'], [
            'name' => 'Kho Hà Nội (Tổng kho)',
            'address' => 'Số 18 Phạm Hùng, Cầu Giấy, Hà Nội',
            'phone' => '024 3987 6543',
            'is_active' => true,
        ]);

        $whHcm = Warehouse::updateOrCreate(['code' => 'WH-HCM'], [
            'name' => 'Kho TP. Hồ Chí Minh',
            'address' => '450 Nguyễn Thị Minh Khai, Quận 3, TP.HCM',
            'phone' => '028 3876 5432',
            'is_active' => true,
        ]);

        $whDn = Warehouse::updateOrCreate(['code' => 'WH-DN'], [
            'name' => 'Kho Đà Nẵng',
            'address' => '120 Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
            'phone' => '0236 3654 321',
            'is_active' => true,
        ]);

        $whCt = Warehouse::updateOrCreate(['code' => 'WH-CT'], [
            'name' => 'Kho Cần Thơ',
            'address' => '88 30 Tháng 4, Ninh Kiều, Cần Thơ',
            'phone' => '0292 3555 789',
            'is_active' => true,
        ]);

        // 3. Users
        $admin = User::updateOrCreate(['email' => 'admin@ledmanager.com'], [
            'name' => 'Do Quang Huy',
            'password' => Hash::make('password'),
            'phone' => '0912 345 678',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);
        $admin->syncRoles([$superAdminRole]);

        $sales1 = User::updateOrCreate(['email' => 'sales1@ledmanager.com'], [
            'name' => 'Trần Minh Tuấn',
            'password' => Hash::make('password'),
            'phone' => '0988 112 233',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);
        $sales1->syncRoles([$salesRole]);

        $sales2 = User::updateOrCreate(['email' => 'sales2@ledmanager.com'], [
            'name' => 'Nguyễn Bích Ngọc',
            'password' => Hash::make('password'),
            'phone' => '0977 445 566',
            'warehouse_id' => $whHcm->id,
            'is_active' => true,
        ]);
        $sales2->syncRoles([$salesRole]);

        $whStaff1 = User::updateOrCreate(['email' => 'khohn@ledmanager.com'], [
            'name' => 'Lê Hoàng Nam',
            'password' => Hash::make('password'),
            'phone' => '0903 667 788',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);
        $whStaff1->syncRoles([$whRole]);

        $whStaff2 = User::updateOrCreate(['email' => 'khohcm@ledmanager.com'], [
            'name' => 'Phạm Quốc Hưng',
            'password' => Hash::make('password'),
            'phone' => '0938 889 900',
            'warehouse_id' => $whHcm->id,
            'is_active' => true,
        ]);
        $whStaff2->syncRoles([$whRole]);

        $techUser = User::updateOrCreate(['email' => 'tech@ledmanager.com'], [
            'name' => 'Vũ Đình Trọng',
            'password' => Hash::make('password'),
            'phone' => '0918 990 011',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);
        $techUser->syncRoles([$techRole]);

        // 4. Device Types
        $dtCabinet = DeviceType::updateOrCreate(['code' => 'CAB'], [
            'name' => 'Cabinet LED Module',
            'unit' => DeviceUnit::Piece,
            'requires_serial' => true,
        ]);

        $dtProcessor = DeviceType::updateOrCreate(['code' => 'PROC'], [
            'name' => 'Bộ xử lý Video Processor',
            'unit' => DeviceUnit::Set,
            'requires_serial' => true,
        ]);

        $dtSendingCard = DeviceType::updateOrCreate(['code' => 'SEND'], [
            'name' => 'Sending Box / Master Controller',
            'unit' => DeviceUnit::Piece,
            'requires_serial' => true,
        ]);

        $dtTruss = DeviceType::updateOrCreate(['code' => 'TRUSS'], [
            'name' => 'Khung nhôm Truss & Flybar',
            'unit' => DeviceUnit::Bar,
            'requires_serial' => false,
        ]);

        $dtFlycase = DeviceType::updateOrCreate(['code' => 'FLY'], [
            'name' => 'Thùng đựng Flycase chuyên dụng',
            'unit' => DeviceUnit::Box,
            'requires_serial' => true,
        ]);

        $dtCable = DeviceType::updateOrCreate(['code' => 'CABLE'], [
            'name' => 'Bộ cáp nguồn & tín hiệu',
            'unit' => DeviceUnit::Cable,
            'requires_serial' => false,
        ]);

        // 5. Product Lines (P1.5, P2.6, P2.9, P3.9, P4.8)
        $plP15 = ProductLine::updateOrCreate(['code' => 'P1.5'], [
            'name' => 'P1.5',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 1.50,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 6.50,
            'power_watt' => 450.00,
            'brand' => 'Gloshine',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP26 = ProductLine::updateOrCreate(['code' => 'P2.6'], [
            'name' => 'P2.6',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 2.60,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 6.80,
            'power_watt' => 380.00,
            'brand' => 'Gloshine',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP29 = ProductLine::updateOrCreate(['code' => 'P2.9'], [
            'name' => 'P2.9',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 2.90,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 13.00,
            'power_watt' => 750.00,
            'brand' => 'Unilumin',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP39 = ProductLine::updateOrCreate(['code' => 'P3.9'], [
            'name' => 'P3.9',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 3.91,
            'environment' => ProductEnvironment::Outdoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 13.50,
            'power_watt' => 850.00,
            'brand' => 'Absen',
            'cabinet_material' => 'Die-cast Aluminum IP65',
            'is_active' => true,
        ]);

        $plP48 = ProductLine::updateOrCreate(['code' => 'P4.8'], [
            'name' => 'P4.8',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 4.81,
            'environment' => ProductEnvironment::Outdoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 14.00,
            'power_watt' => 900.00,
            'brand' => 'Dicolor',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        // 5.1 Pricing Rules (Flexible tiered pricing & agency discounts)
        $pricingRulesData = [
            // P2.6 standard tiers
            [
                'product_line_id' => $plP26->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 400000,
                'min_days' => 1,
                'max_days' => 1,
                'discount_percent' => 0,
            ],
            [
                'product_line_id' => $plP26->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 350000,
                'min_days' => 2,
                'max_days' => 3,
                'discount_percent' => 12.5,
            ],
            [
                'product_line_id' => $plP26->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 300000,
                'min_days' => 4,
                'max_days' => null,
                'discount_percent' => 25,
            ],
            // P2.6 Agency discount
            [
                'product_line_id' => $plP26->id,
                'customer_type' => CustomerType::Agency->value,
                'base_price_per_unit_per_day' => 350000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 10,
            ],
            // P1.5 High-end standard
            [
                'product_line_id' => $plP15->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 700000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
            // P2.9 Indoor
            [
                'product_line_id' => $plP29->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 450000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
            // P3.9 Outdoor
            [
                'product_line_id' => $plP39->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 380000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
            // P4.8 Outdoor
            [
                'product_line_id' => $plP48->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 320000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
        ];

        foreach ($pricingRulesData as $pr) {
            PricingRule::updateOrCreate([
                'product_line_id' => $pr['product_line_id'],
                'customer_type' => $pr['customer_type'],
                'min_days' => $pr['min_days'],
            ], [
                'base_price_per_unit_per_day' => $pr['base_price_per_unit_per_day'],
                'max_days' => $pr['max_days'],
                'discount_percent' => $pr['discount_percent'],
                'crew_rate_per_person_per_day' => 1600000,
                'transport_rate_per_km' => 28000,
                'accessory_rate_per_m2' => 50000,
                'is_active' => true,
            ]);
        }

        // 6. Customers
        $customersData = [
            [
                'code' => 'CUS-001',
                'name' => 'Tập đoàn Vingroup (VinFast & Vincom Events)',
                'type' => CustomerType::Corporate,
                'phone' => '024 3974 9999',
                'email' => 'events@vingroup.net',
                'tax_code' => '0101245486',
                'address' => 'Số 7 Đường Bằng Lăng 1, Vinhomes Riverside, Long Biên, Hà Nội',
                'contact_person' => 'Nguyễn Phương Thảo',
                'note' => 'Khách hàng VIP, yêu cầu thiết bị đồng bộ độ sáng cao',
            ],
            [
                'code' => 'CUS-002',
                'name' => 'Apex Media & Entertainment Agency',
                'type' => CustomerType::Agency,
                'phone' => '028 3910 8888',
                'email' => 'production@apexmedia.vn',
                'tax_code' => '0309876543',
                'address' => 'Tầng 12 Tòa nhà Bitexco, Q1, TP.HCM',
                'contact_person' => 'Đặng Tuấn Anh',
                'note' => 'Agency chuyên tổ chức concert và festival âm nhạc',
            ],
            [
                'code' => 'CUS-003',
                'name' => 'Khách sạn Rex Sài Gòn',
                'type' => CustomerType::Corporate,
                'phone' => '028 3829 2185',
                'email' => 'banquet@rexhotel.com.vn',
                'tax_code' => '0300587921',
                'address' => '141 Nguyễn Huệ, Bến Nghé, Quận 1, TP.HCM',
                'contact_person' => 'Lê Thanh Bình',
                'note' => 'Thường xuyên thuê màn P2.6 phục vụ gala dinner',
            ],
            [
                'code' => 'CUS-004',
                'name' => 'Golden Event JSC (Sự kiện Vàng)',
                'type' => CustomerType::Agency,
                'phone' => '024 3788 6677',
                'email' => 'ops@goldenevent.vn',
                'tax_code' => '0106543210',
                'address' => 'Toà nhà Keangnam Landmark 72, Nam Từ Liêm, Hà Nội',
                'contact_person' => 'Vũ Mai Hương',
                'note' => 'Đối tác thuê thường xuyên theo tháng',
            ],
            [
                'code' => 'CUS-005',
                'name' => 'Tập đoàn FPT (FPT Telecom & Software)',
                'type' => CustomerType::Corporate,
                'phone' => '024 7300 7300',
                'email' => 'events@fpt.com.vn',
                'tax_code' => '0101248141',
                'address' => 'Tòa nhà FPT Cầu Giấy, Phố Duy Tân, Hà Nội',
                'contact_person' => 'Trần Đình Quân',
                'note' => 'Sự kiện Year End Party & AI Summit',
            ],
        ];

        $customers = collect();
        foreach ($customersData as $cData) {
            $customers->push(Customer::updateOrCreate(['code' => $cData['code']], $cData));
        }

        // 7. Assets (Serials exactly matching stock screenshot)
        $exactSerials = [
            // P1.5 (0.5×0.5 m)
            ['serial' => 'GE-R15-000201', 'pl' => $plP15, 'size' => '0.5×0.5 m', 'mfd' => '2025-09-02'],
            ['serial' => 'GE-R15-000202', 'pl' => $plP15, 'size' => '0.5×0.5 m', 'mfd' => '2025-09-02'],
            ['serial' => 'GE-R15-000203', 'pl' => $plP15, 'size' => '0.5×0.5 m', 'mfd' => '2026-03-20'],
            // P2.6 (0.5×0.5 m)
            ['serial' => 'GE-R18-000301', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-03-20'],
            ['serial' => 'GE-R18-000302', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-03-20'],
            ['serial' => 'GE-R26-000101', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-02-10'],
            ['serial' => 'GE-R26-000102', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-02-10'],
            ['serial' => 'GE-R26-000105', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-02-10'],
            ['serial' => 'GE-R26-000106', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-02-10'],
            ['serial' => 'GE-R26-000107', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2025-11-14'],
            ['serial' => 'GE-R26-000109', 'pl' => $plP26, 'size' => '0.5×0.5 m', 'mfd' => '2026-02-10'],
            // P2.9 (0.5×1 m)
            ['serial' => 'GE-R29-000103', 'pl' => $plP29, 'size' => '0.5×1 m', 'mfd' => '2025-11-14'],
            ['serial' => 'GE-R29-000104', 'pl' => $plP29, 'size' => '0.5×1 m', 'mfd' => '2025-11-14'],
            ['serial' => 'GE-R29-000108', 'pl' => $plP29, 'size' => '0.5×1 m', 'mfd' => '2026-02-10'],
            ['serial' => 'GE-R29-000110', 'pl' => $plP29, 'size' => '0.5×1 m', 'mfd' => '2025-11-14'],
        ];

        $assets = collect();
        foreach ($exactSerials as $s) {
            $asset = Asset::updateOrCreate(['serial_no' => $s['serial']], [
                'qr_code' => 'QR-'.$s['serial'],
                'product_line_id' => $s['pl']->id,
                'device_type_id' => $dtCabinet->id,
                'size' => $s['size'],
                'manufactured_date' => $s['mfd'],
                'purchase_cost' => 8500000,
                'purchase_date' => Carbon::parse($s['mfd'])->addDays(10)->toDateString(),
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        // Additional cabinets to fulfill full 84-cabinet screen simulations
        for ($i = 111; $i <= 194; $i++) {
            $serial = 'GE-R26-000'.$i;
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP26->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×0.5 m',
                'manufactured_date' => '2026-02-10',
                'purchase_cost' => 8500000,
                'purchase_date' => '2026-02-20',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        // Processors, Flight cases
        for ($i = 1; $i <= 6; $i++) {
            $serial = 'PROC-NV-00'.$i;
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtProcessor->id,
                'size' => '2U Rack',
                'manufactured_date' => '2025-10-15',
                'purchase_cost' => 45000000,
                'purchase_date' => '2025-11-01',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 15; $i++) {
            $serial = 'FLY-CASE-0'.str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtFlycase->id,
                'size' => '6in1 Flight Case',
                'manufactured_date' => '2025-08-10',
                'purchase_cost' => 4500000,
                'purchase_date' => '2025-08-20',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        // 8. Quotation 1: 6m x 3.5m P2.6, 3 days (Converted to Order)
        $calcService = app(LedCalculationService::class);
        $quoCfg1 = $calcService->deriveConfiguration(6.0, 3.5, $plP26);
        $quoPricing1 = $calcService->calculatePricing(6.0, 3.5, $plP26, 3, 4, 45.0);
        $quoBom1 = $calcService->generateBom(6.0, 3.5, $plP26, 3);

        $quotation1 = Quotation::updateOrCreate(['code' => 'QUO-2608-01'], [
            'customer_id' => $customers[0]->id,
            'sales_user_id' => $sales1->id,
            'screen_width_m' => 6.0,
            'screen_height_m' => 3.5,
            'screen_area_m2' => $quoCfg1['wall_area'],
            'product_line_id' => $plP26->id,
            'rental_days' => 3,
            'event_start_date' => now()->addDays(2)->toDateString(),
            'event_end_date' => now()->addDays(5)->toDateString(),
            'event_name' => 'Lễ Ra Mắt Xe Điện VinFast VF3',
            'location' => 'Trung tâm Hội nghị Quốc gia NCC, Hà Nội',
            'estimated_cabinet_qty' => $quoCfg1['cabinets_qty'],
            'estimated_processor_qty' => 2,
            'estimated_load_kg' => $quoCfg1['load_kg'],
            'estimated_power_kw' => $quoCfg1['peak_power_kw'],
            'equipment_cost' => $quoPricing1['equipment_rental'],
            'labour_cost' => $quoPricing1['crew_labour'],
            'transport_cost' => $quoPricing1['transport'],
            'accessory_cost' => $quoPricing1['accessory'],
            'total_cost' => $quoPricing1['total_cost'],
            'discount_amount' => 0,
            'total_price' => $quoPricing1['total_price'],
            'margin_percent' => $quoPricing1['margin_percent'],
            'status' => QuotationStatus::Converted,
            'note' => 'Báo giá chuẩn theo cấu hình LED Cost Estimator (84 Cabinet P2.6, 21 m2)',
        ]);

        foreach ($quoBom1 as $item) {
            QuotationItem::updateOrCreate([
                'quotation_id' => $quotation1->id,
                'description' => $item['item'],
            ], [
                'device_type_id' => $item['device_type_id'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);
        }

        // Quotation 2: 4m x 2.5m P1.5, 2 days (Approved draft)
        $quoCfg2 = $calcService->deriveConfiguration(4.0, 2.5, $plP15);
        $quoPricing2 = $calcService->calculatePricing(4.0, 2.5, $plP15, 2, 2, 15.0);
        $quoBom2 = $calcService->generateBom(4.0, 2.5, $plP15, 2);

        $quotation2 = Quotation::updateOrCreate(['code' => 'QUO-2608-02'], [
            'customer_id' => $customers[2]->id,
            'sales_user_id' => $sales2->id,
            'screen_width_m' => 4.0,
            'screen_height_m' => 2.5,
            'screen_area_m2' => $quoCfg2['wall_area'],
            'product_line_id' => $plP15->id,
            'rental_days' => 2,
            'event_start_date' => now()->addDays(7)->toDateString(),
            'event_end_date' => now()->addDays(9)->toDateString(),
            'event_name' => 'Gala Dinner Hội nghị Doanh nhân TP.HCM',
            'location' => 'Sảnh Grand Ballroom, Khách sạn Rex Sài Gòn',
            'estimated_cabinet_qty' => $quoCfg2['cabinets_qty'],
            'estimated_processor_qty' => 1,
            'estimated_load_kg' => $quoCfg2['load_kg'],
            'estimated_power_kw' => $quoCfg2['peak_power_kw'],
            'equipment_cost' => $quoPricing2['equipment_rental'],
            'labour_cost' => $quoPricing2['crew_labour'],
            'transport_cost' => $quoPricing2['transport'],
            'accessory_cost' => $quoPricing2['accessory'],
            'total_cost' => $quoPricing2['total_cost'],
            'discount_amount' => 1000000,
            'total_price' => $quoPricing2['total_price'],
            'margin_percent' => $quoPricing2['margin_percent'],
            'status' => QuotationStatus::Approved,
            'note' => 'Màn hình LED P1.5 độ nét cao phục vụ trình chiếu 4K',
        ]);

        foreach ($quoBom2 as $item) {
            QuotationItem::updateOrCreate([
                'quotation_id' => $quotation2->id,
                'description' => $item['item'],
            ], [
                'device_type_id' => $item['device_type_id'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);
        }

        // 9. Order 1 from Quotation 1
        $order1 = Order::updateOrCreate(['order_no' => 'ORD-2608-01'], [
            'warehouse_id' => $whHn->id,
            'customer_id' => $customers[0]->id,
            'quotation_id' => $quotation1->id,
            'request_date' => now()->addDays(2)->toDateString(),
            'expected_return_date' => now()->addDays(5)->toDateString(),
            'area_m2' => 21.0,
            'event' => 'Lễ Ra Mắt Xe Điện VinFast VF3',
            'device_type_id' => $dtCabinet->id,
            'value' => $quoPricing1['total_price'],
            'status' => OrderStatus::OutboundCreated,
            'sales_user_id' => $sales1->id,
            'note' => 'Xuất kho 84 Cabinet P2.6 kèm 14 Flight cases',
        ]);

        $quotation1->update(['converted_order_id' => $order1->id]);

        // Copy entire BOM to Order Items
        foreach ($quoBom1 as $item) {
            if ($item['device_type_id']) {
                OrderItem::updateOrCreate([
                    'order_id' => $order1->id,
                    'device_type_id' => $item['device_type_id'],
                    'note' => $item['item'],
                ], [
                    'quantity_required' => (int) $item['qty'],
                    'unit_price' => $item['unit_cost'],
                ]);
            }
        }

        // 10. Checkout Batch for Order 1
        $outBatch1 = CheckoutBatch::updateOrCreate(['code' => 'OUT-2608-01'], [
            'order_id' => $order1->id,
            'customer_id' => $customers[0]->id,
            'warehouse_id' => $whHn->id,
            'required_area_m2' => 21.0,
            'device_type_id' => $dtCabinet->id,
            'expected_return_date' => now()->addDays(5)->toDateString(),
            'status' => BatchStatus::InProgress,
            'created_by' => $whStaff1->id,
        ]);

        // Seed 10 scanned checkout items
        $dispatchedAssets = $assets->take(10);
        foreach ($dispatchedAssets as $dAsset) {
            CheckoutBatchItem::updateOrCreate([
                'checkout_batch_id' => $outBatch1->id,
                'asset_id' => $dAsset->id,
            ], [
                'is_dispatched' => true,
                'dispatched_by' => $whStaff1->id,
                'dispatched_at' => now(),
            ]);

            $dAsset->update(['current_status' => AssetStatus::InTransit]);

            AssetStatusLog::create([
                'asset_id' => $dAsset->id,
                'from_status' => AssetStatus::Ready,
                'to_status' => AssetStatus::InTransit,
                'from_warehouse_id' => $whHn->id,
                'to_warehouse_id' => $whHn->id,
                'source_type' => CheckoutBatch::class,
                'source_id' => $outBatch1->id,
                'changed_by' => $whStaff1->id,
                'note' => "Xuất kho đi sự kiện {$order1->event}",
            ]);
        }

        // 11. Sample Return Batch (Completed event simulation)
        $returnBatch = ReturnBatch::updateOrCreate(['code' => 'RET-2608-01'], [
            'checkout_batch_id' => $outBatch1->id,
            'status' => ReturnBatchStatus::Completed,
            'return_date' => now()->toDateString(),
            'created_by' => $whStaff1->id,
            'completed_at' => now(),
            'note' => 'Thu hồi lô màn hình P2.6, kiểm tra hoạt động tốt',
        ]);

        $firstItem = $outBatch1->items()->first();
        if ($firstItem) {
            ReturnBatchItem::updateOrCreate([
                'return_batch_id' => $returnBatch->id,
                'asset_id' => $firstItem->asset_id,
            ], [
                'checkout_batch_item_id' => $firstItem->id,
                'grade' => ReturnGrade::Normal,
                'grade_note' => 'Cabinet hoạt động hoàn hảo, module sáng đều',
                'is_received' => true,
                'received_by' => $whStaff1->id,
                'received_at' => now(),
            ]);
        }

        // 12. Sample Repair Log for one asset
        $repairAsset = Asset::where('serial_no', 'GE-R26-000107')->first();
        if ($repairAsset) {
            $repairAsset->update(['current_status' => AssetStatus::Repairing]);

            RepairLog::updateOrCreate([
                'asset_id' => $repairAsset->id,
            ], [
                'start_date' => now()->subDays(3)->toDateString(),
                'end_date' => now()->toDateString(),
                'result_status' => RepairResultStatus::Fixed,
                'repair_note' => 'Thay thế 01 mắt LED P2.6 bị chập điểm ảnh, hàn lại cáp tín hiệu.',
                'repair_cost' => 450000,
                'created_by' => $techUser->id,
            ]);

            AssetStatusLog::create([
                'asset_id' => $repairAsset->id,
                'from_status' => AssetStatus::Ready,
                'to_status' => AssetStatus::Repairing,
                'from_warehouse_id' => $whHn->id,
                'to_warehouse_id' => $whHn->id,
                'source_type' => RepairLog::class,
                'changed_by' => $techUser->id,
                'note' => 'Phát hiện chết 1 bóng LED sau sự kiện, đưa vào xưởng kỹ thuật',
            ]);
        }

        // 13. Contracts & Payments
        $contract1 = Contract::updateOrCreate(['code' => 'HD-2608-01'], [
            'quotation_id' => $quotation1->id,
            'customer_id' => $customers[0]->id,
            'order_id' => $order1->id,
            'title' => 'Hợp đồng thuê màn hình LED P2.6 sự kiện VinFast VF3',
            'signed_date' => now()->subDays(1)->toDateString(),
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'contract_value' => $quoPricing1['total_price'],
            'deposit_percent' => 50,
            'deposit_amount' => round($quoPricing1['total_price'] * 0.5),
            'status' => ContractStatus::Active,
            'sales_user_id' => $sales1->id,
            'created_by' => $admin->id,
        ]);

        Payment::updateOrCreate(['code' => 'PAY-2608-01'], [
            'contract_id' => $contract1->id,
            'order_id' => $order1->id,
            'customer_id' => $customers[0]->id,
            'type' => PaymentType::Deposit,
            'method' => PaymentMethod::BankTransfer,
            'amount' => $contract1->deposit_amount,
            'payment_date' => now()->subDays(1)->toDateString(),
            'reference' => 'VCOM-VF3-DEP-01',
            'note' => 'Thu tiền đặt cọc 50% trước khi xuất kho màn hình',
            'received_by' => $admin->id,
        ]);

        $contract2 = Contract::updateOrCreate(['code' => 'HD-2608-02'], [
            'quotation_id' => $quotation2->id,
            'customer_id' => $customers[2]->id,
            'title' => 'Hợp đồng thuê màn hình LED P1.5 triển lãm công nghệ TechExpo',
            'signed_date' => now()->toDateString(),
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
            'contract_value' => $quoPricing2['total_price'],
            'deposit_percent' => 50,
            'deposit_amount' => round($quoPricing2['total_price'] * 0.5),
            'status' => ContractStatus::Signed,
            'sales_user_id' => $sales2->id,
            'created_by' => $admin->id,
        ]);

        // 14. Event Assignments for Order 1
        EventAssignment::updateOrCreate([
            'order_id' => $order1->id,
            'user_id' => $techUser->id,
            'role' => AssignmentRole::Lead,
        ], [
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'note' => 'Chỉ đạo kỹ thuật thi công và điều khiển tín hiệu Novastar 4K',
        ]);

        EventAssignment::updateOrCreate([
            'order_id' => $order1->id,
            'user_id' => $whStaff1->id,
            'role' => AssignmentRole::Technician,
        ], [
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'note' => 'Hỗ trợ lắp đặt khung treo Truss và nối cáp nguồn',
        ]);

        // 15. Event Milestones for Order 1
        $milestones = [
            [
                'type' => MilestoneType::Delivery,
                'planned_at' => now()->addDays(2)->setHour(8)->setMinute(0),
                'actual_at' => null,
                'status' => MilestoneStatus::Pending,
                'note' => 'Xe tải 2.5 tấn chuyển 14 flight cases đến Trung tâm Hội nghị Quốc gia',
            ],
            [
                'type' => MilestoneType::Setup,
                'planned_at' => now()->addDays(2)->setHour(10)->setMinute(0),
                'actual_at' => null,
                'status' => MilestoneStatus::Pending,
                'note' => 'Dựng khung Truss và ghép 84 cabinet P2.6',
            ],
            [
                'type' => MilestoneType::Testing,
                'planned_at' => now()->addDays(2)->setHour(16)->setMinute(0),
                'actual_at' => null,
                'status' => MilestoneStatus::Pending,
                'note' => 'Chạy thử video 4K HDR và cân bằng trắng',
            ],
            [
                'type' => MilestoneType::EventStart,
                'planned_at' => now()->addDays(3)->setHour(9)->setMinute(0),
                'actual_at' => null,
                'status' => MilestoneStatus::Pending,
                'note' => 'Sự kiện chính thức diễn ra',
            ],
            [
                'type' => MilestoneType::Teardown,
                'planned_at' => now()->addDays(5)->setHour(18)->setMinute(0),
                'actual_at' => null,
                'status' => MilestoneStatus::Pending,
                'note' => 'Tháo dỡ và kiểm kê thiết bị tại chỗ',
            ],
        ];

        foreach ($milestones as $ms) {
            EventMilestone::updateOrCreate([
                'order_id' => $order1->id,
                'type' => $ms['type'],
            ], [
                'planned_at' => $ms['planned_at'],
                'actual_at' => $ms['actual_at'],
                'status' => $ms['status'],
                'note' => $ms['note'],
            ]);
        }
    }
}
