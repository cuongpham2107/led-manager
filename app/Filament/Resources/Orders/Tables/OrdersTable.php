<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Contracts\ContractResource;
use App\Filament\Resources\Orders\Actions\AssignCrewAction;
use App\Filament\Resources\Orders\Actions\ChangeOrderAction;
use App\Filament\Resources\Orders\Actions\CompleteOrderAction;
use App\Filament\Resources\Orders\Actions\CreateCheckoutBatchAction;
use App\Filament\Resources\Orders\Actions\CreateContractAction;
use App\Filament\Resources\Orders\Actions\DispatchOrderAction;
use App\Filament\Resources\Orders\Actions\ManageTimelineAction;
use App\Filament\Resources\Orders\Actions\ReturnOrderAction;
use App\Filament\Resources\Orders\Actions\ViewCheckoutBatchAction;
use App\Filament\Resources\Orders\Actions\ViewContractAction;
use App\Filament\Resources\Orders\Actions\ViewReturnBatchAction;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['contracts', 'customer', 'warehouse', 'quotation', 'salesUser', 'checkoutBatches.items.asset.productLine', 'checkoutBatches.returnBatches']))
            ->columns([
                TextColumn::make('order_no')
                    ->label('Số đơn hàng')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->description(fn (Order $record): ?string => $record->event)
                    ->searchable(['name', 'event'])
                    ->sortable(),
                TextColumn::make('contract_status')
                    ->label('Hợp đồng')
                    ->badge()
                    ->state(function (Order $record): string {
                        $contract = $record->contracts->first();

                        return $contract ? $contract->status->getLabel() : 'Chưa tạo HĐ';
                    })
                    ->description(fn (Order $record): ?string => $record->contracts->first()?->code)
                    ->color(function (Order $record): string {
                        $contract = $record->contracts->first();

                        return $contract?->status->getColor() ?? 'gray';
                    })
                    ->icon(function (Order $record): ?string {
                        $contract = $record->contracts->first();

                        return $contract?->status->getIcon() ?? 'heroicon-m-document';
                    })
                    ->url(function (Order $record): ?string {
                        $contract = $record->contracts->first();

                        return $contract ? ContractResource::getUrl('edit', ['record' => $contract]) : null;
                    }),
                TextColumn::make('deposit_status')
                    ->label('Đặt cọc')
                    ->badge()
                    ->state(function (Order $record): string {
                        $contract = $record->contracts->first();
                        if (! $contract) {
                            return 'Chưa có HĐ';
                        }

                        $depositPaid = $record->deposit_paid;
                        $depositRequired = (float) $contract->deposit_amount;
                        $totalPaid = $record->total_paid;

                        if ($totalPaid >= (float) $record->value && (float) $record->value > 0) {
                            return 'Đã tất toán';
                        }

                        if ($depositRequired <= 0) {
                            return 'Không yêu cầu cọc';
                        }

                        if ($depositPaid >= $depositRequired) {
                            return 'Đã cọc';
                        }

                        if ($depositPaid > 0) {
                            return 'Cọc 1 phần';
                        }

                        return 'Chưa đặt cọc';
                    })
                    ->description(function (Order $record): ?string {
                        $contract = $record->contracts->first();
                        if (! $contract) {
                            return null;
                        }

                        $depositPaid = $record->deposit_paid;
                        $depositRequired = (float) $contract->deposit_amount;

                        if ($depositPaid >= $depositRequired && $depositRequired > 0) {
                            return number_format($depositPaid, 0, ',', '.').' đ';
                        }

                        if ($depositPaid > 0) {
                            return number_format($depositPaid, 0, ',', '.').' / '.number_format($depositRequired, 0, ',', '.').' đ';
                        }

                        if ($depositRequired > 0) {
                            return 'Cần: '.number_format($depositRequired, 0, ',', '.').' đ';
                        }

                        return null;
                    })
                    ->color(function (Order $record): string {
                        $contract = $record->contracts->first();
                        if (! $contract) {
                            return 'gray';
                        }

                        $depositPaid = $record->deposit_paid;
                        $depositRequired = (float) $contract->deposit_amount;
                        $totalPaid = $record->total_paid;

                        if ($totalPaid >= (float) $record->value && (float) $record->value > 0) {
                            return 'emerald';
                        }

                        if ($depositPaid >= $depositRequired && $depositRequired > 0) {
                            return 'success';
                        }

                        if ($depositPaid > 0) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->icon(function (Order $record): ?string {
                        $contract = $record->contracts->first();
                        if (! $contract) {
                            return 'heroicon-m-minus';
                        }

                        $depositPaid = $record->deposit_paid;
                        $depositRequired = (float) $contract->deposit_amount;
                        $totalPaid = $record->total_paid;

                        if ($totalPaid >= (float) $record->value && (float) $record->value > 0) {
                            return 'heroicon-m-check-circle';
                        }

                        if ($depositPaid >= $depositRequired && $depositRequired > 0) {
                            return 'heroicon-m-check-badge';
                        }

                        if ($depositPaid > 0) {
                            return 'heroicon-m-exclamation-triangle';
                        }

                        return 'heroicon-m-x-circle';
                    }),
                TextColumn::make('warehouse.name')
                    ->label('Kho xuất')
                    ->badge()
                    ->color('info')
                    ->sortable(),
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
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Tổng diện tích')
                            ->suffix(' m²')
                            ->numeric(2),
                    ),
                TextColumn::make('value')
                    ->label('Tổng tiền')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold')
                    ->summarize(
                        Sum::make()
                            ->label('Tổng doanh thu')
                            ->money('VND'),
                    ),
                TextColumn::make('deposit_paid')
                    ->label('Tiền cọc đã thu')
                    ->money('VND')
                    ->sortable()
                    ->color('warning')
                    ->summarize(
                        Sum::make()
                            ->label('Tổng tiền cọc')
                            ->money('VND'),
                    ),
                TextColumn::make('remaining_due')
                    ->label('Còn phải thu')
                    ->money('VND')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->state(fn (Order $record): float => max(0, (float) $record->value - (float) $record->total_paid)),
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
                SelectFilter::make('contract_filter')
                    ->label('Tình trạng Hợp đồng')
                    ->options([
                        'has_contract' => 'Đã có hợp đồng',
                        'no_contract' => 'Chưa có hợp đồng',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? null) === 'has_contract') {
                            $query->has('contracts');
                        } elseif (($data['value'] ?? null) === 'no_contract') {
                            $query->doesntHave('contracts');
                        }
                    }),
                SelectFilter::make('deposit_filter')
                    ->label('Tình trạng Đặt cọc')
                    ->options([
                        'deposited' => 'Đã đặt cọc',
                        'not_deposited' => 'Chưa đặt cọc',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? null) === 'deposited') {
                            $query->where('deposit_paid', '>', 0);
                        } elseif (($data['value'] ?? null) === 'not_deposited') {
                            $query->where(fn ($q) => $q->whereNull('deposit_paid')->orWhere('deposit_paid', 0));
                        }
                    }),
                SelectFilter::make('warehouse_id')
                    ->label('Kho hàng')
                    ->relationship('warehouse', 'name'),
                SelectFilter::make('customer_id')
                    ->label('Khách hàng')
                    ->relationship('customer', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    CreateContractAction::make(),
                    ViewContractAction::make(),
                    CreateCheckoutBatchAction::make(),
                    ViewCheckoutBatchAction::make(),
                    DispatchOrderAction::make(),
                    ReturnOrderAction::make(),
                    ViewReturnBatchAction::make(),
                    ChangeOrderAction::make(),
                    AssignCrewAction::make(),
                    ManageTimelineAction::make(),
                    CompleteOrderAction::make(),
                ]),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
