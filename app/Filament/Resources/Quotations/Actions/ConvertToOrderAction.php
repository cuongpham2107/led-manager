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
use Filament\Notifications\Notification;

class ConvertToOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'convert_to_order';
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
            ->schema([
                Select::make('warehouse_id')
                    ->label('Kho xuất hàng thực hiện')
                    ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => Warehouse::where('is_active', true)->first()?->id)
                    ->required(),
            ])
            ->action(function (Quotation $record, array $data): void {
                // Availability guard: block conversion when requested devices exceed stock
                $bom = $record->items
                    ->filter(fn ($item) => $item->product_line_id)
                    ->map(fn ($item) => [
                        'product_line_id' => (int) $item->product_line_id,
                        'quantity' => (int) $item->quantity,
                    ])
                    ->toArray();

                if (! empty($bom)) {
                    $result = app(AvailabilityService::class)->checkBomAvailability(
                        $bom,
                        $record->event_start_date ?? now(),
                        $record->event_end_date ?? now()->addDays(max(1, $record->rental_days ?? 3)),
                        $data['warehouse_id'] ?? null,
                    );

                    if ($result['has_conflicts']) {
                        $messages = collect($result['conflicts'])
                            ->map(fn (array $c): string => "• {$c['product_line_name']}: cần {$c['requested']}, chỉ còn {$c['available']} (thiếu {$c['shortage']})")
                            ->implode("\n");

                        Notification::make()
                            ->title('Không thể chuyển đổi — thiếu thiết bị khả dụng')
                            ->body("Trong khoảng ngày sự kiện, các thiết bị sau không đủ tồn kho:\n{$messages}")
                            ->danger()
                            ->send();

                        return;
                    }
                }

                $orderNo = CodeGeneratorService::generate('ORD', 'orders');

                $warehouseId = $data['warehouse_id'] ?? Warehouse::first()?->id ?? 1;

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
                    'note' => "Được chuyển đổi từ Báo giá {$record->code}.",
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

                Notification::make()
                    ->title('Chuyển đổi đơn hàng thành công!')
                    ->body("Đơn hàng {$orderNo} đã được tạo với đầy đủ danh mục thiết bị BOM.")
                    ->success()
                    ->send();
            });
    }
}
