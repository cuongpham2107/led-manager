<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\BatchStatus;
use App\Enums\ContractStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use App\Filament\Resources\Contracts\ContractResource;
use App\Models\CheckoutBatch;
use App\Models\Contract;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['contracts.payments', 'payments', 'customer', 'warehouse', 'quotation', 'salesUser', 'checkoutBatches']))
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
                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->searchable()
                    ->limit(25),
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
                            $query->where(function ($q) {
                                $q->whereHas('payments', fn ($pq) => $pq->where('type', PaymentType::Deposit)->where('amount', '>', 0))
                                    ->orWhereHas('contracts.payments', fn ($cq) => $cq->where('type', PaymentType::Deposit)->where('amount', '>', 0));
                            });
                        } elseif (($data['value'] ?? null) === 'not_deposited') {
                            $query->whereDoesntHave('payments', fn ($pq) => $pq->where('type', PaymentType::Deposit)->where('amount', '>', 0))
                                ->whereDoesntHave('contracts.payments', fn ($cq) => $cq->where('type', PaymentType::Deposit)->where('amount', '>', 0));
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
                ActionGroup::make([
                    EditAction::make(),

                    Action::make('create_contract')
                        ->label('Tạo Hợp đồng')
                        ->icon('heroicon-o-document-plus')
                        ->color('primary')
                        ->visible(fn (Order $record): bool => ! in_array($record->status, [OrderStatus::Cancelled]) && $record->contracts->isEmpty())
                        ->action(function (Order $record): void {
                            $contractCount = Contract::count() + 1;
                            $contractCode = 'HD-'.date('ym').'-'.str_pad((string) $contractCount, 2, '0', STR_PAD_LEFT);
                            while (Contract::where('code', $contractCode)->exists()) {
                                $contractCount++;
                                $contractCode = 'HD-'.date('ym').'-'.str_pad((string) $contractCount, 2, '0', STR_PAD_LEFT);
                            }

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

                    Action::make('view_contract')
                        ->label('Xem Hợp đồng')
                        ->icon('heroicon-o-document-check')
                        ->color('info')
                        ->visible(fn (Order $record): bool => $record->contracts->isNotEmpty())
                        ->url(fn (Order $record): ?string => ($contract = $record->contracts->first()) ? ContractResource::getUrl('edit', ['record' => $contract]) : null),

                    Action::make('create_checkout_batch')
                        ->label('Tạo Đợt Xuất Kho')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record): bool => $record->status === OrderStatus::Draft && $record->checkoutBatches->isEmpty())
                        ->action(function (Order $record): void {
                            $batchCount = CheckoutBatch::count() + 1;
                            $code = 'OUT-'.date('ym').'-'.str_pad((string) $batchCount, 2, '0', STR_PAD_LEFT);
                            while (CheckoutBatch::where('code', $code)->exists()) {
                                $batchCount++;
                                $code = 'OUT-'.date('ym').'-'.str_pad((string) $batchCount, 2, '0', STR_PAD_LEFT);
                            }

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

                    Action::make('view_checkout_batch')
                        ->label('Xem Đợt Xuất Kho')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('warning')
                        ->visible(fn (Order $record): bool => $record->checkoutBatches->isNotEmpty())
                        ->url(fn (Order $record): ?string => ($batch = $record->checkoutBatches->first()) ? CheckoutBatchResource::getUrl('edit', ['record' => $batch]) : null),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
