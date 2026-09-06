<?php

namespace App\Filament\Resources\CheckoutBatches;

use App\Filament\Resources\CheckoutBatches\Pages\CreateCheckoutBatch;
use App\Filament\Resources\CheckoutBatches\Pages\EditCheckoutBatch;
use App\Filament\Resources\CheckoutBatches\Pages\ListCheckoutBatches;
use App\Filament\Resources\CheckoutBatches\Schemas\CheckoutBatchForm;
use App\Filament\Resources\CheckoutBatches\Tables\CheckoutBatchesTable;
use App\Models\CheckoutBatch;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CheckoutBatchResource extends Resource
{
    protected static ?string $model = CheckoutBatch::class;

    protected static string|UnitEnum|null $navigationGroup = 'Quản lý kho';

    protected static ?string $navigationLabel = 'Xuất kho (Check-out)';

    protected static ?string $modelLabel = 'Đợt xuất kho';

    protected static ?string $pluralModelLabel = 'Danh sách xuất kho';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = auth()->user();

        if ($whId = $user?->getScopedWarehouseId()) {
            $query->where('checkout_batches.warehouse_id', $whId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return CheckoutBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckoutBatchesTable::configure($table);
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
            'index' => ListCheckoutBatches::route('/'),
            'create' => CreateCheckoutBatch::route('/create'),
            'edit' => EditCheckoutBatch::route('/{record}/edit'),
        ];
    }
}
