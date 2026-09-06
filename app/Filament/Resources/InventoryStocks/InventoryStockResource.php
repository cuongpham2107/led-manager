<?php

namespace App\Filament\Resources\InventoryStocks;

use App\Filament\Resources\InventoryStocks\Pages\ListInventoryStocks;
use App\Filament\Resources\InventoryStocks\Schemas\InventoryStockForm;
use App\Filament\Resources\InventoryStocks\Tables\InventoryStocksTable;
use App\Models\InventoryStock;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InventoryStockResource extends Resource
{
    protected static ?string $model = InventoryStock::class;

    protected static string|UnitEnum|null $navigationGroup = 'Quản lý kho';

    protected static ?string $navigationLabel = 'Vị trí & tồn kho';

    protected static ?string $modelLabel = 'Tài sản trong kho';

    protected static ?string $pluralModelLabel = 'Tất cả tài sản trong kho';

    protected static ?string $slug = 'vi-tri-ton-kho';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['currentWarehouse', 'warehouseLocation', 'productLine']);

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->where('assets.current_warehouse_id', $whId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryStockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryStocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryStocks::route('/'),
        ];
    }
}
