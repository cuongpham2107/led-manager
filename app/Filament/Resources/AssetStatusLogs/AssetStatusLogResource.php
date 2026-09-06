<?php

namespace App\Filament\Resources\AssetStatusLogs;

use App\Filament\Resources\AssetStatusLogs\Pages\CreateAssetStatusLog;
use App\Filament\Resources\AssetStatusLogs\Pages\EditAssetStatusLog;
use App\Filament\Resources\AssetStatusLogs\Pages\ListAssetStatusLogs;
use App\Filament\Resources\AssetStatusLogs\Schemas\AssetStatusLogForm;
use App\Filament\Resources\AssetStatusLogs\Tables\AssetStatusLogsTable;
use App\Models\AssetStatusLog;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssetStatusLogResource extends Resource
{
    protected static ?string $model = AssetStatusLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Lịch sử điều chuyển';

    protected static ?string $modelLabel = 'Lịch sử điều chuyển';

    protected static ?string $pluralModelLabel = 'Lịch sử điều chuyển';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->where(fn ($q) => $q->where('from_warehouse_id', $whId)
                ->orWhere('to_warehouse_id', $whId)
                ->orWhereHas('asset', fn ($aq) => $aq->where('current_warehouse_id', $whId)));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return AssetStatusLogForm::configure($schema);
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
            'edit' => EditAssetStatusLog::route('/{record}/edit'),
        ];
    }
}
