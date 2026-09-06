<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\AssignmentRole;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Enums\ContractStatus;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\EventAssignment;
use App\Models\EventMilestone;
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
use App\Models\WarehouseLocation;
use App\Services\LedCalculationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn('Seeding demo data...');

        $warehouses = Warehouse::all();
        if ($warehouses->isEmpty()) {
            $this->command->error('No warehouses found. Please seed base data first.');

            return;
        }

        if (Customer::count() === 0) {
            $this->command->error('No customers found. Please seed base data first.');

            return;
        }

        if (User::count() === 0) {
            $this->command->error('No users found. Please seed base data first.');

            return;
        }

        $warehouseIds = $warehouses->pluck('id')->all();
        $productLines = ProductLine::all();

        // 1. Customers (200)
        $this->command->info('Seeding customers...');
        $createdCustomers = Customer::factory()->count(200)->create();
        if ($createdCustomers->isNotEmpty()) {
            $customers = $createdCustomers;
            $customerIds = $customers->pluck('id')->all();
        }

        // 2. Quotations (200)
        $this->command->info('Seeding quotations...');
        $quotationStatuses = [
            QuotationStatus::Draft,
            QuotationStatus::Sent,
            QuotationStatus::Approved,
            QuotationStatus::Converted,
            QuotationStatus::Rejected,
            QuotationStatus::Expired,
        ];
        /** @var Collection<int, Quotation> $quotations */
        $service = app(LedCalculationService::class);
        $quotations = collect();
        foreach (range(1, 200) as $i) {
            $status = fake()->randomElement($quotationStatuses);
            $start = fake()->dateTimeBetween('-60 days', '+30 days');
            $end = (clone $start)->modify('+'.fake()->numberBetween(1, 14).' days');
            $width = fake()->randomFloat(2, 3, 8);
            $height = fake()->randomFloat(2, 2, 4);
            $productLine = $productLines->isNotEmpty() ? fake()->randomElement($productLines) : null;
            $customerId = fake()->randomElement($customerIds);
            $customer = Customer::find($customerId);
            $rentalDays = fake()->numberBetween(1, 14);
            $crewSize = fake()->numberBetween(2, 12);
            $transportDist = fake()->randomFloat(2, 5, 300);
            $discount = fake()->randomFloat(2, 0, 3_000_000);

            $config = $service->deriveConfiguration($width, $height, $productLine);
            $rates = $service->resolvePricing($productLine, $rentalDays, $customer?->type?->value);
            $bom = $service->generateBom($width, $height, $productLine, $rentalDays, $customer?->type?->value);

            $itemsTotal = 0;
            $itemsData = [];
            foreach ($bom as $item) {
                $itemsData[] = [
                    'product_line_id' => $item['product_line_id'],
                    'quantity' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $item['line_total'],
                    'description' => $item['item'],
                ];
                $itemsTotal += $item['line_total'];
            }

            if (fake()->boolean(35)) {
                $processorDailyRate = fake()->randomElement([1500000, 2000000, 2500000]);
                $processorTotal = $processorDailyRate * $rentalDays;
                $itemsData[] = [
                    'product_line_id' => null,
                    'quantity' => 1,
                    'unit_cost' => $processorTotal,
                    'line_total' => $processorTotal,
                    'description' => 'Bộ xử lý hình ảnh LED Processor 4K ('.number_format($processorDailyRate, 0, ',', '.').' đ/ngày × '.$rentalDays.' ngày)',
                ];
                $itemsTotal += $processorTotal;
            }

            $equipmentCost = $itemsTotal;
            $labourCost = $crewSize * $rates['crew_rate'] * $rentalDays;
            $transportCost = ($transportDist * 2) * $rates['transport_rate'];
            $accessoryCost = $config['wall_area'] * $rates['accessory_rate'];
            $totalCost = ($equipmentCost * 0.40) + ($labourCost * 0.70) + ($transportCost * 0.60) + $accessoryCost;
            $totalPrice = max(0, ($equipmentCost + $labourCost + $transportCost + $accessoryCost) - $discount);
            $marginPercent = $totalPrice > 0 ? round((($totalPrice - $totalCost) / $totalPrice) * 100, 1) : 0.0;

            $quo = Quotation::create([
                'code' => 'QUO-'.now()->format('ym').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'customer_id' => $customerId,
                'sales_user_id' => fake()->numberBetween(1, 10),
                'product_line_id' => $productLine?->id,
                'event_name' => fake()->words(3, true).' '.fake()->randomElement(['Festival', 'Launch', 'Gala', 'Roadshow', 'Conference', 'Concert']),
                'event_start_date' => $start,
                'event_end_date' => $end,
                'screen_width_m' => $width,
                'screen_height_m' => $height,
                'screen_area_m2' => $config['wall_area'],
                'rental_days' => $rentalDays,
                'crew_size' => $crewSize,
                'transport_distance_km' => $transportDist,
                'crew_rate' => $rates['crew_rate'],
                'transport_rate' => $rates['transport_rate'],
                'equipment_cost' => $equipmentCost,
                'labour_cost' => $labourCost,
                'transport_cost' => $transportCost,
                'accessory_cost' => $accessoryCost,
                'total_cost' => $totalCost,
                'discount_amount' => $discount,
                'total_price' => $totalPrice,
                'margin_percent' => $marginPercent,
                'status' => $status,
                'lost_reason' => $status === QuotationStatus::Rejected ? fake()->sentence() : null,
                'location' => fake()->city().', '.fake()->country(),
                'estimated_cabinet_qty' => $config['cabinets_qty'],
                'estimated_processor_qty' => count($itemsData) > 1 ? 2 : 1,
                'estimated_load_kg' => $config['load_kg'],
                'estimated_power_kw' => $config['peak_power_kw'],
                'note' => fake()->optional()->sentence(),
            ]);

            foreach ($itemsData as $it) {
                QuotationItem::create(array_merge(['quotation_id' => $quo->id], $it));
            }

            $quotations->push($quo);
        }

        // 4. Contracts (200) — mapped 1:1 with quotations
        $this->command->info('Seeding contracts...');
        $contractStatuses = [
            ContractStatus::Draft,
            ContractStatus::Signed,
            ContractStatus::Active,
            ContractStatus::Completed,
            ContractStatus::Cancelled,
        ];
        /** @var array<string, list<OrderStatus>> $contractToOrderStatusMap */
        $contractToOrderStatusMap = [
            ContractStatus::Draft->value => [OrderStatus::Draft],
            ContractStatus::Signed->value => [OrderStatus::OutboundCreated],
            ContractStatus::Active->value => [OrderStatus::Dispatched, OrderStatus::Returned],
            ContractStatus::Completed->value => [OrderStatus::Completed],
            ContractStatus::Cancelled->value => [OrderStatus::Cancelled],
        ];
        /** @var Collection<int, Contract> $contracts */
        $contracts = collect();
        foreach (range(1, 200) as $i) {
            $quo = $quotations[$i - 1] ?? $quotations->random();
            $status = fake()->randomElement($contractStatuses);
            $contractValue = (float) $quo->total_price;
            $depositPercent = fake()->randomElement([30, 50, 60, 70]);
            $depositAmount = (int) round($contractValue * ($depositPercent / 100));
            $signed = fake()->dateTimeBetween('-90 days', 'now');
            $start = (clone $signed)->modify('+'.fake()->numberBetween(0, 10).' days');
            $end = (clone $start)->modify('+'.fake()->numberBetween(1, 14).' days');

            $contract = Contract::create([
                'code' => 'HD-'.now()->format('ym').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'quotation_id' => $quo->id,
                'customer_id' => $quo->customer_id,
                'order_id' => null, // set after order created
                'title' => 'Hợp đồng '.$quo->event_name.' #'.$i,
                'signed_date' => $signed,
                'start_date' => $start,
                'end_date' => $end,
                'contract_value' => $contractValue,
                'deposit_percent' => $depositPercent,
                'deposit_amount' => $depositAmount,
                'status' => $status,
                'terms' => fake()->optional()->paragraph(),
                'note' => fake()->optional()->sentence(),
                'sales_user_id' => $quo->sales_user_id,
                'created_by' => User::inRandomOrder()->first()?->id,
            ]);

            $contracts->push($contract);
        }

        // 3. Orders (200) — created with precise lifecycle alignment
        $this->command->info('Seeding orders...');
        /** @var Collection<int, Order> $orders */
        $orders = collect();
        $allUsers = User::all()->all();
        foreach ($contracts as $i => $contract) {
            $quo = $quotations->firstWhere('id', $contract->quotation_id);
            $orderStatus = fake()->randomElement($contractToOrderStatusMap[$contract->status->value]);
            $warehouseId = fake()->randomElement($warehouseIds);

            // Lifecycle-consistent dates
            if ($orderStatus === OrderStatus::Draft) {
                $requestDate = fake()->dateTimeBetween('+3 days', '+25 days');
                $expectedReturnDate = (clone $requestDate)->modify('+'.fake()->numberBetween(2, 6).' days');
                $paidDeposit = 0;
                $paidTotal = 0;
                $paidAt = null;
            } elseif ($orderStatus === OrderStatus::OutboundCreated) {
                $requestDate = fake()->dateTimeBetween('+1 days', '+8 days');
                $expectedReturnDate = (clone $requestDate)->modify('+'.fake()->numberBetween(2, 6).' days');
                $paidDeposit = $contract->deposit_amount;
                $paidTotal = $contract->deposit_amount;
                $paidAt = fake()->dateTimeBetween('-5 days', 'now');
            } elseif ($orderStatus === OrderStatus::Dispatched) {
                $requestDate = fake()->dateTimeBetween('-15 days', 'now');
                $expectedReturnDate = (clone $requestDate)->modify('+'.fake()->numberBetween(2, 5).' days');
                $paidDeposit = $contract->deposit_amount;
                $paidTotal = $contract->deposit_amount;
                $paidAt = fake()->dateTimeBetween('-20 days', '-15 days');
            } elseif ($orderStatus === OrderStatus::Returned) {
                $requestDate = fake()->dateTimeBetween('-25 days', '-6 days');
                $expectedReturnDate = (clone $requestDate)->modify('+'.fake()->numberBetween(2, 5).' days');
                $paidDeposit = $contract->deposit_amount;
                $paidTotal = fake()->boolean(60) ? $contract->contract_value : $contract->deposit_amount;
                $paidAt = fake()->dateTimeBetween('-25 days', '-4 days');
            } elseif ($orderStatus === OrderStatus::Completed) {
                $requestDate = fake()->dateTimeBetween('-60 days', '-10 days');
                $expectedReturnDate = (clone $requestDate)->modify('+'.fake()->numberBetween(2, 5).' days');
                $paidDeposit = $contract->deposit_amount;
                $paidTotal = $contract->contract_value;
                $paidAt = fake()->dateTimeBetween('-60 days', '-8 days');
            } else { // Cancelled
                $requestDate = fake()->dateTimeBetween('-20 days', '+20 days');
                $expectedReturnDate = (clone $requestDate)->modify('+'.fake()->numberBetween(2, 5).' days');
                $paidDeposit = 0;
                $paidTotal = 0;
                $paidAt = null;
            }

            $order = Order::create([
                'order_no' => 'ORD-'.now()->format('ym').'-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'note' => fake()->optional()->sentence(),
                'warehouse_id' => $warehouseId,
                'customer_id' => $contract->customer_id,
                'quotation_id' => $contract->quotation_id,
                'request_date' => $requestDate,
                'expected_return_date' => $expectedReturnDate,
                'area_m2' => $quo?->screen_area_m2 ?? fake()->randomFloat(2, 10, 180),
                'event' => $quo?->event_name ?? fake()->words(3, true),
                'product_line_id' => $quo?->product_line_id ?? ($productLines->isNotEmpty() ? fake()->randomElement($productLines)->id : null),
                'value' => $contract->contract_value,
                'deposit_paid' => $paidDeposit,
                'total_paid' => $paidTotal,
                'paid_at' => $paidAt,
                'status' => $orderStatus,
                'sales_user_id' => $contract->sales_user_id,
            ]);

            if ($quo) {
                $quo->items->each(function ($qItem) use ($order) {
                    $whStock = $qItem->product_line_id ? Asset::where('product_line_id', $qItem->product_line_id)->where('current_warehouse_id', $order->warehouse_id)->count() : 0;
                    $cappedQty = $whStock > 0 ? min((int) $qItem->quantity, max(10, (int) round($whStock * 0.5))) : (int) $qItem->quantity;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_line_id' => $qItem->product_line_id,
                        'quantity_required' => max(1, $cappedQty),
                        'unit_price' => $qItem->unit_cost,
                        'note' => $qItem->description,
                    ]);
                });
            }

            if (! in_array($orderStatus, [OrderStatus::Draft, OrderStatus::Cancelled], true)) {
                $this->seedOrderEvents($order, $allUsers);
            }

            $orders->push($order);

            // Link contract back to order ONLY if order is not draft
            if ($orderStatus !== OrderStatus::Draft) {
                $contract->update(['order_id' => $order->id]);
            }
        }

        // 5. Checkin Batches (25) — New inventory batches
        $this->command->info('Seeding check-in batches...');
        $checkinStatuses = [BatchStatus::Pending, BatchStatus::InProgress, BatchStatus::Completed, BatchStatus::Cancelled];
        foreach (range(1, 25) as $i) {
            $status = fake()->randomElement($checkinStatuses);
            $expectedDate = fake()->dateTimeBetween('-20 days', '+10 days');
            $batch = CheckinBatch::create([
                'code' => 'IN-'.now()->format('ym').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'warehouse_id' => fake()->randomElement($warehouseIds),
                'note' => fake()->optional()->sentence(),
                'expected_date' => $expectedDate,
                'batch_type' => fake()->randomElement([CheckinBatchType::Production, CheckinBatchType::Purchase, CheckinBatchType::Transfer]),
                'product_line_id' => $productLines->isNotEmpty() ? fake()->randomElement($productLines)->id : null,
                'quantity' => fake()->numberBetween(5, 40),
                'production_note' => fake()->optional()->sentence(),
                'status' => $status,
                'created_by' => User::inRandomOrder()->first()?->id,
                'completed_at' => $status === BatchStatus::Completed ? fake()->dateTimeBetween('-10 days', 'now') : null,
            ]);

            $itemCount = fake()->numberBetween(3, min(40, (int) $batch->quantity));
            $batchLocationId = WarehouseLocation::where('warehouse_id', $batch->warehouse_id)->inRandomOrder()->value('id');
            foreach (range(1, $itemCount) as $j) {
                $asset = Asset::factory()->create([
                    'product_line_id' => $batch->product_line_id,
                    'current_status' => AssetStatus::Ready,
                    'current_warehouse_id' => $batch->warehouse_id,
                    'warehouse_location_id' => $batchLocationId,
                    'operating_hours' => 0,
                    'rental_count' => 0,
                ]);

                CheckinBatchItem::create([
                    'checkin_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'condition' => fake()->randomElement(['ok', 'ok', 'ok', 'fault']),
                    'condition_note' => fake()->optional()->sentence(),
                    'is_received' => true,
                    'received_by' => User::inRandomOrder()->first()?->id,
                    'received_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);
            }
        }

        // 6. Checkout Batches & Return Batches with full business logic linkage
        $this->command->info('Seeding checkout and return batches with real inventory linkage...');
        $checkoutIdx = 1;
        $returnIdx = 1;

        foreach ($orders as $order) {
            // Draft and Cancelled orders do not have checkout batches yet!
            if (in_array($order->status, [OrderStatus::Draft, OrderStatus::Cancelled], true)) {
                continue;
            }

            $isPreparing = $order->status === OrderStatus::OutboundCreated;
            $isDispatchedNow = $order->status === OrderStatus::Dispatched;
            $isReturnedOrDone = in_array($order->status, [OrderStatus::Returned, OrderStatus::Completed], true);

            $batchStatus = $isPreparing ? BatchStatus::InProgress : BatchStatus::Completed;
            $dispatchedAt = $isPreparing ? null : ($order->request_date ?? now()->subDays(3));

            $checkoutBatch = CheckoutBatch::create([
                'code' => 'OUT-'.now()->format('ym').'-'.str_pad((string) $checkoutIdx++, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $order->warehouse_id,
                'required_area_m2' => $order->area_m2 ?: fake()->randomFloat(2, 10, 80),
                'expected_return_date' => $order->expected_return_date,
                'status' => $batchStatus,
                'created_by' => User::inRandomOrder()->first()?->id,
                'dispatched_at' => $dispatchedAt,
            ]);

            // Pick 4 to 12 realistic assets from the order's warehouse
            $assetQty = fake()->numberBetween(4, 10);
            $candidateAssets = Asset::where('current_warehouse_id', $order->warehouse_id)
                ->where('current_status', AssetStatus::Ready)
                ->when($order->product_line_id, fn ($q, $plId) => $q->where('product_line_id', $plId))
                ->inRandomOrder()
                ->take($assetQty)
                ->get();

            if ($candidateAssets->count() < $assetQty) {
                $needed = $assetQty - $candidateAssets->count();
                $more = Asset::where('current_warehouse_id', $order->warehouse_id)
                    ->where('current_status', AssetStatus::Ready)
                    ->whereNotIn('id', $candidateAssets->pluck('id'))
                    ->inRandomOrder()
                    ->take($needed)
                    ->get();
                $candidateAssets = $candidateAssets->merge($more);
            }

            if ($candidateAssets->count() < $assetQty) {
                $needed = $assetQty - $candidateAssets->count();
                $batchLocationId = WarehouseLocation::where('warehouse_id', $order->warehouse_id)->inRandomOrder()->value('id');
                $newStock = Asset::factory()->count($needed)->create([
                    'product_line_id' => $order->product_line_id,
                    'current_status' => AssetStatus::Ready,
                    'current_warehouse_id' => $order->warehouse_id,
                    'warehouse_location_id' => $batchLocationId,
                ]);
                $candidateAssets = $candidateAssets->merge($newStock);
            }

            $checkoutItems = collect();
            foreach ($candidateAssets as $cAsset) {
                $cItem = CheckoutBatchItem::create([
                    'checkout_batch_id' => $checkoutBatch->id,
                    'asset_id' => $cAsset->id,
                    'is_dispatched' => ! $isPreparing,
                    'dispatched_by' => ! $isPreparing ? User::inRandomOrder()->first()?->id : null,
                    'dispatched_at' => $dispatchedAt,
                    'note' => fake()->optional(0.3)->sentence(),
                ]);
                $checkoutItems->push($cItem);

                // If currently live in an event, mark asset InEvent
                if ($isDispatchedNow) {
                    $cAsset->update([
                        'current_status' => AssetStatus::InEvent,
                        'warehouse_location_id' => null,
                    ]);
                }
            }

            // If order is Returned or Completed: create ReturnBatch and link return items!
            if ($isReturnedOrDone && $checkoutItems->isNotEmpty()) {
                $returnDate = $order->expected_return_date ?? now()->subDays(2);
                $returnBatch = ReturnBatch::create([
                    'code' => 'RET-'.now()->format('ym').'-'.str_pad((string) $returnIdx++, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                    'checkout_batch_id' => $checkoutBatch->id,
                    'return_date' => $returnDate,
                    'note' => 'Thu hồi hoàn trả và thẩm định kỹ thuật sau sự kiện: '.$order->event,
                    'status' => ReturnBatchStatus::Completed,
                    'created_by' => User::inRandomOrder()->first()?->id,
                    'completed_at' => $returnDate,
                ]);

                foreach ($checkoutItems as $cbItem) {
                    $asset = $cbItem->asset;
                    $isDamaged = fake()->boolean(8);
                    $grade = $isDamaged ? ReturnGrade::Damaged : ReturnGrade::Normal;
                    $gradeNote = $isDamaged
                        ? fake()->randomElement([
                            'Cabinet bị chết 2 bóng LED module P2.6',
                            'Lỗi nguồn thứ cấp, chập IC giải mã',
                            'Móp méo nhẹ khung nhôm do va chạm vận chuyển',
                            'Hỏng cổng cắm tín hiệu HDMI/RJ45',
                        ])
                        : 'Cabinet hoạt động bình thường, module sáng đều, khung nhôm nguyên vẹn';

                    ReturnBatchItem::create([
                        'return_batch_id' => $returnBatch->id,
                        'asset_id' => $cbItem->asset_id,
                        'checkout_batch_item_id' => $cbItem->id,
                        'grade' => $grade,
                        'grade_note' => $gradeNote,
                        'is_received' => true,
                        'received_by' => User::inRandomOrder()->first()?->id,
                        'received_at' => $returnDate,
                    ]);

                    if ($asset) {
                        if ($isDamaged) {
                            $asset->update(['current_status' => AssetStatus::Repairing]);

                            RepairLog::create([
                                'asset_id' => $asset->id,
                                'start_date' => $returnDate,
                                'end_date' => fake()->boolean(60) ? (clone Carbon::parse($returnDate))->modify('+2 days') : null,
                                'repair_note' => 'Bảo dưỡng sửa chữa sau sự kiện: '.$gradeNote,
                                'result_status' => fake()->boolean(60) ? RepairResultStatus::Fixed : RepairResultStatus::Pending,
                                'repair_cost' => fake()->randomFloat(2, 200_000, 2_500_000),
                                'created_by' => User::inRandomOrder()->first()?->id,
                            ]);
                        } else {
                            $locId = WarehouseLocation::where('warehouse_id', $order->warehouse_id)->inRandomOrder()->value('id');
                            $asset->update([
                                'current_status' => AssetStatus::Ready,
                                'current_warehouse_id' => $order->warehouse_id,
                                'warehouse_location_id' => $locId,
                            ]);
                        }
                    }
                }
            }
        }

        // 8. Supplementary Periodic Maintenance & Inspection Logs
        $this->command->info('Seeding periodic maintenance logs...');
        $sampleMaintenanceAssets = Asset::where('current_status', '!=', AssetStatus::Disposed)
            ->inRandomOrder()
            ->take(50)
            ->get();

        foreach ($sampleMaintenanceAssets as $mAsset) {
            $startDate = fake()->dateTimeBetween('-75 days', '-5 days');
            $isFixed = fake()->boolean(85);
            $endDate = $isFixed ? (clone Carbon::parse($startDate))->modify('+'.fake()->numberBetween(1, 4).' days') : null;

            RepairLog::create([
                'asset_id' => $mAsset->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'repair_note' => fake()->randomElement([
                    'Bảo dưỡng định kỳ sau 10 sự kiện: vệ sinh quạt hút, cân chỉnh màu sắc module',
                    'Hiệu chuẩn độ sáng đồng đều và kiểm tra điện áp bảng mạch',
                    'Kiểm tra lại khóa cabinet và bôi trơn bản lề chốt nối',
                    'Cập nhật firmware card nhận Novastar và kiểm tra test pattern',
                ]),
                'result_status' => $isFixed ? RepairResultStatus::Fixed : RepairResultStatus::Pending,
                'repair_cost' => fake()->randomFloat(2, 100_000, 1_500_000),
                'created_by' => User::inRandomOrder()->first()?->id,
            ]);
        }

        // 9. Synchronize Asset Operating Hours & Rental Counts
        $this->command->info('Synchronizing operating hours and rental counts for all assets...');
        foreach (Asset::all() as $asset) {
            $checkoutCount = $asset->checkoutBatchItems()->where('is_dispatched', true)->count();

            if ($asset->id === 1 || $asset->serial_no === 'GE-R15-000201') {
                $totalRentals = 21;
                $operatingHours = 890;
            } else {
                $monthsOld = max(1, (int) Carbon::parse($asset->manufactured_date ?? $asset->purchase_date ?? now()->subMonths(10))->diffInMonths(now()));
                $baselineRentals = $asset->id <= 35 ? fake()->numberBetween(8, 22) : ($monthsOld > 12 ? fake()->numberBetween(2, 9) : 0);
                $totalRentals = $checkoutCount + $baselineRentals;
                $operatingHours = $totalRentals > 0 ? ($totalRentals * fake()->numberBetween(35, 46) + fake()->numberBetween(5, 20)) : 0;
            }

            $asset->updateQuietly([
                'rental_count' => $totalRentals,
                'operating_hours' => $operatingHours,
            ]);
        }

        $this->command->info('Demo data seeded successfully with full logical integrity.');
    }

    private function seedOrderEvents(Order $order, array $users): void
    {
        $types = [
            MilestoneType::Delivery,
            MilestoneType::Setup,
            MilestoneType::Testing,
            MilestoneType::EventStart,
            MilestoneType::EventEnd,
            MilestoneType::Teardown,
            MilestoneType::ReturnToWarehouse,
        ];

        $start = $order->request_date ?? now();
        foreach ($types as $index => $type) {
            $plannedAt = (clone $start)->modify('+'.($index * 2).' days')->setTime(8, 0);
            EventMilestone::create([
                'order_id' => $order->id,
                'type' => $type,
                'planned_at' => $plannedAt,
                'actual_at' => $plannedAt->lessThan(now()) ? fake()->optional(0.8)->dateTimeBetween((string) $plannedAt, (string) (clone $plannedAt)->modify('+1 day')) : null,
                'status' => fake()->randomElement([
                    MilestoneStatus::Pending,
                    MilestoneStatus::InProgress,
                    MilestoneStatus::Completed,
                    MilestoneStatus::Skipped,
                ]),
                'note' => fake()->optional()->sentence(),
            ]);
        }

        $crewCount = fake()->numberBetween(2, 5);
        $roles = [AssignmentRole::Lead, AssignmentRole::Technician, AssignmentRole::Technician, AssignmentRole::Driver, AssignmentRole::Helper];
        $used = [];
        foreach (range(1, $crewCount) as $j) {
            $role = $roles[$j - 1] ?? AssignmentRole::Helper;
            $roleKey = $role->value ?? $role;

            do {
                $userId = fake()->randomElement($users)->id;
            } while (isset($used[$roleKey]) && $used[$roleKey] === $userId);

            $used[$roleKey] = $userId;

            EventAssignment::create([
                'order_id' => $order->id,
                'user_id' => $userId,
                'role' => $role,
                'start_date' => $start,
                'end_date' => (clone $start)->modify('+'.fake()->numberBetween(2, 8).' days'),
                'note' => fake()->optional()->sentence(),
            ]);
        }
    }
}
