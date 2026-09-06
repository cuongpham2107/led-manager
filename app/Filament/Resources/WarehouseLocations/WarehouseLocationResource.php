<?php

namespace App\Filament\Resources\WarehouseLocations;

use App\Filament\Resources\WarehouseLocations\Pages\ListWarehouseLocations;
use App\Filament\Resources\WarehouseLocations\Schemas\WarehouseLocationForm;
use App\Filament\Resources\WarehouseLocations\Tables\WarehouseLocationsTable;
use App\Models\User;
use App\Models\WarehouseLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WarehouseLocationResource extends Resource
{
    protected static ?string $model = WarehouseLocation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu gốc';

    protected static ?string $navigationLabel = 'Vị trí kho';

    protected static ?string $modelLabel = 'Vị trí kho';

    protected static ?string $pluralModelLabel = 'Danh sách vị trí kho';

    protected static ?string $slug = 'vi-tri-kho';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->where('warehouse_locations.warehouse_id', $whId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseLocationsTable::configure($table);
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
            'index' => ListWarehouseLocations::route('/'),
        ];
    }
}
