<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\CustomerType;
use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin khách hàng')
                    ->description('Thông tin định danh và phân loại khách hàng')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Tên khách hàng')
                                ->required()
                                ->placeholder('VD: Tập đoàn Vingroup'),
                            TextInput::make('code')
                                ->label('Mã khách hàng')
                                ->default(function () {
                                    $count = Customer::count() + 1;
                                    $code = 'CUS-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
                                    while (Customer::where('code', $code)->exists()) {
                                        $count++;
                                        $code = 'CUS-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
                                    }

                                    return $code;
                                })
                                ->placeholder('VD: CUS-001'),
                            Select::make('type')
                                ->label('Loại khách hàng')
                                ->options(CustomerType::class)
                                ->required()
                                ->default(CustomerType::Corporate),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('tax_code')
                                ->label('Mã số thuế')
                                ->placeholder('VD: 0101245486'),
                            Toggle::make('is_active')
                                ->label('Đang hoạt động')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),

                Section::make('Liên hệ & Địa chỉ')
                    ->description('Thông tin đầu mối liên hệ và địa chỉ giao dịch')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('contact_person')
                                ->label('Người liên hệ')
                                ->placeholder('VD: Nguyễn Văn A'),
                            TextInput::make('phone')
                                ->label('Số điện thoại')
                                ->tel()
                                ->placeholder('VD: 0912 345 678'),
                            TextInput::make('email')
                                ->label('Địa chỉ Email')
                                ->email()
                                ->placeholder('VD: contact@company.com'),
                        ]),
                        Textarea::make('address')
                            ->label('Địa chỉ')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('note')
                            ->label('Ghi chú')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
