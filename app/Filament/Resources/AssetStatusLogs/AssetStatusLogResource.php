<?php

namespace App\Filament\Resources\AssetStatusLogs;

use App\Filament\Resources\AssetStatusLogs\Pages\CreateAssetStatusLog;
use App\Filament\Resources\AssetStatusLogs\Pages\EditAssetStatusLog;
use App\Filament\Resources\AssetStatusLogs\Pages\ListAssetStatusLogs;
use App\Filament\Resources\AssetStatusLogs\Pages\ViewAssetStatusLog;
use App\Filament\Resources\AssetStatusLogs\Schemas\AssetStatusLogForm;
use App\Filament\Resources\AssetStatusLogs\Schemas\AssetStatusLogInfolist;
use App\Filament\Resources\AssetStatusLogs\Tables\AssetStatusLogsTable;
use App\Models\AssetStatusLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssetStatusLogResource extends Resource
{
    protected static ?string $model = AssetStatusLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Movement history';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function form(Schema $schema): Schema
    {
        return AssetStatusLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetStatusLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetStatusLogsTable::configure($table);
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
            'index' => ListAssetStatusLogs::route('/'),
            'create' => CreateAssetStatusLog::route('/create'),
            'view' => ViewAssetStatusLog::route('/{record}'),
            'edit' => EditAssetStatusLog::route('/{record}/edit'),
        ];
    }
}
