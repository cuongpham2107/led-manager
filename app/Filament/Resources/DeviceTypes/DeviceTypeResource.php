<?php

namespace App\Filament\Resources\DeviceTypes;

use App\Filament\Resources\DeviceTypes\Pages\CreateDeviceType;
use App\Filament\Resources\DeviceTypes\Pages\EditDeviceType;
use App\Filament\Resources\DeviceTypes\Pages\ListDeviceTypes;
use App\Filament\Resources\DeviceTypes\Pages\ViewDeviceType;
use App\Filament\Resources\DeviceTypes\Schemas\DeviceTypeForm;
use App\Filament\Resources\DeviceTypes\Schemas\DeviceTypeInfolist;
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

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Device types';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DeviceTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeviceTypeInfolist::configure($schema);
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
            'create' => CreateDeviceType::route('/create'),
            'view' => ViewDeviceType::route('/{record}'),
            'edit' => EditDeviceType::route('/{record}/edit'),
        ];
    }
}
