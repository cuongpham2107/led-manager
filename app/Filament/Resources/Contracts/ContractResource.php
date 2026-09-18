<?php

namespace App\Filament\Resources\Contracts;

use App\Filament\Resources\Contracts\Pages\CreateContract;
use App\Filament\Resources\Contracts\Pages\EditContract;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Filament\Resources\Contracts\Schemas\ContractForm;
use App\Filament\Resources\Contracts\Tables\ContractsTable;
use App\Models\Contract;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng & Dự án';

    protected static ?string $navigationLabel = 'Hợp đồng cho thuê';

    protected static ?string $modelLabel = 'Hợp đồng';

    protected static ?string $pluralModelLabel = 'Hợp đồng cho thuê';

    protected static ?int $navigationSort = 2;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if ($agencyId = $user?->getScopedAgencyId()) {
            $query->where(function (Builder $q) use ($agencyId) {
                $q->whereHas('order', fn ($oq) => $oq->where('agency_id', $agencyId))
                    ->orWhereHas('salesUser', fn ($sq) => $sq->where('agency_id', $agencyId));
            });
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return ContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractsTable::configure($table);
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
            'index' => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'edit' => EditContract::route('/{record}/edit'),
        ];
    }
}
