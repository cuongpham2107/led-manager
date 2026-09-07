<?php

namespace App\Filament\Resources\PricingRules;

use App\Filament\Resources\PricingRules\Pages\ListPricingRules;
use App\Filament\Resources\PricingRules\Schemas\PricingRuleForm;
use App\Filament\Resources\PricingRules\Tables\PricingRulesTable;
use App\Models\PricingRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?string $navigationLabel = 'Bảng giá thuê';

    protected static ?string $modelLabel = 'Bảng giá thuê';

    protected static ?string $pluralModelLabel = 'Bảng giá thuê';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return PricingRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PricingRulesTable::configure($table);
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
            'index' => ListPricingRules::route('/'),
        ];
    }
}
