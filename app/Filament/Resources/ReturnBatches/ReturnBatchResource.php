<?php

namespace App\Filament\Resources\ReturnBatches;

use App\Filament\Resources\ReturnBatches\Pages\CreateReturnBatch;
use App\Filament\Resources\ReturnBatches\Pages\EditReturnBatch;
use App\Filament\Resources\ReturnBatches\Pages\ListReturnBatches;
use App\Filament\Resources\ReturnBatches\Pages\ViewReturnBatch;
use App\Filament\Resources\ReturnBatches\Schemas\ReturnBatchForm;
use App\Filament\Resources\ReturnBatches\Schemas\ReturnBatchInfolist;
use App\Filament\Resources\ReturnBatches\Tables\ReturnBatchesTable;
use App\Models\ReturnBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ReturnBatchResource extends Resource
{
    protected static ?string $model = ReturnBatch::class;

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Return check-in';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    public static function form(Schema $schema): Schema
    {
        return ReturnBatchForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReturnBatchInfolist::configure($schema);
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
            'view' => ViewReturnBatch::route('/{record}'),
            'edit' => EditReturnBatch::route('/{record}/edit'),
        ];
    }
}
