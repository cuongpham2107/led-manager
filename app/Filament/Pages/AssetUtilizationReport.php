<?php

namespace App\Filament\Pages;

use App\Enums\AssetStatus;
use App\Models\ProductLine;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class AssetUtilizationReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo & Thống kê';

    protected static ?string $navigationLabel = 'Tỷ lệ khai thác kho';

    protected static ?string $title = 'Báo Cáo Tỷ Lệ Khai Thác Kho & Vòng Đời Thiết Bị';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare3Stack3d;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.asset-utilization-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductLine::query()->with('assets')
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Mã dòng')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Dòng LED')
                    ->searchable(),
                TextColumn::make('environment')
                    ->label('Môi trường')
                    ->badge(),
                TextColumn::make('total_cabinets')
                    ->label('Tổng số lượng kho')
                    ->numeric()
                    ->state(fn ($record) => $record->assets()->count()),
                TextColumn::make('ready_count')
                    ->label('Sẵn sàng')
                    ->badge()
                    ->color('success')
                    ->state(fn ($record) => $record->assets()->where('current_status', AssetStatus::Ready)->count()),
                TextColumn::make('in_event_count')
                    ->label('Đang đi sự kiện')
                    ->badge()
                    ->color('info')
                    ->state(fn ($record) => $record->assets()->where('current_status', AssetStatus::InEvent)->count()),
                TextColumn::make('repairing_count')
                    ->label('Đang bảo trì')
                    ->badge()
                    ->color('danger')
                    ->state(fn ($record) => $record->assets()->where('current_status', AssetStatus::Repairing)->count()),
                TextColumn::make('utilization_rate')
                    ->label('Tỷ lệ khai thác (%)')
                    ->badge()
                    ->color(fn ($state) => $state > 70 ? 'danger' : ($state > 40 ? 'warning' : 'success'))
                    ->state(function ($record) {
                        $total = $record->assets()->count();
                        if ($total === 0) {
                            return '0%';
                        }
                        $active = $record->assets()->where('current_status', AssetStatus::InEvent)->count();

                        return round(($active / $total) * 100, 1).'%';
                    }),
            ])
            ->paginated(false);
    }
}
