<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\Contract;
use App\Services\CodeGeneratorService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Lịch sử thanh toán & Phiếu thu';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    TextInput::make('code')
                        ->label('Mã phiếu')
                        ->default(fn () => CodeGeneratorService::generate('PAY', 'payments'))
                        ->required(),
                    Select::make('type')
                        ->label('Loại thanh toán')
                        ->options(PaymentType::class)
                        ->default(PaymentType::Partial)
                        ->required(),
                    Select::make('method')
                        ->label('Hình thức')
                        ->options(PaymentMethod::class)
                        ->default(PaymentMethod::BankTransfer)
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('amount')
                        ->label('Số tiền thu')
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(',')
                        ->numeric()
                        ->suffix(' đ')
                        ->required(),
                    DatePicker::make('payment_date')
                        ->label('Ngày thu')
                        ->native(false)
                        ->default(now()->toDateString())
                        ->required(),
                ]),
                Grid::make(2)->schema([
                    TextInput::make('reference')
                        ->label('Mã GD / HĐ')
                        ->placeholder('VD: FT260812345678'),
                    Select::make('received_by')
                        ->label('Người thu')
                        ->relationship('receiver', 'name')
                        ->default(fn () => Auth::id())
                        ->searchable()
                        ->preload(),
                ]),
                Textarea::make('note')
                    ->label('Ghi chú')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Mã phiếu')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge(),
                TextColumn::make('method')
                    ->label('Hình thức')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Số tiền')
                    ->money('VND')
                    ->weight('bold')
                    ->color('success')
                    ->summarize(
                        Sum::make()->label('Tổng đã thu')->money('VND')
                    ),
                TextColumn::make('payment_date')
                    ->label('Ngày thu')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Mã tham chiếu')
                    ->placeholder('—'),
                TextColumn::make('receiver.name')
                    ->label('Người thu')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Loại')
                    ->options(PaymentType::class),
                SelectFilter::make('method')
                    ->label('Hình thức')
                    ->options(PaymentMethod::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Contract $owner */
                        $owner = $this->getOwnerRecord();
                        $data['customer_id'] = $owner->customer_id;
                        $data['order_id'] = $owner->order_id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
