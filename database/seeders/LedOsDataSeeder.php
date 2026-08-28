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
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
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
use Spatie\Permission\Models\Permission;
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
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        // Sync Shield Permissions if permissions exist
        if (Permission::count() > 0) {
            $superAdminRole->syncPermissions(Permission::all());

            $salesPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Quotation%')
                    ->orWhere('name', 'like', '%:Customer%')
                    ->orWhere('name', 'like', '%:Contract%')
                    ->orWhere('name', 'like', '%:Order%')
                    ->orWhere('name', 'like', 'View%:Asset%')
                    ->orWhere('name', 'like', 'View%:Warehouse%')
                    ->orWhere('name', 'like', 'View%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:DeviceType%')
                    ->orWhere('name', 'like', 'View%:PricingRule%')
                    ->orWhere('name', 'like', 'View%:Payment%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
                    ->orWhere('name', 'like', 'View%:StatsOverview%')
                    ->orWhere('name', 'like', 'View%:LatestOrders%')
                    ->orWhere('name', 'like', 'View%:MonthlyRevenueChart%');
            })->whereNotIn('name', [
                'ForceDelete:Quotation', 'ForceDeleteAny:Quotation',
                'ForceDelete:Contract', 'ForceDeleteAny:Contract',
                'ForceDelete:Order', 'ForceDeleteAny:Order',
                'ForceDelete:Customer', 'ForceDeleteAny:Customer',
            ])->get();
            $salesRole->syncPermissions($salesPermissions);

            $whPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Asset%')
                    ->orWhere('name', 'like', '%:CheckinBatch%')
                    ->orWhere('name', 'like', '%:CheckoutBatch%')
                    ->orWhere('name', 'like', '%:ReturnBatch%')
                    ->orWhere('name', 'like', '%:RepairLog%')
                    ->orWhere('name', 'like', '%:Warehouse%')
                    ->orWhere('name', 'like', '%:ProductLine%')
                    ->orWhere('name', 'like', '%:DeviceType%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Customer%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:WarehouseStatusChart%')
                    ->orWhere('name', 'like', 'View%:AssetUtilizationReport%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%');
            })->get();
            $whRole->syncPermissions($whPermissions);

            $techPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:RepairLog%')
                    ->orWhere('name', 'like', 'View%:ReturnBatch%')
                    ->orWhere('name', 'like', 'Update:ReturnBatch%')
                    ->orWhere('name', 'like', 'View%:CheckoutBatch%')
                    ->orWhere('name', 'like', 'View%:Asset%')
                    ->orWhere('name', 'like', 'View%:Warehouse%')
                    ->orWhere('name', 'like', 'View%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:DeviceType%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
                    ->orWhere('name', 'like', 'View%:RepairFrequencyReport%');
            })->get();
            $techRole->syncPermissions($techPermissions);

            $accountantPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Contract%')
                    ->orWhere('name', 'like', '%:Payment%')
                    ->orWhere('name', 'like', '%:Customer%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Quotation%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:RevenueReport%')
                    ->orWhere('name', 'like', 'View%:StatsOverview%')
                    ->orWhere('name', 'like', 'View%:MonthlyRevenueChart%');
            })->get();
            $accountantRole->syncPermissions($accountantPermissions);
        }

        // 2. Warehouses (4 major hubs)
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

        // 3. Users & Personnel
        $admin = User::updateOrCreate(['email' => 'admin@ledmanager.com'], [
            'name' => 'Đỗ Quang Huy',
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

        $sales3 = User::updateOrCreate(['email' => 'sales3@ledmanager.com'], [
            'name' => 'Hoàng Gia Bảo',
            'password' => Hash::make('password'),
            'phone' => '0966 334 455',
            'warehouse_id' => $whDn->id,
            'is_active' => true,
        ]);
        $sales3->syncRoles([$salesRole]);

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

        $whStaff3 = User::updateOrCreate(['email' => 'khodn@ledmanager.com'], [
            'name' => 'Nguyễn Văn Thắng',
            'password' => Hash::make('password'),
            'phone' => '0944 223 344',
            'warehouse_id' => $whDn->id,
            'is_active' => true,
        ]);
        $whStaff3->syncRoles([$whRole]);

        $techUser1 = User::updateOrCreate(['email' => 'tech@ledmanager.com'], [
            'name' => 'Vũ Đình Trọng',
            'password' => Hash::make('password'),
            'phone' => '0918 990 011',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);
        $techUser1->syncRoles([$techRole]);

        $techUser2 = User::updateOrCreate(['email' => 'tech2@ledmanager.com'], [
            'name' => 'Phan Minh Khang',
            'password' => Hash::make('password'),
            'phone' => '0919 778 899',
            'warehouse_id' => $whHcm->id,
            'is_active' => true,
        ]);
        $techUser2->syncRoles([$techRole]);

        $techUser3 = User::updateOrCreate(['email' => 'tech3@ledmanager.com'], [
            'name' => 'Lê Quốc Hưng',
            'password' => Hash::make('password'),
            'phone' => '0922 556 677',
            'warehouse_id' => $whDn->id,
            'is_active' => true,
        ]);
        $techUser3->syncRoles([$techRole]);

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
            'name' => 'P1.5 Indoor',
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
            'name' => 'P2.6 Indoor',
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
            'name' => 'P2.9 Indoor',
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
            'name' => 'P3.9 Outdoor',
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
            'name' => 'P4.8 Outdoor',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 4.81,
            'environment' => ProductEnvironment::Outdoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 14.00,
            'power_watt' => 900.00,
            'brand' => 'Dicolor',
            'cabinet_material' => 'Die-cast Aluminum IP65',
            'is_active' => true,
        ]);

        // 5.1 Pricing Rules
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

        // 6. Customers (12 realistic accounts across Vietnam)
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
            [
                'code' => 'CUS-006',
                'name' => 'Ngân hàng TMCP Ngoại thương Việt Nam (Vietcombank)',
                'type' => CustomerType::Corporate,
                'phone' => '024 3934 3344',
                'email' => 'event@vietcombank.com.vn',
                'tax_code' => '0100112437',
                'address' => '198 Trần Quang Khải, Hoàn Kiếm, Hà Nội',
                'contact_person' => 'Phạm Đức Long',
                'note' => 'Sự kiện kỷ niệm thành lập & Hội nghị khách hàng VIP',
            ],
            [
                'code' => 'CUS-007',
                'name' => 'Tập đoàn Sun Group (Sun World & Hospitality)',
                'type' => CustomerType::Corporate,
                'phone' => '0236 3890 890',
                'email' => 'events@sungroup.com.vn',
                'tax_code' => '0400780287',
                'address' => 'Tầng 11 Tòa nhà Sun City, Hải Châu, Đà Nẵng',
                'contact_person' => 'Trịnh Mai Lan',
                'note' => 'Chuỗi lễ hội pháo hoa quốc tế Đà Nẵng DIFF & Sun World Ba Na Hills',
            ],
            [
                'code' => 'CUS-008',
                'name' => 'Đất Việt VAC Media Group',
                'type' => CustomerType::Agency,
                'phone' => '028 3823 4567',
                'email' => 'production@datvietvac.vn',
                'tax_code' => '0301456789',
                'address' => '200 Pasteur, Võ Thị Sáu, Quận 3, TP.HCM',
                'contact_person' => 'Ngô Thanh Hùng',
                'note' => 'Agency sản xuất gameshow truyền hình và live concerts lớn',
            ],
            [
                'code' => 'CUS-009',
                'name' => 'Tập đoàn Viettel (Viettel High Tech & Telecom)',
                'type' => CustomerType::Corporate,
                'phone' => '024 6255 6789',
                'email' => 'pr_events@viettel.com.vn',
                'tax_code' => '0100109106',
                'address' => 'Số 1 Trần Hữu Dực, Nam Từ Liêm, Hà Nội',
                'contact_person' => 'Nguyễn Tiến Dũng',
                'note' => 'Hội thảo công nghệ Quân sự & Viễn thông quốc tế',
            ],
            [
                'code' => 'CUS-010',
                'name' => 'Chloe Gallery & Hospitality Sài Gòn',
                'type' => CustomerType::Corporate,
                'phone' => '028 5410 9999',
                'email' => 'banquet@chloegallery.vn',
                'tax_code' => '0312345678',
                'address' => 'Phan Văn Chương, Tân Phú, Quận 7, TP.HCM',
                'contact_person' => 'Võ Tuyết Mai',
                'note' => 'Không gian tiệc cưới & luxury events cao cấp',
            ],
            [
                'code' => 'CUS-011',
                'name' => 'Công ty TNHH Sự Kiện & Truyền Thông Sao Mai',
                'type' => CustomerType::Agency,
                'phone' => '0236 3747 888',
                'email' => 'info@saomaievent.vn',
                'tax_code' => '0401889922',
                'address' => '45 Lê Duẩn, Hải Châu, Đà Nẵng',
                'contact_person' => 'Lê Văn Quang',
                'note' => 'Chuyên thầu màn hình LED các sự kiện miền Trung',
            ],
            [
                'code' => 'CUS-012',
                'name' => 'Bùi Minh Đức (Wedding & Private Party)',
                'type' => CustomerType::Individual,
                'phone' => '0909 123 456',
                'email' => 'duc.buiminh@gmail.com',
                'tax_code' => null,
                'address' => 'Khu đô thị Sala, TP. Thủ Đức, TP.HCM',
                'contact_person' => 'Bùi Minh Đức',
                'note' => 'Khách thuê cá nhân tiệc cưới cao cấp',
            ],
        ];

        $customers = collect();
        foreach ($customersData as $cData) {
            $customers->push(Customer::updateOrCreate(['code' => $cData['code']], $cData));
        }

        // 7. Assets Inventory across Warehouses
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

        // 7.1 Kho Hà Nội (WH-HN) additional cabinets & equipment
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

        // P1.5 in WH-HN (37 units)
        for ($i = 204; $i <= 240; $i++) {
            $serial = 'GE-R15-000'.$i;
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP15->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×0.5 m',
                'manufactured_date' => '2025-10-10',
                'purchase_cost' => 12000000,
                'purchase_date' => '2025-10-25',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        // P3.9 Outdoor in WH-HN (60 units)
        for ($i = 1; $i <= 60; $i++) {
            $serial = 'ABS-P39-HN-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP39->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×1.0 m',
                'manufactured_date' => '2025-11-20',
                'purchase_cost' => 10500000,
                'purchase_date' => '2025-12-05',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        // Processors, Flight cases, Sending cards in WH-HN
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

        for ($i = 1; $i <= 6; $i++) {
            $serial = 'SEND-VX-00'.$i;
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtSendingCard->id,
                'size' => '1U Master Controller',
                'manufactured_date' => '2025-10-15',
                'purchase_cost' => 22000000,
                'purchase_date' => '2025-11-01',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHn->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 25; $i++) {
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

        // 7.2 Kho TP.HCM (WH-HCM) assets
        for ($i = 1; $i <= 60; $i++) {
            $serial = 'GE-R26-HCM-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP26->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×0.5 m',
                'manufactured_date' => '2026-01-15',
                'purchase_cost' => 8500000,
                'purchase_date' => '2026-01-30',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHcm->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 50; $i++) {
            $serial = 'UNI-P29-HCM-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP29->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×1.0 m',
                'manufactured_date' => '2025-12-10',
                'purchase_cost' => 9800000,
                'purchase_date' => '2025-12-25',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHcm->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 60; $i++) {
            $serial = 'ABS-P39-HCM-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP39->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×1.0 m',
                'manufactured_date' => '2025-11-15',
                'purchase_cost' => 10500000,
                'purchase_date' => '2025-12-01',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHcm->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 4; $i++) {
            $serial = 'PROC-NV-HCM-0'.$i;
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtProcessor->id,
                'size' => '4K Ultra Video Processor',
                'manufactured_date' => '2025-10-15',
                'purchase_cost' => 52000000,
                'purchase_date' => '2025-11-01',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHcm->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 20; $i++) {
            $serial = 'FLY-HCM-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtFlycase->id,
                'size' => '6in1 Flight Case',
                'manufactured_date' => '2025-08-10',
                'purchase_cost' => 4500000,
                'purchase_date' => '2025-08-20',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whHcm->id,
            ]);
            $assets->push($asset);
        }

        // 7.3 Kho Đà Nẵng (WH-DN) assets
        for ($i = 1; $i <= 40; $i++) {
            $serial = 'GE-R26-DN-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP26->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×0.5 m',
                'manufactured_date' => '2026-01-20',
                'purchase_cost' => 8500000,
                'purchase_date' => '2026-02-05',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whDn->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 50; $i++) {
            $serial = 'ABS-P39-DN-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP39->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×1.0 m',
                'manufactured_date' => '2025-11-25',
                'purchase_cost' => 10500000,
                'purchase_date' => '2025-12-10',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whDn->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 2; $i++) {
            $serial = 'PROC-NV-DN-0'.$i;
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtProcessor->id,
                'size' => 'Novastar VX600 Set',
                'manufactured_date' => '2025-10-20',
                'purchase_cost' => 38000000,
                'purchase_date' => '2025-11-05',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whDn->id,
            ]);
            $assets->push($asset);
        }

        for ($i = 1; $i <= 12; $i++) {
            $serial = 'FLY-DN-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => null,
                'device_type_id' => $dtFlycase->id,
                'size' => '6in1 Flight Case',
                'manufactured_date' => '2025-08-15',
                'purchase_cost' => 4500000,
                'purchase_date' => '2025-08-25',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whDn->id,
            ]);
            $assets->push($asset);
        }

        // 7.4 Kho Cần Thơ (WH-CT) assets
        for ($i = 1; $i <= 30; $i++) {
            $serial = 'GE-R26-CT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $asset = Asset::updateOrCreate(['serial_no' => $serial], [
                'qr_code' => 'QR-'.$serial,
                'product_line_id' => $plP26->id,
                'device_type_id' => $dtCabinet->id,
                'size' => '0.5×0.5 m',
                'manufactured_date' => '2026-02-01',
                'purchase_cost' => 8500000,
                'purchase_date' => '2026-02-15',
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $whCt->id,
            ]);
            $assets->push($asset);
        }

        // 8. Quotations and BOM Generation
        $calcService = app(LedCalculationService::class);

        // QUO 1: 6m x 3.5m P2.6, 3 days (VinFast -> Converted to ORD-2608-01)
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

        // QUO 2: 4m x 2.5m P1.5, 2 days (Rex Hotel -> Converted to ORD-2608-02)
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
            'status' => QuotationStatus::Converted,
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

        // QUO 3: 12m x 6m P3.9 Outdoor, 4 days (Sun Group DIFF Đà Nẵng -> Converted to ORD-2608-03)
        $quoCfg3 = $calcService->deriveConfiguration(12.0, 6.0, $plP39);
        $quoPricing3 = $calcService->calculatePricing(12.0, 6.0, $plP39, 4, 6, 25.0);
        $quoBom3 = $calcService->generateBom(12.0, 6.0, $plP39, 4);

        $quotation3 = Quotation::updateOrCreate(['code' => 'QUO-2608-03'], [
            'customer_id' => $customers[6]->id, // Sun Group
            'sales_user_id' => $sales3->id,
            'screen_width_m' => 12.0,
            'screen_height_m' => 6.0,
            'screen_area_m2' => $quoCfg3['wall_area'],
            'product_line_id' => $plP39->id,
            'rental_days' => 4,
            'event_start_date' => now()->addDays(10)->toDateString(),
            'event_end_date' => now()->addDays(14)->toDateString(),
            'event_name' => 'Lễ Hội Pháo Hoa Quốc Tế DIFF 2026',
            'location' => 'Khán đài Sông Hàn, Đường Trần Hưng Đạo, Đà Nẵng',
            'estimated_cabinet_qty' => $quoCfg3['cabinets_qty'],
            'estimated_processor_qty' => 3,
            'estimated_load_kg' => $quoCfg3['load_kg'],
            'estimated_power_kw' => $quoCfg3['peak_power_kw'],
            'equipment_cost' => $quoPricing3['equipment_rental'],
            'labour_cost' => $quoPricing3['crew_labour'],
            'transport_cost' => $quoPricing3['transport'],
            'accessory_cost' => $quoPricing3['accessory'],
            'total_cost' => $quoPricing3['total_cost'],
            'discount_amount' => 5000000,
            'total_price' => $quoPricing3['total_price'],
            'margin_percent' => $quoPricing3['margin_percent'],
            'status' => QuotationStatus::Converted,
            'note' => 'Màn hình LED ngoài trời chống nước IP65, độ sáng 5000 nits',
        ]);

        foreach ($quoBom3 as $item) {
            QuotationItem::updateOrCreate([
                'quotation_id' => $quotation3->id,
                'description' => $item['item'],
            ], [
                'device_type_id' => $item['device_type_id'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);
        }

        // QUO 4: 10m x 4m P2.9, 5 days (Đất Việt VAC -> Converted to ORD-2608-04)
        $quoCfg4 = $calcService->deriveConfiguration(10.0, 4.0, $plP29);
        $quoPricing4 = $calcService->calculatePricing(10.0, 4.0, $plP29, 5, 5, 20.0);
        $quoBom4 = $calcService->generateBom(10.0, 4.0, $plP29, 5);

        $quotation4 = Quotation::updateOrCreate(['code' => 'QUO-2608-04'], [
            'customer_id' => $customers[7]->id, // Dat Viet VAC
            'sales_user_id' => $sales2->id,
            'screen_width_m' => 10.0,
            'screen_height_m' => 4.0,
            'screen_area_m2' => $quoCfg4['wall_area'],
            'product_line_id' => $plP29->id,
            'rental_days' => 5,
            'event_start_date' => now()->subDays(10)->toDateString(),
            'event_end_date' => now()->subDays(5)->toDateString(),
            'event_name' => "Gameshow Truyền Hình 'Anh Trai Say Hi'",
            'location' => 'Phim trường DatViet Media, TP. Thủ Đức, TP.HCM',
            'estimated_cabinet_qty' => $quoCfg4['cabinets_qty'],
            'estimated_processor_qty' => 2,
            'estimated_load_kg' => $quoCfg4['load_kg'],
            'estimated_power_kw' => $quoCfg4['peak_power_kw'],
            'equipment_cost' => $quoPricing4['equipment_rental'],
            'labour_cost' => $quoPricing4['crew_labour'],
            'transport_cost' => $quoPricing4['transport'],
            'accessory_cost' => $quoPricing4['accessory'],
            'total_cost' => $quoPricing4['total_cost'],
            'discount_amount' => 2000000,
            'total_price' => $quoPricing4['total_price'],
            'margin_percent' => $quoPricing4['margin_percent'],
            'status' => QuotationStatus::Converted,
            'note' => 'Ghi hình chương trình truyền hình âm nhạc, yêu cầu tần số quét 3840Hz',
        ]);

        foreach ($quoBom4 as $item) {
            QuotationItem::updateOrCreate([
                'quotation_id' => $quotation4->id,
                'description' => $item['item'],
            ], [
                'device_type_id' => $item['device_type_id'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);
        }

        // QUO 5: 8m x 4.5m P2.6, 2 days (FPT TechDay -> Converted to ORD-2608-05)
        $quoCfg5 = $calcService->deriveConfiguration(8.0, 4.5, $plP26);
        $quoPricing5 = $calcService->calculatePricing(8.0, 4.5, $plP26, 2, 4, 30.0);
        $quoBom5 = $calcService->generateBom(8.0, 4.5, $plP26, 2);

        $quotation5 = Quotation::updateOrCreate(['code' => 'QUO-2608-05'], [
            'customer_id' => $customers[4]->id, // FPT
            'sales_user_id' => $sales1->id,
            'screen_width_m' => 8.0,
            'screen_height_m' => 4.5,
            'screen_area_m2' => $quoCfg5['wall_area'],
            'product_line_id' => $plP26->id,
            'rental_days' => 2,
            'event_start_date' => now()->addDays(15)->toDateString(),
            'event_end_date' => now()->addDays(17)->toDateString(),
            'event_name' => 'Hội Nghị Thượng Đỉnh Công Nghệ FPT AI Summit 2026',
            'location' => 'Trung tâm Đổi mới Sáng tạo Quốc gia NIC, Hòa Lạc, Hà Nội',
            'estimated_cabinet_qty' => $quoCfg5['cabinets_qty'],
            'estimated_processor_qty' => 2,
            'estimated_load_kg' => $quoCfg5['load_kg'],
            'estimated_power_kw' => $quoCfg5['peak_power_kw'],
            'equipment_cost' => $quoPricing5['equipment_rental'],
            'labour_cost' => $quoPricing5['crew_labour'],
            'transport_cost' => $quoPricing5['transport'],
            'accessory_cost' => $quoPricing5['accessory'],
            'total_cost' => $quoPricing5['total_cost'],
            'discount_amount' => 0,
            'total_price' => $quoPricing5['total_price'],
            'margin_percent' => $quoPricing5['margin_percent'],
            'status' => QuotationStatus::Converted,
            'note' => 'Cấu hình màn LED siêu rộng phục vụ thuyết trình AI & Keynote',
        ]);

        foreach ($quoBom5 as $item) {
            QuotationItem::updateOrCreate([
                'quotation_id' => $quotation5->id,
                'description' => $item['item'],
            ], [
                'device_type_id' => $item['device_type_id'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);
        }

        // QUO 6: 5m x 3m P2.6, 1 day (Vietcombank -> Converted to ORD-2608-06)
        $quoCfg6 = $calcService->deriveConfiguration(5.0, 3.0, $plP26);
        $quoPricing6 = $calcService->calculatePricing(5.0, 3.0, $plP26, 1, 3, 15.0);
        $quoBom6 = $calcService->generateBom(5.0, 3.0, $plP26, 1);

        $quotation6 = Quotation::updateOrCreate(['code' => 'QUO-2608-06'], [
            'customer_id' => $customers[5]->id, // Vietcombank
            'sales_user_id' => $sales1->id,
            'screen_width_m' => 5.0,
            'screen_height_m' => 3.0,
            'screen_area_m2' => $quoCfg6['wall_area'],
            'product_line_id' => $plP26->id,
            'rental_days' => 1,
            'event_start_date' => now()->addDays(20)->toDateString(),
            'event_end_date' => now()->addDays(21)->toDateString(),
            'event_name' => 'Lễ Kỷ Niệm 63 Năm Thành Lập Vietcombank',
            'location' => 'Khách sạn JW Marriott, Đỗ Đức Dục, Hà Nội',
            'estimated_cabinet_qty' => $quoCfg6['cabinets_qty'],
            'estimated_processor_qty' => 1,
            'estimated_load_kg' => $quoCfg6['load_kg'],
            'estimated_power_kw' => $quoCfg6['peak_power_kw'],
            'equipment_cost' => $quoPricing6['equipment_rental'],
            'labour_cost' => $quoPricing6['crew_labour'],
            'transport_cost' => $quoPricing6['transport'],
            'accessory_cost' => $quoPricing6['accessory'],
            'total_cost' => $quoPricing6['total_cost'],
            'discount_amount' => 0,
            'total_price' => $quoPricing6['total_price'],
            'margin_percent' => $quoPricing6['margin_percent'],
            'status' => QuotationStatus::Converted,
            'note' => 'Sự kiện nội bộ cao cấp ngân hàng',
        ]);

        foreach ($quoBom6 as $item) {
            QuotationItem::updateOrCreate([
                'quotation_id' => $quotation6->id,
                'description' => $item['item'],
            ], [
                'device_type_id' => $item['device_type_id'],
                'quantity' => $item['qty'],
                'unit_cost' => $item['unit_cost'],
                'line_total' => $item['line_total'],
            ]);
        }

        // QUO 7: Approved Draft (Chloe Gallery)
        $quoCfg7 = $calcService->deriveConfiguration(6.0, 3.0, $plP29);
        $quoPricing7 = $calcService->calculatePricing(6.0, 3.0, $plP29, 2, 3, 20.0);
        $quotation7 = Quotation::updateOrCreate(['code' => 'QUO-2608-07'], [
            'customer_id' => $customers[9]->id, // Chloe Gallery
            'sales_user_id' => $sales2->id,
            'screen_width_m' => 6.0,
            'screen_height_m' => 3.0,
            'screen_area_m2' => $quoCfg7['wall_area'],
            'product_line_id' => $plP29->id,
            'rental_days' => 2,
            'event_start_date' => now()->addDays(25)->toDateString(),
            'event_end_date' => now()->addDays(27)->toDateString(),
            'event_name' => 'Triển Lãm Cưới Luxury Wedding Showcase 2026',
            'location' => 'Chloe Gallery Riverside, Quận 7, TP.HCM',
            'estimated_cabinet_qty' => $quoCfg7['cabinets_qty'],
            'estimated_processor_qty' => 1,
            'estimated_load_kg' => $quoCfg7['load_kg'],
            'estimated_power_kw' => $quoCfg7['peak_power_kw'],
            'equipment_cost' => $quoPricing7['equipment_rental'],
            'labour_cost' => $quoPricing7['crew_labour'],
            'transport_cost' => $quoPricing7['transport'],
            'accessory_cost' => $quoPricing7['accessory'],
            'total_cost' => $quoPricing7['total_cost'],
            'discount_amount' => 500000,
            'total_price' => $quoPricing7['total_price'],
            'margin_percent' => $quoPricing7['margin_percent'],
            'status' => QuotationStatus::Approved,
            'note' => 'Khách hàng đã chốt phương án kỹ thuật, chờ ký hợp đồng',
        ]);

        // QUO 8: Sent Quotation (Sao Mai Event)
        $quoCfg8 = $calcService->deriveConfiguration(14.0, 5.0, $plP39);
        $quoPricing8 = $calcService->calculatePricing(14.0, 5.0, $plP39, 3, 6, 35.0);
        $quotation8 = Quotation::updateOrCreate(['code' => 'QUO-2608-08'], [
            'customer_id' => $customers[10]->id, // Sao Mai Event
            'sales_user_id' => $sales3->id,
            'screen_width_m' => 14.0,
            'screen_height_m' => 5.0,
            'screen_area_m2' => $quoCfg8['wall_area'],
            'product_line_id' => $plP39->id,
            'rental_days' => 3,
            'event_start_date' => now()->addDays(30)->toDateString(),
            'event_end_date' => now()->addDays(33)->toDateString(),
            'event_name' => 'Lễ Hội Âm Nhạc Bãi Biển Mỹ Khê Summer Fest',
            'location' => 'Công viên Biển Đông, Đường Võ Nguyên Giáp, Đà Nẵng',
            'estimated_cabinet_qty' => $quoCfg8['cabinets_qty'],
            'estimated_processor_qty' => 3,
            'estimated_load_kg' => $quoCfg8['load_kg'],
            'estimated_power_kw' => $quoCfg8['peak_power_kw'],
            'equipment_cost' => $quoPricing8['equipment_rental'],
            'labour_cost' => $quoPricing8['crew_labour'],
            'transport_cost' => $quoPricing8['transport'],
            'accessory_cost' => $quoPricing8['accessory'],
            'total_cost' => $quoPricing8['total_cost'],
            'discount_amount' => 3000000,
            'total_price' => $quoPricing8['total_price'],
            'margin_percent' => $quoPricing8['margin_percent'],
            'status' => QuotationStatus::Sent,
            'note' => 'Đã gửi báo giá cho đối tác agency, đang chờ duyệt ngân sách',
        ]);

        // 9. Orders Lifecycle Management
        // 9.1 Order 1: VinFast VF3 (WH-HN) -> Dispatched
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
            'status' => OrderStatus::Dispatched,
            'sales_user_id' => $sales1->id,
            'note' => 'Xuất kho 84 Cabinet P2.6 kèm 14 Flight cases',
        ]);
        $quotation1->update(['converted_order_id' => $order1->id]);

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

        // 9.2 Order 2: Rex Hotel Gala (WH-HCM) -> OutboundCreated
        $order2 = Order::updateOrCreate(['order_no' => 'ORD-2608-02'], [
            'warehouse_id' => $whHcm->id,
            'customer_id' => $customers[2]->id,
            'quotation_id' => $quotation2->id,
            'request_date' => now()->addDays(7)->toDateString(),
            'expected_return_date' => now()->addDays(9)->toDateString(),
            'area_m2' => 10.0,
            'event' => 'Gala Dinner Hội nghị Doanh nhân TP.HCM',
            'device_type_id' => $dtCabinet->id,
            'value' => $quoPricing2['total_price'],
            'status' => OrderStatus::OutboundCreated,
            'sales_user_id' => $sales2->id,
            'note' => 'Đã tạo lệnh xuất kho 40 cabinet P1.5 tại kho HCM',
        ]);
        $quotation2->update(['converted_order_id' => $order2->id]);

        foreach ($quoBom2 as $item) {
            if ($item['device_type_id']) {
                OrderItem::updateOrCreate([
                    'order_id' => $order2->id,
                    'device_type_id' => $item['device_type_id'],
                    'note' => $item['item'],
                ], [
                    'quantity_required' => (int) $item['qty'],
                    'unit_price' => $item['unit_cost'],
                ]);
            }
        }

        // 9.3 Order 3: Sun Group DIFF Đà Nẵng (WH-DN) -> Confirmed / OutboundCreated
        $order3 = Order::updateOrCreate(['order_no' => 'ORD-2608-03'], [
            'warehouse_id' => $whDn->id,
            'customer_id' => $customers[6]->id,
            'quotation_id' => $quotation3->id,
            'request_date' => now()->addDays(10)->toDateString(),
            'expected_return_date' => now()->addDays(14)->toDateString(),
            'area_m2' => 72.0,
            'event' => 'Lễ Hội Pháo Hoa Quốc Tế DIFF 2026',
            'device_type_id' => $dtCabinet->id,
            'value' => $quoPricing3['total_price'],
            'status' => OrderStatus::OutboundCreated,
            'sales_user_id' => $sales3->id,
            'note' => 'Màn hình LED ngoài trời 72m2 lắp trên khán đài bờ sông Hàn',
        ]);
        $quotation3->update(['converted_order_id' => $order3->id]);

        foreach ($quoBom3 as $item) {
            if ($item['device_type_id']) {
                OrderItem::updateOrCreate([
                    'order_id' => $order3->id,
                    'device_type_id' => $item['device_type_id'],
                    'note' => $item['item'],
                ], [
                    'quantity_required' => (int) $item['qty'],
                    'unit_price' => $item['unit_cost'],
                ]);
            }
        }

        // 9.4 Order 4: Dat Viet VAC (WH-HCM) -> Completed (Historical returned order)
        $order4 = Order::updateOrCreate(['order_no' => 'ORD-2608-04'], [
            'warehouse_id' => $whHcm->id,
            'customer_id' => $customers[7]->id,
            'quotation_id' => $quotation4->id,
            'request_date' => now()->subDays(10)->toDateString(),
            'expected_return_date' => now()->subDays(5)->toDateString(),
            'area_m2' => 40.0,
            'event' => "Gameshow Truyền Hình 'Anh Trai Say Hi'",
            'device_type_id' => $dtCabinet->id,
            'value' => $quoPricing4['total_price'],
            'status' => OrderStatus::Completed,
            'sales_user_id' => $sales2->id,
            'note' => 'Đã hoàn tất sự kiện và thu hồi thiết bị về kho an toàn',
        ]);
        $quotation4->update(['converted_order_id' => $order4->id]);

        foreach ($quoBom4 as $item) {
            if ($item['device_type_id']) {
                OrderItem::updateOrCreate([
                    'order_id' => $order4->id,
                    'device_type_id' => $item['device_type_id'],
                    'note' => $item['item'],
                ], [
                    'quantity_required' => (int) $item['qty'],
                    'unit_price' => $item['unit_cost'],
                ]);
            }
        }

        // 9.5 Order 5: FPT AI Summit (WH-HN) -> Confirmed
        $order5 = Order::updateOrCreate(['order_no' => 'ORD-2608-05'], [
            'warehouse_id' => $whHn->id,
            'customer_id' => $customers[4]->id,
            'quotation_id' => $quotation5->id,
            'request_date' => now()->addDays(15)->toDateString(),
            'expected_return_date' => now()->addDays(17)->toDateString(),
            'area_m2' => 36.0,
            'event' => 'Hội Nghị Thượng Đỉnh Công Nghệ FPT AI Summit 2026',
            'device_type_id' => $dtCabinet->id,
            'value' => $quoPricing5['total_price'],
            'status' => OrderStatus::OutboundCreated,
            'sales_user_id' => $sales1->id,
            'note' => 'Đã xác nhận đơn hàng và tạo phiếu xuất kho, đang lên kế hoạch phân bổ kỹ thuật viên',
        ]);
        $quotation5->update(['converted_order_id' => $order5->id]);

        // 9.6 Order 6: Vietcombank Anniversary (WH-HN) -> Draft
        $order6 = Order::updateOrCreate(['order_no' => 'ORD-2608-06'], [
            'warehouse_id' => $whHn->id,
            'customer_id' => $customers[5]->id,
            'quotation_id' => $quotation6->id,
            'request_date' => now()->addDays(20)->toDateString(),
            'expected_return_date' => now()->addDays(21)->toDateString(),
            'area_m2' => 15.0,
            'event' => 'Lễ Kỷ Niệm 63 Năm Thành Lập Vietcombank',
            'device_type_id' => $dtCabinet->id,
            'value' => $quoPricing6['total_price'],
            'status' => OrderStatus::Draft,
            'sales_user_id' => $sales1->id,
            'note' => 'Đơn hàng mới tạo từ báo giá đã duyệt',
        ]);
        $quotation6->update(['converted_order_id' => $order6->id]);

        // 10. Checkin Batches (Nhập kho hàng mới nhập từ hãng)
        $inBatch1 = CheckinBatch::updateOrCreate(['code' => 'IN-2608-01'], [
            'warehouse_id' => $whHn->id,
            'note' => 'Nhập lô hàng 20 Cabinet Gloshine P2.6 mới xuất xưởng',
            'expected_date' => now()->subDays(15)->toDateString(),
            'status' => BatchStatus::Completed,
            'created_by' => $whStaff1->id,
            'completed_at' => now()->subDays(14),
        ]);

        $inAssetsHn = Asset::where('current_warehouse_id', $whHn->id)->take(5)->get();
        foreach ($inAssetsHn as $iAsset) {
            CheckinBatchItem::updateOrCreate([
                'checkin_batch_id' => $inBatch1->id,
                'asset_id' => $iAsset->id,
            ], [
                'condition' => 'ok',
                'condition_note' => 'Hàng nguyên seal thùng carton, test bóng hoạt động tốt',
                'is_received' => true,
                'received_by' => $whStaff1->id,
                'received_at' => now()->subDays(14),
            ]);
        }

        $inBatch2 = CheckinBatch::updateOrCreate(['code' => 'IN-2608-02'], [
            'warehouse_id' => $whHcm->id,
            'note' => 'Nhập 4 bộ xử lý hình ảnh Novastar Ultra 4K từ nhà phân phối',
            'expected_date' => now()->subDays(20)->toDateString(),
            'status' => BatchStatus::Completed,
            'created_by' => $whStaff2->id,
            'completed_at' => now()->subDays(19),
        ]);

        // 11. Checkout Batches (Xuất kho đi sự kiện)
        // 11.1 OUT-2608-01 (Order 1, WH-HN, InProgress)
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
        $dispatchedAssets1 = $assets->take(10);
        foreach ($dispatchedAssets1 as $dAsset) {
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

        // 11.2 OUT-2608-02 (Order 2, WH-HCM, Pending)
        $outBatch2 = CheckoutBatch::updateOrCreate(['code' => 'OUT-2608-02'], [
            'order_id' => $order2->id,
            'customer_id' => $customers[2]->id,
            'warehouse_id' => $whHcm->id,
            'required_area_m2' => 10.0,
            'device_type_id' => $dtCabinet->id,
            'expected_return_date' => now()->addDays(9)->toDateString(),
            'status' => BatchStatus::Pending,
            'created_by' => $whStaff2->id,
        ]);

        // 11.3 OUT-2608-04 (Order 4, WH-HCM, Completed)
        $outBatch4 = CheckoutBatch::updateOrCreate(['code' => 'OUT-2608-04'], [
            'order_id' => $order4->id,
            'customer_id' => $customers[7]->id,
            'warehouse_id' => $whHcm->id,
            'required_area_m2' => 40.0,
            'device_type_id' => $dtCabinet->id,
            'expected_return_date' => now()->subDays(5)->toDateString(),
            'status' => BatchStatus::Completed,
            'created_by' => $whStaff2->id,
            'dispatched_at' => now()->subDays(10),
        ]);

        $hcmDispatchedAssets = Asset::where('current_warehouse_id', $whHcm->id)->take(8)->get();
        foreach ($hcmDispatchedAssets as $hAsset) {
            CheckoutBatchItem::updateOrCreate([
                'checkout_batch_id' => $outBatch4->id,
                'asset_id' => $hAsset->id,
            ], [
                'is_dispatched' => true,
                'dispatched_by' => $whStaff2->id,
                'dispatched_at' => now()->subDays(10),
            ]);
        }

        // 12. Return Batches (Thu hồi thiết bị sau sự kiện)
        // 12.1 RET-2608-01 (Order 1 partial return simulation)
        $returnBatch1 = ReturnBatch::updateOrCreate(['code' => 'RET-2608-01'], [
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
                'return_batch_id' => $returnBatch1->id,
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

        // 12.2 RET-2608-04 (Order 4 complete return)
        $returnBatch4 = ReturnBatch::updateOrCreate(['code' => 'RET-2608-04'], [
            'checkout_batch_id' => $outBatch4->id,
            'status' => ReturnBatchStatus::Completed,
            'return_date' => now()->subDays(5)->toDateString(),
            'created_by' => $whStaff2->id,
            'completed_at' => now()->subDays(5),
            'note' => 'Thu hồi toàn bộ 40m2 màn hình P2.9 từ phim trường DatViet Media',
        ]);

        foreach ($outBatch4->items as $bItem) {
            ReturnBatchItem::updateOrCreate([
                'return_batch_id' => $returnBatch4->id,
                'asset_id' => $bItem->asset_id,
            ], [
                'checkout_batch_item_id' => $bItem->id,
                'grade' => ReturnGrade::Normal,
                'grade_note' => 'Kiểm tra tín hiệu và bề mặt bình thường',
                'is_received' => true,
                'received_by' => $whStaff2->id,
                'received_at' => now()->subDays(5),
            ]);
        }

        // 13. Maintenance & Repair Logs
        $repairAsset1 = Asset::where('serial_no', 'GE-R26-000107')->first();
        if ($repairAsset1) {
            $repairAsset1->update(['current_status' => AssetStatus::Repairing]);

            RepairLog::updateOrCreate([
                'asset_id' => $repairAsset1->id,
            ], [
                'start_date' => now()->subDays(3)->toDateString(),
                'end_date' => now()->toDateString(),
                'result_status' => RepairResultStatus::Fixed,
                'repair_note' => 'Thay thế 01 mắt LED P2.6 bị chập điểm ảnh, hàn lại cáp tín hiệu.',
                'repair_cost' => 450000,
                'created_by' => $techUser1->id,
            ]);

            AssetStatusLog::create([
                'asset_id' => $repairAsset1->id,
                'from_status' => AssetStatus::Ready,
                'to_status' => AssetStatus::Repairing,
                'from_warehouse_id' => $whHn->id,
                'to_warehouse_id' => $whHn->id,
                'source_type' => RepairLog::class,
                'changed_by' => $techUser1->id,
                'note' => 'Phát hiện chết 1 bóng LED sau sự kiện, đưa vào xưởng kỹ thuật',
            ]);
        }

        $repairAsset2 = Asset::where('serial_no', 'ABS-P39-HCM-005')->first();
        if ($repairAsset2) {
            $repairAsset2->update(['current_status' => AssetStatus::Repairing]);

            RepairLog::updateOrCreate([
                'asset_id' => $repairAsset2->id,
            ], [
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => null,
                'result_status' => RepairResultStatus::Pending,
                'repair_note' => 'Chập jack nguồn PowerCON chống nước sau cơn mưa lớn ngoài trời, đang chờ thay thế linh kiện chính hãng.',
                'repair_cost' => 600000,
                'created_by' => $techUser2->id,
            ]);
        }

        $repairAsset3 = Asset::where('serial_no', 'UNI-P29-HCM-012')->first();
        if ($repairAsset3) {
            RepairLog::updateOrCreate([
                'asset_id' => $repairAsset3->id,
            ], [
                'start_date' => now()->subDays(8)->toDateString(),
                'end_date' => now()->subDays(6)->toDateString(),
                'result_status' => RepairResultStatus::Fixed,
                'repair_note' => 'Thay thế bo mạch nhận Receiving Card Nova A5s Plus.',
                'repair_cost' => 850000,
                'created_by' => $techUser2->id,
            ]);
        }

        // 14. Contracts & Payments
        // Contract 1 (Active, 50% deposit paid)
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

        // Contract 2 (Signed, 50% deposit paid)
        $contract2 = Contract::updateOrCreate(['code' => 'HD-2608-02'], [
            'quotation_id' => $quotation2->id,
            'customer_id' => $customers[2]->id,
            'order_id' => $order2->id,
            'title' => 'Hợp đồng thuê màn hình LED P1.5 Gala Dinner Rex Hotel',
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

        Payment::updateOrCreate(['code' => 'PAY-2608-02'], [
            'contract_id' => $contract2->id,
            'order_id' => $order2->id,
            'customer_id' => $customers[2]->id,
            'type' => PaymentType::Deposit,
            'method' => PaymentMethod::BankTransfer,
            'amount' => $contract2->deposit_amount,
            'payment_date' => now()->toDateString(),
            'reference' => 'REX-GALA-DEP-02',
            'note' => 'Thu tiền đặt cọc 50% sự kiện Gala Rex',
            'received_by' => $admin->id,
        ]);

        // Contract 3 (Active, Sun Group DIFF)
        $contract3 = Contract::updateOrCreate(['code' => 'HD-2608-03'], [
            'quotation_id' => $quotation3->id,
            'customer_id' => $customers[6]->id,
            'order_id' => $order3->id,
            'title' => 'Hợp đồng cho thuê màn hình LED ngoài trời DIFF Đà Nẵng 2026',
            'signed_date' => now()->subDays(2)->toDateString(),
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'contract_value' => $quoPricing3['total_price'],
            'deposit_percent' => 50,
            'deposit_amount' => round($quoPricing3['total_price'] * 0.5),
            'status' => ContractStatus::Active,
            'sales_user_id' => $sales3->id,
            'created_by' => $admin->id,
        ]);

        Payment::updateOrCreate(['code' => 'PAY-2608-03'], [
            'contract_id' => $contract3->id,
            'order_id' => $order3->id,
            'customer_id' => $customers[6]->id,
            'type' => PaymentType::Deposit,
            'method' => PaymentMethod::BankTransfer,
            'amount' => $contract3->deposit_amount,
            'payment_date' => now()->subDays(2)->toDateString(),
            'reference' => 'SUN-DIFF-DEP-03',
            'note' => 'Tạm ứng đợt 1 hợp đồng lễ hội pháo hoa quốc tế DIFF',
            'received_by' => $admin->id,
        ]);

        // Contract 4 (Completed, 100% paid - Dat Viet VAC)
        $contract4 = Contract::updateOrCreate(['code' => 'HD-2608-04'], [
            'quotation_id' => $quotation4->id,
            'customer_id' => $customers[7]->id,
            'order_id' => $order4->id,
            'title' => "Hợp đồng thuê màn hình LED gameshow 'Anh Trai Say Hi'",
            'signed_date' => now()->subDays(15)->toDateString(),
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'contract_value' => $quoPricing4['total_price'],
            'deposit_percent' => 50,
            'deposit_amount' => round($quoPricing4['total_price'] * 0.5),
            'status' => ContractStatus::Completed,
            'sales_user_id' => $sales2->id,
            'created_by' => $admin->id,
        ]);

        Payment::updateOrCreate(['code' => 'PAY-2608-04-1'], [
            'contract_id' => $contract4->id,
            'order_id' => $order4->id,
            'customer_id' => $customers[7]->id,
            'type' => PaymentType::Deposit,
            'method' => PaymentMethod::BankTransfer,
            'amount' => $contract4->deposit_amount,
            'payment_date' => now()->subDays(15)->toDateString(),
            'reference' => 'DATVIET-DEP-04',
            'note' => 'Thu cọc 50% gameshow Anh Trai Say Hi',
            'received_by' => $admin->id,
        ]);

        Payment::updateOrCreate(['code' => 'PAY-2608-04-2'], [
            'contract_id' => $contract4->id,
            'order_id' => $order4->id,
            'customer_id' => $customers[7]->id,
            'type' => PaymentType::Final,
            'method' => PaymentMethod::BankTransfer,
            'amount' => $contract4->contract_value - $contract4->deposit_amount,
            'payment_date' => now()->subDays(4)->toDateString(),
            'reference' => 'DATVIET-FIN-04',
            'note' => 'Thanh toán quyết toán đợt cuối sau nghiệm thu',
            'received_by' => $admin->id,
        ]);

        // 15. Event Assignments & Milestones
        // 15.1 Order 1 (VinFast VF3)
        EventAssignment::updateOrCreate([
            'order_id' => $order1->id,
            'user_id' => $techUser1->id,
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

        $milestonesOrder1 = [
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

        foreach ($milestonesOrder1 as $ms) {
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

        // 15.2 Order 3 (Sun Group DIFF)
        EventAssignment::updateOrCreate([
            'order_id' => $order3->id,
            'user_id' => $techUser3->id,
            'role' => AssignmentRole::Lead,
        ], [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'note' => 'Chủ trì setup màn LED P3.9 ngoài trời và đồng bộ hệ thống pháo hoa',
        ]);

        EventAssignment::updateOrCreate([
            'order_id' => $order3->id,
            'user_id' => $whStaff3->id,
            'role' => AssignmentRole::Technician,
        ], [
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'note' => 'Vận hành cáp quang tín hiệu 500m từ phòng điều khiển',
        ]);

        // 15.3 Order 2 (Rex Hotel)
        EventAssignment::updateOrCreate([
            'order_id' => $order2->id,
            'user_id' => $techUser2->id,
            'role' => AssignmentRole::Lead,
        ], [
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
            'note' => 'Kỹ thuật viên phụ trách màn P1.5 sảnh Grand Ballroom',
        ]);
    }
}
