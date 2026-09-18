<?php

namespace App\Filament\Resources\PricingRules\Schemas;

use App\Enums\CustomerType;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class PricingRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cấu hình định giá theo Máy / Ngày & Thời điểm')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Tên đợt / Bảng giá')
                                    ->placeholder('VD: Bảng giá tiêu chuẩn 2026, Mùa cao điểm...')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                Select::make('agency_id')
                                    ->label('Đại lý áp dụng')
                                    ->relationship('agency', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Toàn quốc (Mặc định)')
                                    ->default(function () {
                                        $user = Auth::user();

                                        return $user instanceof User ? $user->getScopedAgencyId() : null;
                                    })
                                    ->disabled(function () {
                                        $user = Auth::user();

                                        return (bool) ($user instanceof User && $user->getScopedAgencyId());
                                    })
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Select::make('product_line_id')
                                    ->label('Dòng máy / Thiết bị LED')
                                    ->relationship('productLine', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),
                                DatePicker::make('effective_from')
                                    ->label('Áp dụng từ ngày')
                                    ->displayFormat('d/m/Y')
                                    ->native(true)
                                    ->default(now()->toDateString())
                                    ->columnSpan(1),
                                DatePicker::make('effective_to')
                                    ->label('Áp dụng đến ngày')
                                    ->displayFormat('d/m/Y')
                                    ->native(true)
                                    ->placeholder('Vô thời hạn')
                                    ->columnSpan(1),
                            ]),
                        Grid::make(4)
                            ->schema([
                                TextInput::make('base_price_per_unit_per_day')
                                    ->label('Đơn giá thuê / máy (tấm) / ngày')
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(400000)
                                    ->columnSpan(2),
                                TextInput::make('min_days')
                                    ->label('Từ ngày thứ')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->columnSpan(1),
                                TextInput::make('max_days')
                                    ->label('Đến ngày thứ')
                                    ->numeric()
                                    ->placeholder('Không giới hạn')
                                    ->columnSpan(1),
                            ]),
                        Grid::make(4)
                            ->schema([
                                Select::make('customer_type')
                                    ->label('Nhóm khách hàng')
                                    ->options(CustomerType::class)
                                    ->placeholder('Tất cả nhóm khách hàng'),
                                TextInput::make('discount_percent')
                                    ->label('Chiết khấu (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0),
                                TextInput::make('crew_rate_per_person_per_day')
                                    ->label('Chi phí nhân công / người / ngày')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(1600000),
                                TextInput::make('transport_rate_per_km')
                                    ->label('Đơn giá vận chuyển / km')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(28000),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('accessory_rate_per_m2')
                                    ->label('Phụ kiện & vật tư / m²')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(50000),
                                Toggle::make('is_active')
                                    ->label('Đang áp dụng hiệu lực')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
