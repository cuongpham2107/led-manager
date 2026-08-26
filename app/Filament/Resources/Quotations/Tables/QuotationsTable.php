<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Services\QuotationPdfService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã Báo Giá')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('event_name')
                    ->label('Sự kiện')
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('productLine.name')
                    ->label('Dòng LED')
                    ->badge()
                    ->color('primary')
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('screen_area_m2')
                    ->label('Diện tích')
                    ->suffix(' m²')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('rental_days')
                    ->label('Số ngày')
                    ->suffix(' ngày')
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Tổng giá trị')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('salesUser.name')
                    ->label('Sales')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(QuotationStatus::class),
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
                SelectFilter::make('product_line_id')
                    ->label('Dòng sản phẩm')
                    ->relationship('productLine', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (Quotation $record): bool => in_array($record->status, [
                            QuotationStatus::Draft,
                            QuotationStatus::Sent,
                            QuotationStatus::Approved,
                        ])),

                    Action::make('convert_to_order')
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
                            $orderNo = 'ORD-'.date('ym').'-'.str_pad((string) (Order::count() + 1), 2, '0', STR_PAD_LEFT);
                            $warehouseId = $data['warehouse_id'] ?? Warehouse::first()?->id ?? 1;

                            $order = Order::create([
                                'order_no' => $orderNo,
                                'customer_id' => $record->customer_id,
                                'warehouse_id' => $warehouseId,
                                'quotation_id' => $record->id,
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
                                ->title('Đã tạo đơn hàng thành công!')
                                ->body("Đơn hàng {$orderNo} đã được khởi tạo từ báo giá {$record->code} kèm toàn bộ danh mục vật tư BOM.")
                                ->success()
                                ->send();
                        }),

                    Action::make('download_pdf')
                        ->label('Xuất PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (Quotation $record): bool => in_array($record->status, [
                            QuotationStatus::Draft,
                            QuotationStatus::Sent,
                            QuotationStatus::Approved,
                            QuotationStatus::Converted,
                        ]))
                        ->action(function (Quotation $record) {
                            return app(QuotationPdfService::class)->downloadPdf($record);
                        }),

                    Action::make('reject_quotation')
                        ->label('Từ chối')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->modalHeading('Ghi nhận lý do từ chối báo giá')
                        ->modalDescription('Cập nhật trạng thái báo giá sang "Bị từ chối" và lưu lại nguyên nhân để phân tích hiệu quả bán hàng.')
                        ->visible(fn (Quotation $record): bool => in_array($record->status, [
                            QuotationStatus::Draft,
                            QuotationStatus::Sent,
                            QuotationStatus::Approved,
                        ]))
                        ->form([
                            Select::make('lost_reason_select')
                                ->label('Lý do chính')
                                ->options([
                                    'Giá cao hơn đối thủ' => 'Giá cao hơn đối thủ',
                                    'Không đủ số lượng thiết bị trong kho' => 'Không đủ số lượng thiết bị trong kho',
                                    'Khách dời / hủy lịch sự kiện' => 'Khách dời / hủy lịch sự kiện',
                                    'Thời gian phản hồi chậm' => 'Thời gian phản hồi chậm',
                                    'Lý do khác' => 'Lý do khác (nhập chi tiết bên dưới)',
                                ])
                                ->required(),
                            Textarea::make('lost_reason_note')
                                ->label('Ghi chú chi tiết thêm')
                                ->placeholder('Nhập chi tiết lý do từ chối (tùy chọn)...'),
                        ])
                        ->action(function (Quotation $record, array $data): void {
                            $reason = $data['lost_reason_select'];
                            if (! empty($data['lost_reason_note'])) {
                                $reason .= ' — '.$data['lost_reason_note'];
                            }

                            $record->update([
                                'status' => QuotationStatus::Rejected,
                                'lost_reason' => $reason,
                            ]);

                            Notification::make()
                                ->title('Đã cập nhật trạng thái báo giá')
                                ->body("Báo giá {$record->code} đã chuyển sang Bị từ chối.")
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
