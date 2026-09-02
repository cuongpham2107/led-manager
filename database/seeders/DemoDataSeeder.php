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
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedCalculationService;
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
        $deviceTypes = DeviceType::all();

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
        $quotations = collect();
        foreach (range(1, 200) as $i) {
            $status = fake()->randomElement($quotationStatuses);
            $start = fake()->dateTimeBetween('-60 days', '+30 days');
            $end = (clone $start)->modify('+'.fake()->numberBetween(1, 14).' days');

            $quo = Quotation::create([
                'code' => 'QUO-'.now()->format('ym').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'customer_id' => fake()->randomElement($customerIds),
                'sales_user_id' => fake()->numberBetween(1, 10),
                'product_line_id' => $productLines->isNotEmpty() ? fake()->randomElement($productLines)->id : null,
                'event_name' => fake()->words(3, true).' '.fake()->randomElement(['Festival', 'Launch', 'Gala', 'Roadshow', 'Conference', 'Concert']),
                'event_start_date' => $start,
                'event_end_date' => $end,
                'screen_width_m' => fake()->randomFloat(2, 3, 20),
                'screen_height_m' => fake()->randomFloat(2, 2, 10),
                'screen_area_m2' => fake()->randomFloat(2, 10, 200),
                'rental_days' => fake()->numberBetween(1, 14),
                'crew_size' => fake()->numberBetween(2, 12),
                'transport_distance_km' => fake()->randomFloat(2, 5, 300),
                'discount_amount' => fake()->randomFloat(2, 0, 3_000_000),
                'status' => $status,
                'lost_reason' => $status === QuotationStatus::Rejected ? fake()->sentence() : null,
                'location' => fake()->city().', '.fake()->country(),
                'estimated_cabinet_qty' => fake()->numberBetween(4, 120),
                'estimated_processor_qty' => fake()->numberBetween(2, 20),
                'estimated_load_kg' => fake()->randomFloat(2, 200, 5000),
                'estimated_power_kw' => fake()->randomFloat(2, 3, 120),
                'note' => fake()->optional()->sentence(),
            ]);

            // ponytail: derive ALL cost fields from one service call so they stay consistent
            $productLine = $quo->product_line_id ? ProductLine::find($quo->product_line_id) : null;
            $customer = Customer::find($quo->customer_id);
            $pricing = app(LedCalculationService::class)->calculatePricing(
                (float) $quo->screen_width_m,
                (float) $quo->screen_height_m,
                $productLine,
                (int) $quo->rental_days,
                (int) $quo->crew_size,
                (float) $quo->transport_distance_km,
                (float) $quo->discount_amount,
                $customer?->type?->value,
            );

            $itemsTotal = 0;
            $itemCount = fake()->numberBetween(1, 4);
            $usedDeviceTypeIds = $deviceTypes->isNotEmpty() ? $deviceTypes->pluck('id')->all() : [];
            foreach (range(1, $itemCount) as $j) {
                if (empty($usedDeviceTypeIds)) {
                    break;
                }

                $deviceTypeId = fake()->randomElement($usedDeviceTypeIds);
                $unitCost = fake()->randomFloat(2, 500_000, 12_000_000);
                $quantity = fake()->numberBetween(2, 20);
                $lineTotal = $unitCost * $quantity;
                $itemsTotal += $lineTotal;
                QuotationItem::create([
                    'quotation_id' => $quo->id,
                    'device_type_id' => $deviceTypeId,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                    'description' => fake()->optional()->sentence(),
                ]);
            }

            // ponytail: items_total is the real equipment_cost; service total is the canonical total_price
            $quo->equipment_cost = $itemsTotal;
            $quo->labour_cost = $pricing['crew_labour'];
            $quo->transport_cost = $pricing['transport'];
            $quo->accessory_cost = $pricing['accessory'];
            $quo->total_cost = $pricing['total_cost'];
            $quo->total_price = $pricing['total_price'];
            $quo->margin_percent = $pricing['margin_percent'];
            $quo->save();

            $quotations->push($quo);
        }

        // 4. Contracts (200) — create BEFORE orders so each order's status
        // can be derived from its contract's status (keeps them in sync).
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
            ContractStatus::Signed->value => [OrderStatus::OutboundCreated, OrderStatus::Dispatched],
            ContractStatus::Active->value => [OrderStatus::Dispatched, OrderStatus::Returned],
            ContractStatus::Completed->value => [OrderStatus::Completed, OrderStatus::Returned],
            ContractStatus::Cancelled->value => [OrderStatus::Cancelled],
        ];
        /** @var Collection<int, Contract> $contracts */
        $contracts = collect();
        foreach (range(1, 200) as $i) {
            $quo = $quotations->random();
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

        // 3. Orders (200) — created AFTER contracts so status can be derived
        $this->command->info('Seeding orders...');
        /** @var Collection<int, Order> $orders */
        $orders = collect();
        $allUsers = User::all()->all();
        foreach ($contracts as $i => $contract) {
            $quo = $quotations->firstWhere('id', $contract->quotation_id);
            $orderStatus = fake()->randomElement($contractToOrderStatusMap[$contract->status->value]);
            $warehouseId = fake()->randomElement($warehouseIds);
            $requestDate = fake()->dateTimeBetween('-30 days', '+20 days');

            $order = Order::create([
                'order_no' => 'ORD-'.now()->format('ym').'-'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'note' => fake()->optional()->sentence(),
                'warehouse_id' => $warehouseId,
                'customer_id' => $contract->customer_id,
                'quotation_id' => $contract->quotation_id,
                'request_date' => $requestDate,
                'expected_return_date' => fake()->optional(0.7)->dateTimeBetween($requestDate, '+20 days'),
                'area_m2' => fake()->randomFloat(2, 10, 180),
                'event' => $quo?->event_name ?? fake()->words(3, true),
                'device_type_id' => $deviceTypes->isNotEmpty() ? fake()->randomElement($deviceTypes)->id : null,
                'value' => $contract->contract_value,
                'deposit_paid' => 0,
                'total_paid' => 0,
                'paid_at' => null,
                'status' => $orderStatus,
                'sales_user_id' => $contract->sales_user_id,
            ]);

            if ($quo) {
                $quo->items->each(function ($qItem) use ($order) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'device_type_id' => $qItem->device_type_id,
                        'quantity_required' => $qItem->quantity,
                        'unit_price' => $qItem->unit_cost,
                        'note' => $qItem->description,
                    ]);
                });
            }

            $this->seedOrderEvents($order, $allUsers);

            $orders->push($order);

            // Link contract back to order, then sync payment snapshot
            $contract->update(['order_id' => $order->id]);

            if (in_array($contract->status, [ContractStatus::Active, ContractStatus::Completed], true)) {
                $paidDeposit = $contract->deposit_amount;
                $paidTotal = $contract->status === ContractStatus::Completed
                    ? $contract->contract_value
                    : $contract->deposit_amount;
                $paidAt = fake()->dateTimeBetween($contract->signed_date->format('Y-m-d H:i:s'), 'now');

                $order->update([
                    'deposit_paid' => $paidDeposit,
                    'total_paid' => $paidTotal,
                    'paid_at' => $paidAt,
                ]);
            }
        }

        // 5. Checkin Batches (25)
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
                'device_type_id' => $deviceTypes->isNotEmpty() ? fake()->randomElement($deviceTypes)->id : null,
                'quantity' => fake()->numberBetween(5, 40),
                'production_note' => fake()->optional()->sentence(),
                'status' => $status,
                'created_by' => User::inRandomOrder()->first()?->id,
                'completed_at' => $status === BatchStatus::Completed ? fake()->dateTimeBetween('-10 days', 'now') : null,
            ]);

            $itemCount = fake()->numberBetween(3, min(40, (int) $batch->quantity));
            foreach (range(1, $itemCount) as $j) {
                $asset = Asset::factory()->create([
                    'product_line_id' => $batch->product_line_id,
                    'device_type_id' => $batch->device_type_id,
                    'current_status' => AssetStatus::Ready,
                    'current_warehouse_id' => $batch->warehouse_id,
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

        // 6. Checkout Batches (200)
        $this->command->info('Seeding check-out batches...');
        foreach (range(1, 200) as $i) {
            $order = $orders->random();
            $status = fake()->randomElement([BatchStatus::Pending, BatchStatus::InProgress, BatchStatus::Completed, BatchStatus::Cancelled]);
            $expectedReturn = fake()->dateTimeBetween('+1 days', '+20 days');
            $batch = CheckoutBatch::create([
                'code' => 'OUT-'.now()->format('ym').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $order->warehouse_id,
                'required_area_m2' => fake()->randomFloat(2, 10, 120),
                'device_type_id' => $deviceTypes->isNotEmpty() ? fake()->randomElement($deviceTypes)->id : null,
                'expected_return_date' => $expectedReturn,
                'status' => $status,
                'created_by' => User::inRandomOrder()->first()?->id,
                'dispatched_at' => in_array($status, [BatchStatus::Completed, BatchStatus::Cancelled], true) ? fake()->dateTimeBetween('-10 days', 'now') : null,
            ]);

            $assetQty = fake()->numberBetween(3, 15);
            $assets = Asset::factory()->count($assetQty)->create([
                'product_line_id' => $batch->device_type_id ? null : ($productLines->isNotEmpty() ? fake()->randomElement($productLines)->id : null),
                'device_type_id' => $batch->device_type_id,
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => $batch->warehouse_id,
            ]);

            foreach ($assets as $asset) {
                CheckoutBatchItem::create([
                    'checkout_batch_id' => $batch->id,
                    'asset_id' => $asset->id,
                    'is_dispatched' => in_array($status, [BatchStatus::Completed, BatchStatus::Cancelled], true),
                    'dispatched_by' => User::inRandomOrder()->first()?->id,
                    'dispatched_at' => in_array($status, [BatchStatus::Completed, BatchStatus::Cancelled], true) ? fake()->dateTimeBetween('-8 days', 'now') : null,
                    'checked_brightness' => fake()->boolean(85),
                    'checked_dead_pixels' => fake()->boolean(90),
                    'checked_color' => fake()->boolean(88),
                    'checked_power' => fake()->boolean(92),
                    'checklist_note' => fake()->optional()->sentence(),
                ]);

                if (in_array($status, [BatchStatus::Completed, BatchStatus::Cancelled], true)) {
                    $asset->update([
                        'current_status' => AssetStatus::InEvent,
                        'current_warehouse_id' => null,
                    ]);
                }
            }
        }

        // 7. Return Batches (25)
        $this->command->info('Seeding return batches...');
        $checkoutBatches = CheckoutBatch::all();
        $returnStatuses = [ReturnBatchStatus::Pending, ReturnBatchStatus::InProgress, ReturnBatchStatus::Completed];
        foreach (range(1, 25) as $i) {
            $checkout = $checkoutBatches->isNotEmpty() ? $checkoutBatches->random() : null;
            $status = fake()->randomElement($returnStatuses);
            $returnDate = fake()->dateTimeBetween('-5 days', '+10 days');

            $returnBatch = ReturnBatch::create([
                'code' => 'RET-'.now()->format('ym').'-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
                'checkout_batch_id' => $checkout?->id,
                'return_date' => $returnDate,
                'note' => fake()->optional()->sentence(),
                'status' => $status,
                'created_by' => User::inRandomOrder()->first()?->id,
                'completed_at' => $status === ReturnBatchStatus::Completed ? fake()->dateTimeBetween('-3 days', 'now') : null,
            ]);

            $itemCount = fake()->numberBetween(2, 12);
            $assets = Asset::factory()->count($itemCount)->create([
                'product_line_id' => $productLines->isNotEmpty() ? fake()->randomElement($productLines)->id : null,
                'device_type_id' => $deviceTypes->isNotEmpty() ? fake()->randomElement($deviceTypes)->id : null,
                'current_status' => AssetStatus::Ready,
                'current_warehouse_id' => fake()->randomElement($warehouseIds),
            ]);

            foreach ($assets as $asset) {
                $grade = fake()->randomElement([ReturnGrade::Normal, ReturnGrade::Normal, ReturnGrade::Normal, ReturnGrade::Damaged]);
                ReturnBatchItem::create([
                    'return_batch_id' => $returnBatch->id,
                    'asset_id' => $asset->id,
                    'checkout_batch_item_id' => null,
                    'grade' => $grade,
                    'grade_note' => $grade === ReturnGrade::Damaged ? fake()->sentence() : null,
                    'is_received' => true,
                    'received_by' => User::inRandomOrder()->first()?->id,
                    'received_at' => fake()->dateTimeBetween('-30 days', 'now'),
                ]);

                if ($status === ReturnBatchStatus::Completed) {
                    $newStatus = $grade === ReturnGrade::Damaged ? AssetStatus::Repairing : AssetStatus::Ready;
                    $asset->update(['current_status' => $newStatus]);
                }
            }
        }

        // 8. Repair Logs (200)
        $this->command->info('Seeding repair logs...');
        foreach (range(1, 200) as $i) {
            $asset = Asset::inRandomOrder()->first() ?? Asset::factory()->create();
            $startDate = fake()->dateTimeBetween('-60 days', '-1 day');
            $endDate = fake()->boolean(80) ? fake()->dateTimeBetween($startDate->format('Y-m-d H:i:s'), 'now') : null;
            RepairLog::create([
                'asset_id' => $asset->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'repair_note' => fake()->sentence(),
                'result_status' => $endDate
                    ? fake()->randomElement([RepairResultStatus::Fixed, RepairResultStatus::Disposed])
                    : RepairResultStatus::Pending,
                'repair_cost' => fake()->optional(0.6)->randomFloat(2, 200_000, 8_000_000),
                'created_by' => User::inRandomOrder()->first()?->id,
            ]);

            if ($endDate && fake()->boolean(40)) {
                AssetStatusLog::create([
                    'asset_id' => $asset->id,
                    'from_status' => AssetStatus::Repairing,
                    'to_status' => fake()->randomElement([AssetStatus::Ready, AssetStatus::Disposed]),
                    'from_warehouse_id' => $warehouses->random()->id,
                    'to_warehouse_id' => $warehouses->random()->id,
                    'source_type' => RepairLog::class,
                    'source_id' => $i,
                    'changed_by' => User::inRandomOrder()->first()?->id,
                    'note' => fake()->optional()->sentence(),
                ]);
            }
        }

        $this->command->info('Demo data seeded successfully.');
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
