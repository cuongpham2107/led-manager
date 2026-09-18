<?php

namespace App\Filament\Resources\Assets\Tables;

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\Actions\CompleteMaintenanceBulkAction;
use App\Filament\Resources\Assets\Actions\SendToMaintenanceBulkAction;
use App\Filament\Resources\Assets\Actions\ViewQrCodeAction;
use App\Models\Agency;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Tìm theo số seri hoặc kho hàng...')
            ->defaultSort('serial_no', 'asc')
            ->columns([
                TextColumn::make('serial_no')
                    ->label('SỐ SERI')
                    ->searchable(query: function ($query, string $search) {
                        $query->where(function ($q) use ($search) {
                            $q->where('serial_no', 'like', "%{$search}%")
                                ->orWhereHas('currentWarehouse', fn ($wq) => $wq->where('name', 'like', "%{$search}%")->orWhereHas('agency', fn ($aq) => $aq->where('name', 'like', "%{$search}%")));
                        });
                    })
                    ->alignCenter()
                    ->color('primary')
                    ->weight('bold')
                    ->copyable()
                    ->sortable(),

                TextColumn::make('productLine.name')
                    ->label('DÒNG SẢN PHẨM')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('batch_no')
                    ->label('LÔ SẢN XUẤT')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('current_status')
                    ->label('TRẠNG THÁI')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AssetStatus ? $state->getLabel() : (string) $state)
                    ->color(fn ($state): string => $state instanceof AssetStatus ? $state->getColor() : 'gray')
                    ->sortable(),

                TextColumn::make('manufactured_date')
                    ->label('NGÀY SẢN XUẤT')
                    ->alignCenter()
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('current_warehouse_id')
                    ->label('Kho hàng')
                    ->options(fn (): array => Warehouse::query()->pluck('name', 'id')->toArray())
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && (bool) $user->getScopedWarehouseId();
                    }),

                SelectFilter::make('agency_filter')
                    ->label('Đại lý / Đơn vị')
                    ->options(function (): array {
                        $options = ['hq' => 'Tổng công ty (HQ)'];
                        $agencies = Agency::query()->orderBy('name')->pluck('name', 'id')->toArray();
                        foreach ($agencies as $id => $name) {
                            $options[(string) $id] = $name;
                        }

                        return $options;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $val = $data['value'] ?? null;
                        if (! $val) {
                            return $query;
                        }

                        if ($val === 'hq') {
                            return $query->whereHas('currentWarehouse', function ($wq) {
                                $wq->whereDoesntHave('agency');
                            });
                        }

                        return $query->whereHas('currentWarehouse.agency', function ($aq) use ($val) {
                            $aq->where('id', (int) $val);
                        });
                    })
                    ->hidden(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User && (bool) $user->getScopedWarehouseId();
                    }),

                SelectFilter::make('current_status')
                    ->label('Tất cả trạng thái')
                    ->options(fn (): array => collect(AssetStatus::cases())->mapWithKeys(fn (AssetStatus $status): array => [$status->value => $status->getLabel()])->toArray()),

                SelectFilter::make('product_line_id')
                    ->label('Tất cả dòng sản phẩm')
                    ->options(fn (): array => ProductLine::query()->pluck('name', 'id')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordActions([
                ViewQrCodeAction::make()
                    ->icon('heroicon-o-squares-2x2')
                    ->iconButton()
                    ->tooltip('Xem mã QR'),

                EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->iconButton()
                    ->modalHeading('Cập nhật thông tin tài sản LED')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->tooltip('Chỉnh sửa'),

                DeleteAction::make()
                    ->icon('heroicon-o-x-mark')
                    ->iconButton()
                    ->color('danger')
                    ->tooltip('Xóa'),
            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    SendToMaintenanceBulkAction::make(),
                    CompleteMaintenanceBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
