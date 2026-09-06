<?php

namespace App\Filament\Resources\CheckinBatches;

use App\Filament\Resources\CheckinBatches\Pages\ListCheckinBatches;
use App\Filament\Resources\CheckinBatches\Schemas\CheckinBatchForm;
use App\Filament\Resources\CheckinBatches\Tables\CheckinBatchesTable;
use App\Models\CheckinBatch;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CheckinBatchResource extends Resource
{
    protected static ?string $model = CheckinBatch::class;

    protected static string|UnitEnum|null $navigationGroup = 'Quản lý kho';

    protected static ?string $navigationLabel = 'Nhập kho (Check-in)';

    protected static ?string $modelLabel = 'Đợt nhập kho';

    protected static ?string $pluralModelLabel = 'Danh sách nhập kho';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->where('checkin_batches.warehouse_id', $whId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return CheckinBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckinBatchesTable::configure($table);
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
            'index' => ListCheckinBatches::route('/'),
        ];
    }
}
