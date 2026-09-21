<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Models\Order;
use App\Models\RepairLog;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\RawJs;
use Illuminate\Support\Collection;

class CompleteOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete_order';
    }

    /**
     * Thiết bị vẫn đang sửa chữa (Pending) thuộc kho của đơn hàng và CHƯA
     * ghi nhận chi phí sửa chữa — cần nhập chi phí trước khi đóng đơn, dù
     * đơn vẫn đóng được ngay cả khi thiết bị chưa sửa xong (thiết bị đã về
     * kho, việc sửa chữa xử lý độc lập với vòng đời đơn hàng).
     *
     * @return Collection<int, RepairLog>
     */
    private static function pendingRepairsMissingCost(Order $record): Collection
    {
        return RepairLog::whereHas('asset', function ($query) use ($record) {
            $query->where('current_warehouse_id', $record->warehouse_id);
        })
            ->where('result_status', RepairResultStatus::Pending)
            ->whereNull('repair_cost')
            ->with('asset.productLine')
            ->get();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('Complete:Order')
            ->label('Hoàn tất Đơn hàng')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Returned)
            ->modalHeading('Hoàn tất & Đóng đơn hàng')
            ->modalDescription(fn (Order $record) => "Xác nhận đơn hàng {$record->order_no} đã thu hồi đầy đủ thiết bị về kho và hoàn tất tất cả các khâu để đóng đơn?")
            ->modalSubmitActionLabel('Xác nhận Hoàn tất')
            ->fillForm(fn (Order $record): array => [
                'repair_costs' => static::pendingRepairsMissingCost($record)
                    ->map(fn (RepairLog $log): array => [
                        'repair_log_id' => $log->id,
                        'asset_label' => $log->asset
                            ? "{$log->asset->serial_no} — ".($log->asset->productLine?->name ?? 'LED')
                            : "Thiết bị #{$log->asset_id}",
                        'repair_cost' => null,
                    ])
                    ->all(),
            ])
            ->before(function (Action $action, Order $record): void {
                $paid = round((float) $record->total_paid, 2);
                $value = round((float) $record->value, 2);

                if ($paid < $value) {
                    $remaining = $value - $paid;
                    $body = "Đơn hàng {$record->order_no} mới thu ".number_format($paid, 0, ',', '.')
                        .' đ / '.number_format($value, 0, ',', '.')
                        .' đ, còn thiếu '.number_format($remaining, 0, ',', '.')
                        .' đ. Vui lòng cập nhật "Tổng tiền đã thu" đủ trước khi hoàn tất đơn hàng.';

                    Notification::make()
                        ->title('Chưa thu đủ tiền đơn hàng')
                        ->body($body)
                        ->danger()
                        ->send();

                    $action->halt();
                }
            })
            ->form([
                Section::make('Thiết bị đang sửa chữa chưa có chi phí')
                    ->description('Đơn hàng vẫn đóng được dù thiết bị chưa sửa xong — nhập chi phí dự kiến để theo dõi công nợ sửa chữa. Chi phí này KHÔNG tính vào doanh thu / hoa hồng của đơn hàng.')
                    ->visible(fn (Get $get): bool => filled($get('repair_costs')))
                    ->schema([
                        Repeater::make('repair_costs')
                            ->label('')
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->table([
                                TableColumn::make('Thiết bị'),
                                TableColumn::make('Chi phí sửa chữa dự kiến'),
                            ])
                            ->schema([
                                Hidden::make('repair_log_id'),
                                TextInput::make('asset_label')
                                    ->label('Thiết bị')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('repair_cost')
                                    ->label('Chi phí sửa chữa dự kiến')
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->suffix(' đ')
                                    ->placeholder('0')
                                    ->live(onBlur: true),
                            ]),
                        Placeholder::make('total_repair_cost')
                            ->label('Tổng chi phí sửa chữa (còn phải thanh toán, không tính vào doanh thu đơn hàng)')
                            ->content(function (Get $get): string {
                                $items = $get('repair_costs') ?? [];
                                $total = collect($items)->sum(function (array $item): float {
                                    // Field bị mask $money($input) nên giá trị live có thể
                                    // còn dấu phẩy ngăn cách hàng nghìn (VD "100,000") — phải
                                    // bỏ dấu phẩy trước khi ép kiểu số, nếu không (float) sẽ
                                    // cắt tại dấu phẩy đầu tiên (100,000 -> 100).
                                    $raw = str_replace(',', '', (string) ($item['repair_cost'] ?? 0));

                                    return (float) $raw;
                                });

                                return number_format($total, 0, ',', '.').' đ';
                            }),
                    ]),
            ])
            ->action(function (Order $record, array $data): void {
                foreach (($data['repair_costs'] ?? []) as $item) {
                    if (! empty($item['repair_log_id']) && filled($item['repair_cost'] ?? null)) {
                        $cost = (float) str_replace(',', '', (string) $item['repair_cost']);

                        RepairLog::where('id', $item['repair_log_id'])
                            ->update(['repair_cost' => $cost]);
                    }
                }

                $record->update(['status' => OrderStatus::Completed]);
                $record->refresh();

                Notification::make()
                    ->title('Đơn hàng đã hoàn tất!')
                    ->body("Đơn hàng {$record->order_no} đã được chuyển sang trạng thái Hoàn tất thành công.")
                    ->success()
                    ->send();
            });
    }
}
