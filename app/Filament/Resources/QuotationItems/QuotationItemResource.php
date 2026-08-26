<?php

namespace App\Filament\Resources\QuotationItems;

use App\Filament\Resources\QuotationItems\Pages\CreateQuotationItem;
use App\Filament\Resources\QuotationItems\Pages\EditQuotationItem;
use App\Filament\Resources\QuotationItems\Pages\ListQuotationItems;
use App\Filament\Resources\QuotationItems\Schemas\QuotationItemForm;
use App\Filament\Resources\QuotationItems\Tables\QuotationItemsTable;
use App\Models\QuotationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class QuotationItemResource extends Resource
{
    protected static ?string $model = QuotationItem::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Mục báo giá';

    protected static ?string $pluralModelLabel = 'Chi tiết báo giá';

    public static function form(Schema $schema): Schema
    {
        return QuotationItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotationItemsTable::configure($table);
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
            'index' => ListQuotationItems::route('/'),
            'create' => CreateQuotationItem::route('/create'),
            'edit' => EditQuotationItem::route('/{record}/edit'),
        ];
    }
}
