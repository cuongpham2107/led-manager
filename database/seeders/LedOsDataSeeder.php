<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Customer;
use App\Models\DeviceType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LedOsDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Warehouses
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

        $warehouses = collect([$whHn, $whHcm, $whDn, $whCt]);

        // 2. Users
        $admin = User::updateOrCreate(['email' => 'admin@ledmanager.com'], [
            'name' => 'Do Quang Huy',
            'password' => Hash::make('password'),
            'phone' => '0912 345 678',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);

        $sales1 = User::updateOrCreate(['email' => 'sales1@ledmanager.com'], [
            'name' => 'Trần Minh Tuấn',
            'password' => Hash::make('password'),
            'phone' => '0988 112 233',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);

        $sales2 = User::updateOrCreate(['email' => 'sales2@ledmanager.com'], [
            'name' => 'Nguyễn Bích Ngọc',
            'password' => Hash::make('password'),
            'phone' => '0977 445 566',
            'warehouse_id' => $whHcm->id,
            'is_active' => true,
        ]);

        $whStaff1 = User::updateOrCreate(['email' => 'khohn@ledmanager.com'], [
            'name' => 'Lê Hoàng Nam',
            'password' => Hash::make('password'),
            'phone' => '0903 667 788',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);

        $whStaff2 = User::updateOrCreate(['email' => 'khohcm@ledmanager.com'], [
            'name' => 'Phạm Quốc Hưng',
            'password' => Hash::make('password'),
            'phone' => '0938 889 900',
            'warehouse_id' => $whHcm->id,
            'is_active' => true,
        ]);

        $techUser = User::updateOrCreate(['email' => 'tech@ledmanager.com'], [
            'name' => 'Vũ Đình Trọng',
            'password' => Hash::make('password'),
            'phone' => '0918 990 011',
            'warehouse_id' => $whHn->id,
            'is_active' => true,
        ]);

        $users = collect([$admin, $sales1, $sales2, $whStaff1, $whStaff2, $techUser]);

        // 3. Device Types
        $dtCabinet = DeviceType::updateOrCreate(['code' => 'CAB'], [
            'name' => 'Cabinet LED Module',
            'unit' => 'piece',
            'requires_serial' => true,
        ]);

        $dtProcessor = DeviceType::updateOrCreate(['code' => 'PROC'], [
            'name' => 'Bộ xử lý Video Processor',
            'unit' => 'set',
            'requires_serial' => true,
        ]);

        $dtSendingCard = DeviceType::updateOrCreate(['code' => 'SEND'], [
            'name' => 'Sending Box / Master Controller',
            'unit' => 'piece',
            'requires_serial' => true,
        ]);

        $dtTruss = DeviceType::updateOrCreate(['code' => 'TRUSS'], [
            'name' => 'Khung nhôm Truss & Flybar',
            'unit' => 'set',
            'requires_serial' => false,
        ]);

        $dtFlycase = DeviceType::updateOrCreate(['code' => 'FLY'], [
            'name' => 'Thùng đựng Flycase chuyên dụng',
            'unit' => 'piece',
            'requires_serial' => true,
        ]);

        $dtCable = DeviceType::updateOrCreate(['code' => 'CABLE'], [
            'name' => 'Bộ cáp nguồn & tín hiệu',
            'unit' => 'set',
            'requires_serial' => false,
        ]);

        // 4. Product Lines
        $plP25In = ProductLine::updateOrCreate(['code' => 'P25-IN'], [
            'name' => 'P2.5 Indoor High-Refresh',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 2.50,
            'environment' => 'indoor',
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 7.50,
            'power_watt' => 600.00,
            'brand' => 'Gloshine',
            'cabinet_material' => 'Nhôm đúc Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP391In = ProductLine::updateOrCreate(['code' => 'P391-IN'], [
            'name' => 'P3.91 Indoor Ultra-Slim',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 3.91,
            'environment' => 'indoor',
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 7.20,
            'power_watt' => 550.00,
            'brand' => 'Unilumin',
            'cabinet_material' => 'Magnesium Alloy',
            'is_active' => true,
        ]);

        $plP391Out = ProductLine::updateOrCreate(['code' => 'P391-OUT'], [
            'name' => 'P3.91 Outdoor High-Brightness IP65',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 3.91,
            'environment' => 'outdoor',
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 13.50,
            'power_watt' => 850.00,
            'brand' => 'Absen',
            'cabinet_material' => 'Nhôm đúc chống nước IP65',
            'is_active' => true,
        ]);

        $plP481Out = ProductLine::updateOrCreate(['code' => 'P481-OUT'], [
            'name' => 'P4.81 Outdoor Stadium Rental',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 4.81,
            'environment' => 'outdoor',
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 14.00,
            'power_watt' => 900.00,
            'brand' => 'Dicolor',
            'cabinet_material' => 'Nhôm đúc cường lực',
            'is_active' => true,
        ]);

        $plP186Cob = ProductLine::updateOrCreate(['code' => 'P186-COB'], [
            'name' => 'P1.86 COB Studio Master 4K',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 1.86,
            'environment' => 'indoor',
            'module_width_mm' => 600.00,
            'module_height_mm' => 337.50,
            'weight_kg' => 6.80,
            'power_watt' => 450.00,
            'brand' => 'Leyard',
            'cabinet_material' => 'Carbon Fiber Frame',
            'is_active' => true,
        ]);

        $productLines = collect([$plP25In, $plP391In, $plP391Out, $plP481Out, $plP186Cob]);

        // 5. Customers
        $customersData = [
            [
                'code' => 'CUS-001',
                'name' => 'Tập đoàn Vingroup (VinFast & Vincom Events)',
                'type' => 'corporate',
                'phone' => '024 3974 9999',
                'email' => 'events@vingroup.net',
                'tax_code' => '0101245486',
                'address' => 'Số 7 Đường Bằng Lăng 1, Vinhomes Riverside, Long Biên, Hà Nội',
                'contact_person' => 'Nguyễn Phương Thảo (Trưởng phòng Event)',
                'note' => 'Khách hàng VIP, yêu cầu thiết bị đồng bộ độ sáng cao',
            ],
            [
                'code' => 'CUS-002',
                'name' => 'Apex Media & Entertainment Agency',
                'type' => 'agency',
                'phone' => '028 3910 8888',
                'email' => 'production@apexmedia.vn',
                'tax_code' => '0309876543',
                'address' => 'Tầng 12 Tòa nhà Bitexco, Q1, TP.HCM',
                'contact_person' => 'Đặng Tuấn Anh (Technical Director)',
                'note' => 'Agency chuyên tổ chức concert và festival âm nhạc',
            ],
            [
                'code' => 'CUS-003',
                'name' => 'Khách sạn Rex Sài Gòn',
                'type' => 'corporate',
                'phone' => '028 3829 2185',
                'email' => 'banquet@rexhotel.com.vn',
                'tax_code' => '0300587921',
                'address' => '141 Nguyễn Huệ, Bến Nghé, Quận 1, TP.HCM',
                'contact_person' => 'Lê Thanh Bình (Quản lý Hội nghị)',
                'note' => 'Thường xuyên thuê màn P2.5 phục vụ gala dinner',
            ],
            [
                'code' => 'CUS-004',
                'name' => 'Golden Event JSC (Sự kiện Vàng)',
                'type' => 'agency',
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
                'type' => 'corporate',
                'phone' => '024 7300 7300',
                'email' => 'internal_events@fpt.com.vn',
                'tax_code' => '0101248141',
                'address' => 'Tòa nhà FPT Cầu Giấy, Phố Duy Tân, Hà Nội',
                'contact_person' => 'Trần Đình Quân',
                'note' => 'Sự kiện Year End Party & ra mắt sản phẩm',
            ],
            [
                'code' => 'CUS-006',
                'name' => 'ABC Corp Conference & Exhibitions',
                'type' => 'corporate',
                'phone' => '0909 112 334',
                'email' => 'contact@abccorp.vn',
                'tax_code' => '0312345678',
                'address' => '72 Lê Thánh Tôn, Bến Nghé, Quận 1, TP.HCM',
                'contact_person' => 'Hoàng Nhật Minh',
                'note' => 'Hội nghị xúc tiến thương mại quốc tế',
            ],
            [
                'code' => 'CUS-007',
                'name' => 'XYZ Product Launch & Marketing',
                'type' => 'agency',
                'phone' => '0933 555 777',
                'email' => 'launch@xyzmedia.com',
                'tax_code' => '0315678901',
                'address' => '33 Lê Duẩn, Bến Nghé, Quận 1, TP.HCM',
                'contact_person' => 'Đỗ Minh Trí',
                'note' => 'Ra mắt sản phẩm điện thoại flagship',
            ],
            [
                'code' => 'CUS-008',
                'name' => 'Rồng Việt Pro Audio & Lighting',
                'type' => 'agency',
                'phone' => '0908 666 888',
                'email' => 'rongvietav@gmail.com',
                'tax_code' => '0308889999',
                'address' => '156 Huỳnh Tấn Phát, Quận 7, TP.HCM',
                'contact_person' => 'Phạm Gia Bảo',
                'note' => 'Nhà thầu phụ âm thanh ánh sáng sân khấu',
            ],
        ];

        $customers = collect();
        foreach ($customersData as $cData) {
            $customers->push(Customer::updateOrCreate(['code' => $cData['code']], $cData));
        }

        // 6. Assets (~70 assets across all categories & warehouses)
        $assets = collect();
        $assetConfigs = [
            ['prefix' => 'GE-R26-', 'pl' => $plP25In, 'dt' => $dtCabinet, 'size' => '500x500mm', 'cost' => 8500000, 'count' => 30],
            ['prefix' => 'GE-R15-', 'pl' => $plP391In, 'dt' => $dtCabinet, 'size' => '500x500mm', 'cost' => 6500000, 'count' => 20],
            ['prefix' => 'GE-R39-', 'pl' => $plP391Out, 'dt' => $dtCabinet, 'size' => '500x1000mm', 'cost' => 12000000, 'count' => 16],
            ['prefix' => 'GE-R48-', 'pl' => $plP481Out, 'dt' => $dtCabinet, 'size' => '500x1000mm', 'cost' => 10500000, 'count' => 12],
            ['prefix' => 'PROC-NV-', 'pl' => null, 'dt' => $dtProcessor, 'size' => '2U Rack', 'cost' => 45000000, 'count' => 6],
            ['prefix' => 'SEND-CL-', 'pl' => null, 'dt' => $dtSendingCard, 'size' => '1U Rack', 'cost' => 18000000, 'count' => 6],
            ['prefix' => 'FLY-CASE-', 'pl' => null, 'dt' => $dtFlycase, 'size' => '8in1 Cabinet Case', 'cost' => 4500000, 'count' => 10],
        ];

        $statuses = [
            'ready', 'ready', 'ready', 'ready', 'ready',
            'in_event', 'in_event',
            'in_transit',
            'repairing',
        ];

        foreach ($assetConfigs as $cfg) {
            for ($i = 1; $i <= $cfg['count']; $i++) {
                $serialNo = $cfg['prefix'].str_pad($i, ($cfg['count'] > 99 ? 4 : 3), '0', STR_PAD_LEFT);
                $status = $statuses[array_rand($statuses)];
                $wh = $warehouses->random();

                $asset = Asset::updateOrCreate(['serial_no' => $serialNo], [
                    'qr_code' => 'QR-'.$serialNo,
                    'product_line_id' => $cfg['pl']?->id,
                    'device_type_id' => $cfg['dt']->id,
                    'size' => $cfg['size'],
                    'manufactured_date' => now()->subMonths(rand(3, 36))->toDateString(),
                    'purchase_cost' => $cfg['cost'],
                    'purchase_date' => now()->subMonths(rand(2, 30))->toDateString(),
                    'current_status' => $status,
                    'current_warehouse_id' => $wh->id,
                    'note' => $status === 'repairing' ? 'Module pixel bị lỗi cần thay thế' : null,
                ]);

                $assets->push($asset);
            }
        }

        // 7. Quotations & Quotation Items
        $quotationSpecs = [
            [
                'code' => 'QUO-2608-01',
                'customer' => $customers[0], // Vingroup
                'sales' => $sales1,
                'product_line' => $plP25In,
                'event_name' => 'Lễ Ra Mắt Xe Điện VinFast VF3',
                'location' => 'Trung tâm Hội nghị Quốc gia NCC, Hà Nội',
                'screen_w' => 12.0,
                'screen_h' => 4.5,
                'rental_days' => 3,
                'status' => 'converted',
                'eq_cost' => 54000000,
                'labour_cost' => 12000000,
                'transport_cost' => 6000000,
                'accessory_cost' => 4000000,
                'discount' => 5000000,
                'total_price' => 95000000,
            ],
            [
                'code' => 'QUO-2608-02',
                'customer' => $customers[1], // Apex
                'sales' => $sales2,
                'product_line' => $plP391Out,
                'event_name' => 'Music Festival Countdown 2027',
                'location' => 'Phố đi bộ Nguyễn Huệ, Quận 1, TP.HCM',
                'screen_w' => 18.0,
                'screen_h' => 6.0,
                'rental_days' => 4,
                'status' => 'approved',
                'eq_cost' => 120000000,
                'labour_cost' => 25000000,
                'transport_cost' => 15000000,
                'accessory_cost' => 8000000,
                'discount' => 10000000,
                'total_price' => 220000000,
            ],
            [
                'code' => 'QUO-2608-03',
                'customer' => $customers[2], // Rex Hotel
                'sales' => $sales2,
                'product_line' => $plP25In,
                'event_name' => 'Gala Dinner Kỷ Niệm 30 Năm Thành Lập',
                'location' => 'Sảnh Lotus, Khách sạn Rex Sài Gòn',
                'screen_w' => 8.0,
                'screen_h' => 3.5,
                'rental_days' => 2,
                'status' => 'converted',
                'eq_cost' => 28000000,
                'labour_cost' => 6000000,
                'transport_cost' => 3000000,
                'accessory_cost' => 2000000,
                'discount' => 2000000,
                'total_price' => 48000000,
            ],
            [
                'code' => 'QUO-2608-04',
                'customer' => $customers[3], // Golden Event
                'sales' => $sales1,
                'product_line' => $plP391In,
                'event_name' => 'Hội Thảo Tech Expo 2026',
                'location' => 'Cung Triển lãm Kiến trúc Quy hoạch, Hà Nội',
                'screen_w' => 10.0,
                'screen_h' => 4.0,
                'rental_days' => 3,
                'status' => 'sent',
                'eq_cost' => 40000000,
                'labour_cost' => 8000000,
                'transport_cost' => 4000000,
                'accessory_cost' => 3000000,
                'discount' => 0,
                'total_price' => 68000000,
            ],
            [
                'code' => 'QUO-2608-05',
                'customer' => $customers[4], // FPT
                'sales' => $sales1,
                'product_line' => $plP186Cob,
                'event_name' => 'FPT Techday 2026 - AI Summit',
                'location' => 'Sân vận động Quần Ngựa, Ba Đình, Hà Nội',
                'screen_w' => 15.0,
                'screen_h' => 5.0,
                'rental_days' => 3,
                'status' => 'draft',
                'eq_cost' => 150000000,
                'labour_cost' => 20000000,
                'transport_cost' => 10000000,
                'accessory_cost' => 8000000,
                'discount' => 15000000,
                'total_price' => 260000000,
            ],
            [
                'code' => 'QUO-2608-06',
                'customer' => $customers[5], // ABC Corp
                'sales' => $sales2,
                'product_line' => $plP391Out,
                'event_name' => 'ABC International Partner Summit',
                'location' => 'Gem Center, Quận 1, TP.HCM',
                'screen_w' => 9.0,
                'screen_h' => 4.0,
                'rental_days' => 2,
                'status' => 'converted',
                'eq_cost' => 36000000,
                'labour_cost' => 7000000,
                'transport_cost' => 4000000,
                'accessory_cost' => 3000000,
                'discount' => 0,
                'total_price' => 62000000,
            ],
        ];

        $quotations = collect();
        foreach ($quotationSpecs as $qSpec) {
            $area = $qSpec['screen_w'] * $qSpec['screen_h'];
            $cabQty = (int) ceil($area / 0.25);
            $totalCost = $qSpec['eq_cost'] + $qSpec['labour_cost'] + $qSpec['transport_cost'] + $qSpec['accessory_cost'];
            $margin = $qSpec['total_price'] > 0 ? (($qSpec['total_price'] - $totalCost) / $qSpec['total_price']) * 100 : 0;

            $quo = Quotation::updateOrCreate(['code' => $qSpec['code']], [
                'customer_id' => $qSpec['customer']->id,
                'sales_user_id' => $qSpec['sales']->id,
                'screen_width_m' => $qSpec['screen_w'],
                'screen_height_m' => $qSpec['screen_h'],
                'screen_area_m2' => $area,
                'product_line_id' => $qSpec['product_line']->id,
                'rental_days' => $qSpec['rental_days'],
                'event_start_date' => now()->addDays(rand(5, 20))->toDateString(),
                'event_end_date' => now()->addDays(rand(21, 25))->toDateString(),
                'event_name' => $qSpec['event_name'],
                'location' => $qSpec['location'],
                'estimated_cabinet_qty' => $cabQty,
                'estimated_processor_qty' => 2,
                'estimated_load_kg' => $cabQty * 7.5,
                'estimated_power_kw' => ($cabQty * 0.6) * 0.7,
                'equipment_cost' => $qSpec['eq_cost'],
                'labour_cost' => $qSpec['labour_cost'],
                'transport_cost' => $qSpec['transport_cost'],
                'accessory_cost' => $qSpec['accessory_cost'],
                'total_cost' => $totalCost,
                'discount_amount' => $qSpec['discount'],
                'total_price' => $qSpec['total_price'],
                'margin_percent' => round($margin, 2),
                'status' => $qSpec['status'],
                'note' => 'Báo giá đã bao gồm kỹ thuật viên trực 24/7 trong thời gian chạy sự kiện',
            ]);

            // Quotation Items
            QuotationItem::updateOrCreate(['quotation_id' => $quo->id, 'description' => 'Cabinet LED '.$qSpec['product_line']->name], [
                'category' => 'equipment',
                'device_type_id' => $dtCabinet->id,
                'quantity' => $cabQty,
                'unit' => 'piece',
                'unit_cost' => $qSpec['eq_cost'] / max(1, $cabQty),
                'line_total' => $qSpec['eq_cost'],
            ]);

            QuotationItem::updateOrCreate(['quotation_id' => $quo->id, 'description' => 'Bộ điều khiển & xử lý tín hiệu Video 4K'], [
                'category' => 'equipment',
                'device_type_id' => $dtProcessor->id,
                'quantity' => 2,
                'unit' => 'set',
                'unit_cost' => 5000000,
                'line_total' => 10000000,
            ]);

            QuotationItem::updateOrCreate(['quotation_id' => $quo->id, 'description' => 'Nhân công lắp đặt, căn chỉnh và tháo dỡ'], [
                'category' => 'labour',
                'device_type_id' => null,
                'quantity' => 4,
                'unit' => 'person',
                'unit_cost' => $qSpec['labour_cost'] / 4,
                'line_total' => $qSpec['labour_cost'],
            ]);

            $quotations->push($quo);
        }

        // 8. Orders & Order Items
        $orderSpecs = [
            [
                'order_no' => 'ORD-2608-01',
                'quotation' => $quotations[0],
                'customer' => $customers[0],
                'warehouse' => $whHn,
                'sales' => $sales1,
                'event' => 'Lễ Ra Mắt Xe Điện VinFast VF3',
                'area_m2' => 54.0,
                'value' => 95000000,
                'status' => 'dispatched',
                'req_date' => now()->subDays(2)->toDateString(),
                'return_date' => now()->addDays(3)->toDateString(),
            ],
            [
                'order_no' => 'ORD-2608-02',
                'quotation' => $quotations[2],
                'customer' => $customers[2],
                'warehouse' => $whHcm,
                'sales' => $sales2,
                'event' => 'Gala Dinner Kỷ Niệm 30 Năm Thành Lập',
                'area_m2' => 28.0,
                'value' => 48000000,
                'status' => 'outbound_created',
                'req_date' => now()->addDays(1)->toDateString(),
                'return_date' => now()->addDays(4)->toDateString(),
            ],
            [
                'order_no' => 'ORD-2608-03',
                'quotation' => $quotations[5],
                'customer' => $customers[5],
                'warehouse' => $whHcm,
                'sales' => $sales2,
                'event' => 'ABC International Partner Summit',
                'area_m2' => 36.0,
                'value' => 62000000,
                'status' => 'completed',
                'req_date' => now()->subDays(10)->toDateString(),
                'return_date' => now()->subDays(6)->toDateString(),
            ],
            [
                'order_no' => 'ORD-2608-04',
                'quotation' => null,
                'customer' => $customers[4],
                'warehouse' => $whHn,
                'sales' => $sales1,
                'event' => 'FPT Global Kick-off Meeting 2026',
                'area_m2' => 40.0,
                'value' => 70000000,
                'status' => 'draft',
                'req_date' => now()->addDays(7)->toDateString(),
                'return_date' => now()->addDays(10)->toDateString(),
            ],
        ];

        $orders = collect();
        foreach ($orderSpecs as $oSpec) {
            $order = Order::updateOrCreate(['order_no' => $oSpec['order_no']], [
                'warehouse_id' => $oSpec['warehouse']->id,
                'customer_id' => $oSpec['customer']->id,
                'quotation_id' => $oSpec['quotation']?->id,
                'request_date' => $oSpec['req_date'],
                'expected_return_date' => $oSpec['return_date'],
                'area_m2' => $oSpec['area_m2'],
                'event' => $oSpec['event'],
                'device_type_id' => $dtCabinet->id,
                'value' => $oSpec['value'],
                'status' => $oSpec['status'],
                'sales_user_id' => $oSpec['sales']->id,
                'note' => 'Đơn hàng thi công chuẩn bị trước 12 tiếng',
            ]);

            if ($oSpec['quotation']) {
                $oSpec['quotation']->update(['converted_order_id' => $order->id]);
            }

            // Order Items
            OrderItem::updateOrCreate(['order_id' => $order->id, 'product_line_id' => $plP25In->id], [
                'device_type_id' => $dtCabinet->id,
                'quantity_required' => (int) ($oSpec['area_m2'] / 0.25),
                'unit_price' => 800000,
                'note' => 'Cabinet 500x500mm đồng màu',
            ]);

            OrderItem::updateOrCreate(['order_id' => $order->id, 'product_line_id' => null, 'device_type_id' => $dtProcessor->id], [
                'quantity_required' => 2,
                'unit_price' => 5000000,
                'note' => 'Novastar VX1000 Pro',
            ]);

            $orders->push($order);
        }

        // 9. Checkin Batches (Nhập kho hàng mới)
        $inBatch1 = CheckinBatch::updateOrCreate(['code' => 'IN-2608-01'], [
            'warehouse_id' => $whHn->id,
            'note' => 'Nhập lô 20 Cabinet P2.5 Indoor mới từ hãng Gloshine',
            'expected_date' => now()->subDays(15)->toDateString(),
            'status' => 'completed',
            'created_by' => $whStaff1->id,
            'completed_at' => now()->subDays(14),
        ]);

        $inBatch2 = CheckinBatch::updateOrCreate(['code' => 'IN-2608-02'], [
            'warehouse_id' => $whHcm->id,
            'note' => 'Nhập 10 bộ khung Truss nhôm mới',
            'expected_date' => now()->addDays(3)->toDateString(),
            'status' => 'pending',
            'created_by' => $whStaff2->id,
            'completed_at' => null,
        ]);

        // Link items to inBatch1
        $hnAssets = $assets->where('current_warehouse_id', $whHn->id)->take(8);
        foreach ($hnAssets as $ast) {
            CheckinBatchItem::updateOrCreate([
                'checkin_batch_id' => $inBatch1->id,
                'asset_id' => $ast->id,
            ], [
                'condition' => 'ok',
                'condition_note' => 'Kiểm tra điểm ảnh và độ sáng 100% đạt chuẩn',
                'is_received' => true,
                'received_by' => $whStaff1->id,
                'received_at' => now()->subDays(14),
            ]);
        }

        // 10. Checkout Batches (Xuất kho đi sự kiện)
        $outBatch1 = CheckoutBatch::updateOrCreate(['code' => 'OUT-2608-01'], [
            'order_id' => $orders[0]->id, // ORD-2608-01
            'customer_id' => $customers[0]->id,
            'warehouse_id' => $whHn->id,
            'required_area_m2' => 54.0,
            'device_type_id' => $dtCabinet->id,
            'expected_return_date' => now()->addDays(3)->toDateString(),
            'status' => 'dispatched',
            'created_by' => $whStaff1->id,
            'dispatched_at' => now()->subDays(2),
        ]);

        $outBatch2 = CheckoutBatch::updateOrCreate(['code' => 'OUT-2608-02'], [
            'order_id' => $orders[1]->id, // ORD-2608-02
            'customer_id' => $customers[2]->id,
            'warehouse_id' => $whHcm->id,
            'required_area_m2' => 28.0,
            'device_type_id' => $dtCabinet->id,
            'expected_return_date' => now()->addDays(4)->toDateString(),
            'status' => 'in_progress',
            'created_by' => $whStaff2->id,
            'dispatched_at' => null,
        ]);

        $outBatch3 = CheckoutBatch::updateOrCreate(['code' => 'OUT-2608-03'], [
            'order_id' => $orders[2]->id, // ORD-2608-03
            'customer_id' => $customers[5]->id,
            'warehouse_id' => $whHcm->id,
            'required_area_m2' => 36.0,
            'device_type_id' => $dtCabinet->id,
            'expected_return_date' => now()->subDays(6)->toDateString(),
            'status' => 'dispatched',
            'created_by' => $whStaff2->id,
            'dispatched_at' => now()->subDays(10),
        ]);

        // Attach items to CheckoutBatch 1
        $outAssets1 = $assets->where('current_warehouse_id', $whHn->id)->take(12);
        $outItems1 = collect();
        foreach ($outAssets1 as $ast) {
            $ast->update(['current_status' => 'in_event']);
            $outItem = CheckoutBatchItem::updateOrCreate([
                'checkout_batch_id' => $outBatch1->id,
                'asset_id' => $ast->id,
            ], [
                'is_dispatched' => true,
                'dispatched_by' => $whStaff1->id,
                'dispatched_at' => now()->subDays(2),
            ]);
            $outItems1->push($outItem);
        }

        // Attach items to CheckoutBatch 3 (completed order)
        $outAssets3 = $assets->where('current_warehouse_id', $whHcm->id)->take(10);
        $outItems3 = collect();
        foreach ($outAssets3 as $ast) {
            $outItem = CheckoutBatchItem::updateOrCreate([
                'checkout_batch_id' => $outBatch3->id,
                'asset_id' => $ast->id,
            ], [
                'is_dispatched' => true,
                'dispatched_by' => $whStaff2->id,
                'dispatched_at' => now()->subDays(10),
            ]);
            $outItems3->push($outItem);
        }

        // 11. Return Batches (Nhập trả sau sự kiện)
        $retBatch1 = ReturnBatch::updateOrCreate(['code' => 'RET-2608-01'], [
            'checkout_batch_id' => $outBatch3->id,
            'return_date' => now()->subDays(6)->toDateString(),
            'note' => 'Nhập trả hoàn tất từ sự kiện ABC International Partner Summit',
            'status' => 'completed',
            'created_by' => $whStaff2->id,
            'completed_at' => now()->subDays(6),
        ]);

        foreach ($outItems3 as $index => $cItem) {
            $isDamaged = ($index === 2); // 1 item damaged for demo
            $grade = $isDamaged ? 'damaged' : 'normal';
            $gradeNote = $isDamaged ? 'Mặt kính bị nứt do va đập khi tháo dỡ, cần thay module' : 'Thiết bị hoạt động tốt';

            ReturnBatchItem::updateOrCreate([
                'return_batch_id' => $retBatch1->id,
                'asset_id' => $cItem->asset_id,
            ], [
                'checkout_batch_item_id' => $cItem->id,
                'grade' => $grade,
                'grade_note' => $gradeNote,
                'is_received' => true,
                'received_by' => $whStaff2->id,
                'received_at' => now()->subDays(6),
            ]);

            // Update asset status
            Asset::where('id', $cItem->asset_id)->update([
                'current_status' => $isDamaged ? 'repairing' : 'ready',
            ]);
        }

        // 12. Repair Logs (Bảo dưỡng & Sửa chữa)
        $repairAssets = $assets->where('current_status', 'repairing')->take(4);
        foreach ($repairAssets as $index => $rAsset) {
            $resultStatus = $index === 0 ? 'pending' : 'fixed';
            $endDate = $resultStatus === 'fixed' ? now()->subDays(1)->toDateString() : null;

            RepairLog::updateOrCreate([
                'asset_id' => $rAsset->id,
                'start_date' => now()->subDays(rand(3, 10))->toDateString(),
            ], [
                'end_date' => $endDate,
                'repair_note' => 'Hàn lại chân IC driver và thay thế 2 bóng LED SMD bị chết màu xanh',
                'result_status' => $resultStatus,
                'repair_cost' => rand(400000, 1500000),
                'created_by' => $techUser->id,
            ]);
        }

        // 13. Asset Status Logs (Audit Trail)
        $logSamples = [
            ['from' => 'ready', 'to' => 'in_transit', 'src' => $outBatch1, 'note' => 'Xe tải nhận thiết bị xuất kho'],
            ['from' => 'in_transit', 'to' => 'in_event', 'src' => $outBatch1, 'note' => 'Lắp đặt hoàn thiện tại Trung tâm Hội nghị NCC'],
            ['from' => 'in_event', 'to' => 'in_transit', 'src' => $retBatch1, 'note' => 'Tháo dỡ vận chuyển về kho HCM'],
            ['from' => 'in_transit', 'to' => 'ready', 'src' => $retBatch1, 'note' => 'Kiểm tra và xếp vào giá kho A2'],
        ];

        foreach ($assets->take(15) as $ast) {
            foreach ($logSamples as $sample) {
                AssetStatusLog::create([
                    'asset_id' => $ast->id,
                    'from_status' => $sample['from'],
                    'to_status' => $sample['to'],
                    'from_warehouse_id' => $whHn->id,
                    'to_warehouse_id' => $whHn->id,
                    'source_type' => get_class($sample['src']),
                    'source_id' => $sample['src']->id,
                    'changed_by' => $admin->id,
                    'note' => $sample['note'],
                    'created_at' => now()->subDays(rand(1, 14))->subHours(rand(1, 12)),
                ]);
            }
        }
    }
}
