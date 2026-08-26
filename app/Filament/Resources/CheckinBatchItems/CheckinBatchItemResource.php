<?php

namespace App\Filament\Resources\CheckinBatchItems;

use App\Filament\Resources\CheckinBatchItems\Pages\CreateCheckinBatchItem;
use App\Filament\Resources\CheckinBatchItems\Pages\EditCheckinBatchItem;
use App\Filament\Resources\CheckinBatchItems\Pages\ListCheckinBatchItems;
use App\Filament\Resources\CheckinBatchItems\Pages\ViewCheckinBatchItem;
use App\Filament\Resources\CheckinBatchItems\Schemas\CheckinBatchItemForm;
use App\Filament\Resources\CheckinBatchItems\Schemas\CheckinBatchItemInfolist;
use App\Filament\Resources\CheckinBatchItems\Tables\CheckinBatchItemsTable;
use App\Models\CheckinBatchItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CheckinBatchItemResource extends Resource
{
    protected static ?string $model = CheckinBatchItem::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return CheckinBatchItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CheckinBatchItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckinBatchItemsTable::configure($table);
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
            'index' => ListCheckinBatchItems::route('/'),
            'create' => CreateCheckinBatchItem::route('/create'),
            'view' => ViewCheckinBatchItem::route('/{record}'),
            'edit' => EditCheckinBatchItem::route('/{record}/edit'),
        ];
    }
}
