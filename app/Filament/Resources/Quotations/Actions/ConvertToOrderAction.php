<?php

namespace App\Filament\Resources\Quotations\Actions;

use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quotation;
use App\Models\Warehouse;
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
            ->label('Tạo Đơn hàng')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->modalHeading('Chuyển đổi Báo giá thành Đơn hàng')
            ->modalDescription('Chọn kho xuất hàng thực tế để khởi tạo Đơn hàng và chuyển giao danh mục thiết bị (BOM) cho thủ kho.')
            ->visible(fn (Quotation $record): bool => in_array($record->status, [
                QuotationStatus::Draft,
                QuotationStatus::Sent,
                QuotationStatus::Approved,
            ]) && ! $record->converted_order_id)
            ->form([
                Select::make('warehouse_id')
                    ->label('Kho xuất hàng thực hiện')
                    ->options(Warehouse::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => Warehouse::where('is_active', true)->first()?->id)
                    ->required(),
            ])
            ->action(function (Quotation $record, array $data): void {
                $orderNo = CodeGeneratorService::generate('ORD', 'orders');

                $warehouseId = $data['warehouse_id'] ?? Warehouse::first()?->id ?? 1;

                $order = Order::create([
                    'order_no' => $orderNo,
                    'customer_id' => $record->customer_id,
                    'warehouse_id' => $warehouseId,
                    'quotation_id' => $record->id,
                    'device_type_id' => $record->device_type_id,
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
                    if ($item->device_type_id) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'device_type_id' => $item->device_type_id,
                            'quantity_required' => (int) $item->quantity,
                            'unit_price' => $item->unit_cost,
                            'note' => $item->description,
                        ]);
                    }
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
