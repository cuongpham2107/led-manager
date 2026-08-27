<?php

namespace App\Filament\Resources\Contracts\Schemas;

use App\Enums\ContractStatus;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Contract;
use App\Models\Quotation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
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
                Grid::make(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->schema([

                        // ================= LEFT COLUMN: Form Inputs (5 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 5])
                            ->schema([
                                Section::make('Thông tin hợp đồng & Pháp lý')
                                    ->description('Số hợp đồng, khách hàng, nguồn báo giá và người phụ trách')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('code')
                                                ->label('Số hợp đồng')
                                                ->default(function () {
                                                    $count = Contract::count() + 1;
                                                    $code = 'HD-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                                                    while (Contract::where('code', $code)->exists()) {
                                                        $count++;
                                                        $code = 'HD-'.date('ym').'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);
                                                    }

                                                    return $code;
                                                })
                                                ->required()
                                                ->live(debounce: 300)
                                                ->columnSpan(1),
                                            Select::make('status')
                                                ->label('Trạng thái')
                                                ->options(ContractStatus::class)
                                                ->default(ContractStatus::Draft)
                                                ->required()
                                                ->live()
                                                ->columnSpan(1),
                                        ]),
                                        Select::make('customer_id')
                                            ->label('Khách hàng / Đối tác (Bên A)')
                                            ->relationship('customer', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->createOptionForm(fn (Schema $schema) => CustomerForm::configure($schema))
                                            ->createOptionModalHeading('Thêm khách hàng mới')
                                            ->live(),
                                        Grid::make(2)->schema([
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
                                                            $set('title', 'Hợp đồng dịch vụ cho thuê màn hình LED: '.$quo->event_name);
                                                        }
                                                    }
                                                })
                                                ->columnSpan(1),
                                            Select::make('order_id')
                                                ->label('Đơn hàng liên kết')
                                                ->relationship('order', 'order_no')
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->columnSpan(1),
                                        ]),
                                        Select::make('sales_user_id')
                                            ->label('Đại diện kinh doanh (Bên B)')
                                            ->relationship('salesUser', 'name')
                                            ->default(fn () => Auth::id())
                                            ->searchable()
                                            ->preload()
                                            ->live(),
                                        TextInput::make('title')
                                            ->label('Tên hợp đồng / Sự kiện')
                                            ->placeholder('VD: Hợp đồng cho thuê màn hình LED phục vụ sự kiện VinFast')
                                            ->live(debounce: 300)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Giá trị hợp đồng, Đặt cọc & Thời hạn')
                                    ->description('Giá trị thanh toán, tỷ lệ đặt cọc và ngày ký kết')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('contract_value')
                                                ->label('Tổng giá trị (đ)')
                                                ->required()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->numeric()
                                                ->suffix(' đ')
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $val = (float) str_replace(',', '', (string) $state);
                                                    $pct = (float) ($get('deposit_percent') ?: 50);
                                                    $set('deposit_amount', round($val * ($pct / 100)));
                                                }),
                                            TextInput::make('deposit_percent')
                                                ->label('Đặt cọc (%)')
                                                ->numeric()
                                                ->suffix('%')
                                                ->default(50)
                                                ->live(debounce: 300)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $val = (float) str_replace(',', '', (string) $get('contract_value'));
                                                    $pct = (float) $state;
                                                    $set('deposit_amount', round($val * ($pct / 100)));
                                                }),
                                            TextInput::make('deposit_amount')
                                                ->label('Tiền cọc (đ)')
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->numeric()
                                                ->suffix(' đ')
                                                ->default(0)
                                                ->live(debounce: 300),
                                        ]),
                                        Grid::make(3)->schema([
                                            DatePicker::make('signed_date')
                                                ->label('Ngày ký hợp đồng')
                                                ->native(false)
                                                ->default(now())
                                                ->live(),
                                            DatePicker::make('start_date')
                                                ->label('Ngày bắt đầu thuê')
                                                ->native(false)
                                                ->live(),
                                            DatePicker::make('end_date')
                                                ->label('Ngày kết thúc thuê')
                                                ->native(false)
                                                ->live(),
                                        ]),
                                        Textarea::make('terms')
                                            ->label('Điều khoản bổ sung / Cam kết đặc biệt')
                                            ->placeholder('VD: Bên B hỗ trợ kỹ thuật trực đêm; Thiết bị bổ sung tính theo biểu giá thỏa thuận...')
                                            ->rows(3)
                                            ->live(debounce: 400)
                                            ->columnSpanFull(),
                                        Textarea::make('note')
                                            ->label('Ghi chú nội bộ')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Live Contract Preview (7 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 7])
                            ->schema([
                                Section::make('Văn bản Hợp đồng & Phụ lục (Live Preview)')
                                    ->description('Xem trước toàn bộ văn bản Hợp đồng dịch vụ, Phụ lục báo giá và Biên bản nghiệm thu theo thời gian thực')
                                    ->collapsible()
                                    ->schema([
                                        TextEntry::make('contract_live_preview')
                                            ->hiddenLabel()
                                            ->state(fn (Get $get, ?Contract $record) => view('filament.contracts.contract-live-preview', [
                                                'get' => $get,
                                                'record' => $record,
                                            ])),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
