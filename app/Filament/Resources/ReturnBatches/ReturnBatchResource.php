<?php

namespace App\Filament\Resources\ReturnBatches;

use App\Filament\Resources\ReturnBatches\Pages\CreateReturnBatch;
use App\Filament\Resources\ReturnBatches\Pages\EditReturnBatch;
use App\Filament\Resources\ReturnBatches\Pages\ListReturnBatches;
use App\Filament\Resources\ReturnBatches\Schemas\ReturnBatchForm;
use App\Filament\Resources\ReturnBatches\Tables\ReturnBatchesTable;
use App\Models\ReturnBatch;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReturnBatchResource extends Resource
{
    protected static ?string $model = ReturnBatch::class;

    protected static string|UnitEnum|null $navigationGroup = 'Quản lý kho';

    protected static ?string $navigationLabel = 'Thu hồi & Trả kho';

    protected static ?string $modelLabel = 'Đợt trả hàng';

    protected static ?string $pluralModelLabel = 'Danh sách trả kho';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->whereHas('checkoutBatch', fn ($q) => $q->where('warehouse_id', $whId));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ReturnBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnBatchesTable::configure($table);
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
            'index' => ListReturnBatches::route('/'),
            'create' => CreateReturnBatch::route('/create'),
            'edit' => EditReturnBatch::route('/{record}/edit'),
        ];
    }
}
