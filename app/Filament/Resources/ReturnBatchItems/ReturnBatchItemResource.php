<?php

namespace App\Filament\Resources\ReturnBatchItems;

use App\Filament\Resources\ReturnBatchItems\Pages\CreateReturnBatchItem;
use App\Filament\Resources\ReturnBatchItems\Pages\EditReturnBatchItem;
use App\Filament\Resources\ReturnBatchItems\Pages\ListReturnBatchItems;
use App\Filament\Resources\ReturnBatchItems\Pages\ViewReturnBatchItem;
use App\Filament\Resources\ReturnBatchItems\Schemas\ReturnBatchItemForm;
use App\Filament\Resources\ReturnBatchItems\Schemas\ReturnBatchItemInfolist;
use App\Filament\Resources\ReturnBatchItems\Tables\ReturnBatchItemsTable;
use App\Models\ReturnBatchItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ReturnBatchItemResource extends Resource
{
    protected static ?string $model = ReturnBatchItem::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return ReturnBatchItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReturnBatchItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReturnBatchItemsTable::configure($table);
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
            'index' => ListReturnBatchItems::route('/'),
            'create' => CreateReturnBatchItem::route('/create'),
            'view' => ViewReturnBatchItem::route('/{record}'),
            'edit' => EditReturnBatchItem::route('/{record}/edit'),
        ];
    }
}
