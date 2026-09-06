<?php

namespace App\Filament\Resources\RepairLogs;

use App\Filament\Resources\RepairLogs\Pages\ListRepairLogs;
use App\Filament\Resources\RepairLogs\Schemas\RepairLogForm;
use App\Filament\Resources\RepairLogs\Tables\RepairLogsTable;
use App\Models\RepairLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RepairLogResource extends Resource
{
    protected static ?string $model = RepairLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Quản lý kho';

    protected static ?string $navigationLabel = 'Bảo trì & Sửa chữa';

    protected static ?string $modelLabel = 'Nhật ký sửa chữa';

    protected static ?string $pluralModelLabel = 'Bảo trì & Sửa chữa';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->whereHas('asset', fn ($q) => $q->where('current_warehouse_id', $whId));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return RepairLogForm::configure($schema);
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
        ];
    }
}
