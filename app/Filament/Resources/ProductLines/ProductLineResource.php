<?php

namespace App\Filament\Resources\ProductLines;

use App\Filament\Resources\ProductLines\Pages\ListProductLines;
use App\Filament\Resources\ProductLines\Schemas\ProductLineForm;
use App\Filament\Resources\ProductLines\Tables\ProductLinesTable;
use App\Models\ProductLine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductLineResource extends Resource
{
    protected static ?string $model = ProductLine::class;

    protected static string|UnitEnum|null $navigationGroup = 'Dữ liệu gốc';

    protected static ?string $navigationLabel = 'Dòng sản phẩm LED';

    protected static ?string $modelLabel = 'Dòng sản phẩm';

    protected static ?string $pluralModelLabel = 'Dòng sản phẩm LED';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    public static function form(Schema $schema): Schema
    {
        return ProductLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductLinesTable::configure($table);
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
            'index' => ListProductLines::route('/'),
        ];
    }
}
