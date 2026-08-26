<?php

namespace App\Filament\Resources\Contracts\Schemas;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Quotation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin hợp đồng & Pháp lý')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Số hợp đồng')
                                    ->default(fn () => 'HD-'.date('ym').'-'.str_pad((string) (Contract::count() + 1), 2, '0', STR_PAD_LEFT))
                                    ->required(),
                                Select::make('customer_id')
                                    ->label('Khách hàng / Đối tác')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('status')
                                    ->label('Trạng thái')
                                    ->options(ContractStatus::class)
                                    ->default(ContractStatus::Draft)
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                Select::make('quotation_id')
                                    ->label('Từ báo giá gốc')
                                    ->relationship('quotation', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $quo = Quotation::find($state);
                                            if ($quo) {
                                                $set('customer_id', $quo->customer_id);
                                                $set('contract_value', $quo->total_price);
                                                $set('start_date', $quo->event_start_date);
                                                $set('end_date', $quo->event_end_date);
                                                $set('deposit_amount', round($quo->total_price * 0.5));
                                                $set('sales_user_id', $quo->sales_user_id);
                                                $set('title', 'Hợp đồng cho thuê màn hình LED: '.$quo->event_name);
                                            }
                                        }
                                    }),
                                Select::make('order_id')
                                    ->label('Đơn hàng liên kết')
                                    ->relationship('order', 'order_no')
                                    ->searchable()
                                    ->preload(),
                                Select::make('sales_user_id')
                                    ->label('Đại diện kinh doanh')
                                    ->relationship('salesUser', 'name')
                                    ->default(fn () => Auth::id())
                                    ->searchable()
                                    ->preload(),
                            ]),
                        TextInput::make('title')
                            ->label('Tên hợp đồng / Dự án')
                            ->placeholder('VD: Hợp đồng cho thuê màn hình LED phục vụ sự kiện VinFast')
                            ->columnSpanFull(),
                    ]),

                Section::make('Giá trị hợp đồng, Đặt cọc & Thời hạn')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('contract_value')
                                    ->label('Tổng giá trị hợp đồng')
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $val = (float) str_replace(',', '', (string) $state);
                                        $pct = (float) ($get('deposit_percent') ?: 50);
                                        $set('deposit_amount', round($val * ($pct / 100)));
                                    }),
                                TextInput::make('deposit_percent')
                                    ->label('Tỷ lệ đặt cọc (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(50)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $val = (float) str_replace(',', '', (string) $get('contract_value'));
                                        $pct = (float) $state;
                                        $set('deposit_amount', round($val * ($pct / 100)));
                                    }),
                                TextInput::make('deposit_amount')
                                    ->label('Tiền đặt cọc cần thu')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->default(0),
                            ]),
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('signed_date')
                                    ->label('Ngày ký hợp đồng')
                                    ->native(false)
                                    ->default(now()),
                                DatePicker::make('start_date')
                                    ->label('Ngày bắt đầu thuê')
                                    ->native(false),
                                DatePicker::make('end_date')
                                    ->label('Ngày kết thúc thuê')
                                    ->native(false),
                            ]),
                        Textarea::make('terms')
                            ->label('Điều khoản cam kết & Chế tài bảo hành')
                            ->placeholder('1. Bên B có trách nhiệm đặt cọc 50% trước khi xuất kho...')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('note')
                            ->label('Ghi chú nội bộ')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
