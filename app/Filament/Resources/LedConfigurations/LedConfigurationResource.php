<?php

namespace App\Filament\Resources\LedConfigurations;

use App\Filament\Resources\LedConfigurations\Pages\ListLedConfigurations;
use App\Filament\Resources\LedConfigurations\Schemas\LedConfigurationForm;
use App\Filament\Resources\LedConfigurations\Tables\LedConfigurationsTable;
use App\Models\LedConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LedConfigurationResource extends Resource
{
    protected static ?string $model = LedConfiguration::class;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Cấu hình LED';

    protected static ?string $modelLabel = 'Cấu hình LED';

    protected static ?string $pluralModelLabel = 'Cấu hình LED';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function form(Schema $schema): Schema
    {
        return LedConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LedConfigurationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLedConfigurations::route('/'),
        ];
    }
}
