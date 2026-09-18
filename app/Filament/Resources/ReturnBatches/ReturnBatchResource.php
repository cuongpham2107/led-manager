<?php

namespace App\Filament\Resources\ReturnBatches;

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
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        if ($agencyId = $user?->getScopedAgencyId()) {
            $query->where('return_batches.agency_id', $agencyId);
        } elseif ($whId = $user?->getScopedWarehouseId()) {
            $query->where(function ($q) use ($whId) {
                $q->where('return_batches.warehouse_id', $whId)
                    ->orWhereHas('checkoutBatch', fn ($cq) => $cq->where('warehouse_id', $whId));
            });
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
            // 'edit' => EditReturnBatch::route('/{record}/edit'),
        ];
    }
}
