<?php

namespace App\Filament\Resources\Quotations\Actions;

use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Services\AvailabilityService;
use App\Services\CodeGeneratorService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

class ConvertToOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'convert_to_order';
    }

    /**
     * Extract BOM items with product_line_id and quantity from quotation
     *
     * @return array<int, array{product_line_id: int, quantity: int}>
     */
    protected function getBomItems(Quotation $record): array
    {
        return $record->items
            ->filter(fn ($item) => $item->product_line_id)
            ->map(fn ($item) => [
                'product_line_id' => (int) $item->product_line_id,
                'quantity' => (int) $item->quantity,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Check if a quotation location matches a warehouse
     */
    protected function isLocationMatch(string $location, Warehouse $warehouse): bool
    {
        if (empty($location)) {
            return false;
        }

        $loc = mb_strtolower($location);
        $keywords = match ($warehouse->code) {
            'WH-HN' => ['hà nội', 'ha noi', 'hn'],
            'WH-HCM' => ['hồ chí minh', 'ho chi minh', 'tp.hcm', 'tphcm', 'hcm', 'sài gòn', 'sai gon'],
            'WH-DN' => ['đà nẵng', 'da nang', 'dn'],
            'WH-CT' => ['cần thơ', 'can tho', 'ct'],
            default => [mb_strtolower($warehouse->name)],
        };

        foreach ($keywords as $kw) {
            if (str_contains($loc, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the best default warehouse for this quotation
     */
    protected function getBestWarehouseId(Quotation $record): ?int
    {
        $scopedWh = auth()->user()?->getScopedWarehouseId();
        $warehouses = Warehouse::query()->where('is_active', true)->get();
        if ($warehouses->isEmpty()) {
            return null;
        }

        $bom = $this->getBomItems($record);
        $startDate = $record->event_start_date ?? now();
        $endDate = $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3));
        $service = app(AvailabilityService::class);

        $sufficientWarehouses = [];
        foreach ($warehouses as $w) {
            $check = ! empty($bom)
                ? $service->checkBomAvailability($bom, $startDate, $endDate, $w->id)
                : ['has_conflicts' => false];

            if (! $check['has_conflicts']) {
                $sufficientWarehouses[$w->id] = $w;
            }
        }

        // 1. If user is scoped to a warehouse and it has stock, use it
        if ($scopedWh && isset($sufficientWarehouses[$scopedWh])) {
            return $scopedWh;
        }

        // 2. Prioritize sufficient warehouse matching quotation location
        $location = (string) ($record->location ?? '');
        foreach ($sufficientWarehouses as $wId => $w) {
            if ($this->isLocationMatch($location, $w)) {
                return $wId;
            }
        }

        // 3. First sufficient warehouse
        if (! empty($sufficientWarehouses)) {
            return array_key_first($sufficientWarehouses);
        }

        // 4. If none sufficient, match by location
        foreach ($warehouses as $w) {
            if ($this->isLocationMatch($location, $w)) {
                return $w->id;
            }
        }

        return $scopedWh ?? $warehouses->first()?->id;
    }

    /**
     * Build options with real-time stock evaluation for warehouse select
     *
     * @return array<int, string>
     */
    protected function getWarehouseOptions(Quotation $record): array
    {
        $warehouses = Warehouse::query()->where('is_active', true)->get();
        $bom = $this->getBomItems($record);
        $startDate = $record->event_start_date ?? now();
        $endDate = $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3));
        $service = app(AvailabilityService::class);
        $location = (string) ($record->location ?? '');

        $options = [];
        foreach ($warehouses as $w) {
            $check = ! empty($bom)
                ? $service->checkBomAvailability($bom, $startDate, $endDate, $w->id)
                : ['has_conflicts' => false];

            $isMatch = $this->isLocationMatch($location, $w);
            $locTag = $isMatch ? ' [Gần sự kiện]' : '';

            if (! $check['has_conflicts']) {
                $options[$w->id] = "{$w->name} ({$w->code}) — ✓ Đủ thiết bị khả dụng{$locTag}";
            } else {
                $shortages = collect($check['conflicts'])
                    ->map(fn ($c) => "thiếu {$c['shortage']} {$c['product_line_name']}")
                    ->implode(', ');
                $options[$w->id] = "{$w->name} ({$w->code}) — ⚠️ {$shortages}{$locTag}";
            }
        }

        return $options;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('ConvertToOrder:Quotation')
            ->label('Tạo Đơn hàng')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('Chuyển đổi Báo giá thành Đơn hàng')
            ->modalDescription('Chọn kho xuất hàng thực tế để khởi tạo Đơn hàng và chuyển giao danh mục thiết bị (BOM) cho thủ kho.')
            ->visible(fn (Quotation $record): bool => in_array($record->status, [
                QuotationStatus::Draft,
                QuotationStatus::Sent,
                QuotationStatus::Approved,
            ]) && ! $record->converted_order_id && ! $record->orders()->exists())
            ->form([
                Select::make('warehouse_id')
                    ->label('Kho xuất hàng thực hiện')
                    ->options(fn (Quotation $record): array => $this->getWarehouseOptions($record))
                    ->default(fn (Quotation $record): ?int => $this->getBestWarehouseId($record))
                    ->live()
                    ->helperText(function ($state, Quotation $record): ?string {
                        if (! $state) {
                            return null;
                        }
                        $bom = $this->getBomItems($record);
                        if (empty($bom)) {
                            return null;
                        }
                        $startDate = $record->event_start_date ?? now();
                        $endDate = $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3));
                        $check = app(AvailabilityService::class)->checkBomAvailability($bom, $startDate, $endDate, (int) $state);

                        if (! $check['has_conflicts']) {
                            return '🟢 Kho này có đủ thiết bị đáp ứng toàn bộ danh mục BOM trong khoảng ngày sự kiện.';
                        }

                        $messages = collect($check['conflicts'])
                            ->map(fn (array $c): string => "{$c['product_line_name']} (cần {$c['requested']}, khả dụng {$c['available']})")
                            ->implode(', ');

                        return "⚠️ Kho đang thiếu thiết bị: {$messages}. Bạn có thể chọn kho khác có sẵn hàng hoặc bật tùy chọn bên dưới để vẫn tiếp tục tạo đơn hàng.";
                    })
                    ->required(),

                Toggle::make('force_convert')
                    ->label('Vẫn tạo Đơn hàng (xác nhận sẽ điều chuyển kho nội bộ hoặc thuê ngoài)')
                    ->helperText('Bật tùy chọn này để tiếp tục tạo Đơn hàng ngay cả khi kho được chọn chưa đủ tồn kho khả dụng.')
                    ->default(false)
                    ->visible(function (Get $get, Quotation $record): bool {
                        $whId = $get('warehouse_id');
                        if (! $whId) {
                            return false;
                        }
                        $bom = $this->getBomItems($record);
                        if (empty($bom)) {
                            return false;
                        }
                        $startDate = $record->event_start_date ?? now();
                        $endDate = $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3));
                        $check = app(AvailabilityService::class)->checkBomAvailability($bom, $startDate, $endDate, (int) $whId);

                        return $check['has_conflicts'];
                    }),
            ])
            ->action(function (Quotation $record, array $data): void {
                $bom = $this->getBomItems($record);
                $forceConvert = (bool) ($data['force_convert'] ?? false);

                $result = ! empty($bom)
                    ? app(AvailabilityService::class)->checkBomAvailability(
                        $bom,
                        $record->event_start_date ?? now(),
                        $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3)),
                        $data['warehouse_id'] ?? null,
                    )
                    : ['has_conflicts' => false, 'conflicts' => []];

                if ($result['has_conflicts'] && ! $forceConvert) {
                    $messages = collect($result['conflicts'])
                        ->map(fn (array $c): string => "• {$c['product_line_name']}: cần {$c['requested']}, chỉ còn {$c['available']} (thiếu {$c['shortage']})")
                        ->implode("\n");

                    Notification::make()
                        ->title('Không thể chuyển đổi — thiếu thiết bị khả dụng')
                        ->body("Trong khoảng ngày sự kiện, kho được chọn không đủ tồn kho:\n{$messages}\n\nVui lòng chọn kho khác có đủ hàng hoặc bật 'Vẫn tạo Đơn hàng' nếu sẽ điều chuyển / thuê ngoài.")
                        ->danger()
                        ->send();

                    return;
                }

                $orderNo = CodeGeneratorService::generate('ORD', 'orders');
                $warehouseId = $data['warehouse_id'] ?? Warehouse::first()?->id ?? 1;
                $warehouse = Warehouse::find($warehouseId);

                $note = "Được chuyển đổi từ Báo giá {$record->code}.";
                if ($result['has_conflicts'] && $forceConvert) {
                    $shortageNote = collect($result['conflicts'])
                        ->map(fn (array $c): string => "{$c['product_line_name']} thiếu {$c['shortage']}")
                        ->implode(', ');
                    $note .= "\n[Lưu ý tồn kho]: Kho {$warehouse?->name} thiếu thiết bị ({$shortageNote}), cần điều chuyển nội bộ hoặc thuê ngoài bổ sung.";
                }

                $order = Order::create([
                    'order_no' => $orderNo,
                    'customer_id' => $record->customer_id,
                    'warehouse_id' => $warehouseId,
                    'quotation_id' => $record->id,
                    'product_line_id' => $record->product_line_id,
                    'request_date' => $record->event_start_date ?? now()->toDateString(),
                    'expected_return_date' => $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3))->toDateString(),
                    'area_m2' => $record->screen_area_m2,
                    'event' => $record->event_name,
                    'value' => $record->total_price,
                    'status' => OrderStatus::Draft,
                    'sales_user_id' => $record->sales_user_id,
                    'note' => $note,
                ]);

                // Copy BOM items to Order items
                foreach ($record->items as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_line_id' => $item->product_line_id,
                        'quantity_required' => (int) $item->quantity,
                        'unit_price' => $item->unit_cost,
                        'note' => $item->description,
                    ]);
                }

                $record->update([
                    'status' => QuotationStatus::Converted,
                    'converted_order_id' => $order->id,
                ]);

                if ($result['has_conflicts'] && $forceConvert) {
                    Notification::make()
                        ->title('Đã tạo Đơn hàng (Cần lưu ý tồn kho)!')
                        ->body("Đơn hàng {$orderNo} đã được tạo tại {$warehouse?->name}. Lưu ý: Cần lên kế hoạch điều chuyển kho hoặc thuê ngoài do thiếu thiết bị.")
                        ->warning()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Chuyển đổi đơn hàng thành công!')
                        ->body("Đơn hàng {$orderNo} đã được tạo với đầy đủ danh mục thiết bị BOM.")
                        ->success()
                        ->send();
                }
            });
    }
}
