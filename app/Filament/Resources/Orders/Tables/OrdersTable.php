<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\BatchStatus;
use App\Enums\ContractStatus;
use App\Enums\OrderStatus;
use App\Models\CheckoutBatch;
use App\Models\Contract;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_no')
                    ->label('Số đơn hàng')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Kho xuất')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->searchable()
                    ->limit(25),
                TextColumn::make('request_date')
                    ->label('Ngày bắt đầu')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('expected_return_date')
                    ->label('Ngày dự kiến trả')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('area_m2')
                    ->label('Diện tích')
                    ->suffix(' m²')
                    ->numeric(2)
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Tổng tiền')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->sortable(),
                TextColumn::make('quotation.code')
                    ->label('Từ báo giá')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salesUser.name')
                    ->label('Sales phụ trách')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(OrderStatus::class),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name'),
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('create_contract')
                        ->label('Tạo Hợp đồng')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled]))
                        ->action(function (Order $record): void {
                            $contractCode = 'HD-'.date('ym').'-'.str_pad((string) (Contract::count() + 1), 2, '0', STR_PAD_LEFT);
                            $contract = Contract::create([
                                'code' => $contractCode,
                                'quotation_id' => $record->quotation_id,
                                'customer_id' => $record->customer_id,
                                'order_id' => $record->id,
                                'title' => 'Hợp đồng cho thuê màn hình LED: '.($record->event ?: $record->order_no),
                                'signed_date' => now()->toDateString(),
                                'start_date' => $record->request_date,
                                'end_date' => $record->expected_return_date,
                                'contract_value' => $record->value,
                                'deposit_percent' => 50,
                                'deposit_amount' => round((float) $record->value * 0.5),
                                'status' => ContractStatus::Draft,
                                'sales_user_id' => $record->sales_user_id,
                                'created_by' => Auth::id(),
                            ]);

                            Notification::make()
                                ->title('Đã tạo hợp đồng thành công!')
                                ->body("Hợp đồng {$contractCode} đã được tạo tự động cho đơn hàng {$record->order_no}.")
                                ->success()
                                ->send();
                        }),

                    Action::make('create_checkout_batch')
                        ->label('Tạo Đợt Xuất Kho')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Draft, OrderStatus::OutboundCreated]))
                        ->action(function (Order $record): void {
                            $code = 'OUT-'.date('ym').'-'.str_pad((string) (CheckoutBatch::count() + 1), 2, '0', STR_PAD_LEFT);

                            $batch = CheckoutBatch::create([
                                'code' => $code,
                                'order_id' => $record->id,
                                'customer_id' => $record->customer_id,
                                'warehouse_id' => $record->warehouse_id,
                                'required_area_m2' => $record->area_m2,
                                'device_type_id' => $record->device_type_id,
                                'expected_return_date' => $record->expected_return_date,
                                'status' => BatchStatus::Pending,
                                'created_by' => Auth::id(),
                            ]);

                            $record->update([
                                'status' => OrderStatus::OutboundCreated,
                            ]);

                            Notification::make()
                                ->title('Đã tạo phiếu xuất kho!')
                                ->body("Phiếu xuất kho {$code} cho đơn hàng {$record->order_no} đã được tạo thành công.")
                                ->success()
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
