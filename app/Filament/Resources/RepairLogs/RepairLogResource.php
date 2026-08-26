<?php

namespace App\Filament\Resources\RepairLogs;

use App\Filament\Resources\RepairLogs\Pages\CreateRepairLog;
use App\Filament\Resources\RepairLogs\Pages\EditRepairLog;
use App\Filament\Resources\RepairLogs\Pages\ListRepairLogs;
use App\Filament\Resources\RepairLogs\Pages\ViewRepairLog;
use App\Filament\Resources\RepairLogs\Schemas\RepairLogForm;
use App\Filament\Resources\RepairLogs\Schemas\RepairLogInfolist;
use App\Filament\Resources\RepairLogs\Tables\RepairLogsTable;
use App\Models\RepairLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RepairLogResource extends Resource
{
    protected static ?string $model = RepairLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Maintenance';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return RepairLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RepairLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepairLogsTable::configure($table);
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
            'index' => ListRepairLogs::route('/'),
            'create' => CreateRepairLog::route('/create'),
            'view' => ViewRepairLog::route('/{record}'),
            'edit' => EditRepairLog::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
