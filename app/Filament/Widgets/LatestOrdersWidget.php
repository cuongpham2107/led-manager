<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Đơn Hàng Gần Đây';

    protected static ?int $sort = 4;

    public static int $gridW = 24;

    public static int $gridH = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Order::query()->latest();
        /** @var User|null $user */
        $user = Auth::user();
        if ($agencyId = $user?->getScopedAgencyId()) {
            $query->where(function ($q) use ($agencyId, $user) {
                $q->where('agency_id', $agencyId);
                if ($whId = $user->getScopedWarehouseId()) {
                    $q->orWhere('warehouse_id', $whId);
                }
            });
        } elseif ($whId = $user?->getScopedWarehouseId()) {
            $query->where('warehouse_id', $whId);
        }

        return $table
            ->query($query->limit(5))
            ->columns([
                TextColumn::make('order_no')
                    ->label('Mã đơn')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable(),
                TextColumn::make('event')
                    ->label('Sự kiện')
                    ->placeholder('—'),
                TextColumn::make('warehouse.name')
                    ->label('Kho xuất'),
                TextColumn::make('request_date')
                    ->label('Ngày bắt đầu')
                    ->date('d/m/Y'),
                TextColumn::make('expected_return_date')
                    ->label('Dự kiến trả')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('value')
                    ->label('Giá trị đơn')
                    ->money('VND')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),
            ])
            ->paginated(false);
    }
}
