<?php

namespace App\Filament\Resources\CheckoutBatches;

use App\Filament\Resources\CheckoutBatches\Pages\CreateCheckoutBatch;
use App\Filament\Resources\CheckoutBatches\Pages\EditCheckoutBatch;
use App\Filament\Resources\CheckoutBatches\Pages\ListCheckoutBatches;
use App\Filament\Resources\CheckoutBatches\Pages\ViewCheckoutBatch;
use App\Filament\Resources\CheckoutBatches\Schemas\CheckoutBatchForm;
use App\Filament\Resources\CheckoutBatches\Schemas\CheckoutBatchInfolist;
use App\Filament\Resources\CheckoutBatches\Tables\CheckoutBatchesTable;
use App\Models\CheckoutBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CheckoutBatchResource extends Resource
{
    protected static ?string $model = CheckoutBatch::class;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Check-out';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    public static function form(Schema $schema): Schema
    {
        return CheckoutBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CheckoutBatchInfolist::configure($schema);
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
            'view' => ViewCheckoutBatch::route('/{record}'),
            'edit' => EditCheckoutBatch::route('/{record}/edit'),
        ];
    }
}
