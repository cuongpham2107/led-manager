<?php

namespace App\Filament\Resources\CheckoutBatchItems;

use App\Filament\Resources\CheckoutBatchItems\Pages\CreateCheckoutBatchItem;
use App\Filament\Resources\CheckoutBatchItems\Pages\EditCheckoutBatchItem;
use App\Filament\Resources\CheckoutBatchItems\Pages\ListCheckoutBatchItems;
use App\Filament\Resources\CheckoutBatchItems\Pages\ViewCheckoutBatchItem;
use App\Filament\Resources\CheckoutBatchItems\Schemas\CheckoutBatchItemForm;
use App\Filament\Resources\CheckoutBatchItems\Schemas\CheckoutBatchItemInfolist;
use App\Filament\Resources\CheckoutBatchItems\Tables\CheckoutBatchItemsTable;
use App\Models\CheckoutBatchItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CheckoutBatchItemResource extends Resource
{
    protected static ?string $model = CheckoutBatchItem::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return CheckoutBatchItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CheckoutBatchItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckoutBatchItemsTable::configure($table);
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
            'index' => ListCheckoutBatchItems::route('/'),
            'create' => CreateCheckoutBatchItem::route('/create'),
            'view' => ViewCheckoutBatchItem::route('/{record}'),
            'edit' => EditCheckoutBatchItem::route('/{record}/edit'),
        ];
    }
}
