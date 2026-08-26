<?php

namespace App\Filament\Resources\DeviceTypes;

use App\Filament\Resources\DeviceTypes\Pages\ListDeviceTypes;
use App\Filament\Resources\DeviceTypes\Schemas\DeviceTypeForm;
use App\Filament\Resources\DeviceTypes\Tables\DeviceTypesTable;
use App\Models\DeviceType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeviceTypeResource extends Resource
{
    protected static ?string $model = DeviceType::class;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu gốc';

    protected static ?string $navigationLabel = 'Loại thiết bị';

    protected static ?string $modelLabel = 'Loại thiết bị';

    protected static ?string $pluralModelLabel = 'Danh mục loại thiết bị';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DeviceTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeviceTypesTable::configure($table);
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
            'index' => ListDeviceTypes::route('/'),
        ];
    }
}
