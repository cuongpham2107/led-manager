<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Filament\Resources\Assets\Widgets\AssetStatsOverviewWidget;
use App\Models\Asset;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu gốc';

    protected static ?string $navigationLabel = 'Danh mục thiết bị';

    protected static ?string $modelLabel = 'Tài sản LED';

    protected static ?string $pluralModelLabel = 'Danh sách tài sản LED';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->where('assets.current_warehouse_id', $whId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            AssetStatsOverviewWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
        ];
    }
}
