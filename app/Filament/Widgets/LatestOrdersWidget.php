<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?string $heading = 'Đơn Hàng Gần Đây';

    protected static ?int $sort = 4;

    public static int $gridW = 24;

    public static int $gridH = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(5)
            )
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
