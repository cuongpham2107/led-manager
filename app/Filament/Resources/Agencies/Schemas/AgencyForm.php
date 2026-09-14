<?php

namespace App\Filament\Resources\Agencies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin Đại lý tỉnh')
                    ->description('Cấu hình định danh, tỉnh thành, người đại diện và kho hàng liên kết')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Tên đại lý')
                                ->placeholder('VD: Đại lý Hải Phòng')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('code')
                                ->label('Mã đại lý')
                                ->placeholder('VD: DL-HP')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('province')
                                ->label('Tỉnh / Thành phố')
                                ->placeholder('VD: Hải Phòng')
                                ->maxLength(100),
                            TextInput::make('contact_person')
                                ->label('Người đại diện / Phụ trách')
                                ->placeholder('VD: Nguyễn Văn A')
                                ->maxLength(150),
                            TextInput::make('phone')
                                ->label('Số điện thoại liên hệ')
                                ->tel()
                                ->maxLength(30),
                        ]),
                        TextInput::make('address')
                            ->label('Địa chỉ văn phòng / Điểm giao dịch')
                            ->placeholder('Số nhà, đường, phường, quận...'),
                        Select::make('warehouse_id')
                            ->label('Kho hàng trực thuộc đại lý')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('— Chưa gán kho riêng —')
                            ->helperText('Kho lưu trữ thiết bị LED thực tế của đại lý tại tỉnh'),
                    ]),

                Section::make('Chính sách & Hạn mức hợp tác')
                    ->description('Tỷ lệ % hoa hồng ăn theo doanh số và định mức diện tích 1.000m²')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('commission_rate')
                                ->label('Tỷ lệ hoa hồng (% ăn chia)')
                                ->numeric()
                                ->suffix('%')
                                ->default(15.00)
                                ->required()
                                ->helperText('Tỷ lệ hoa hồng được hưởng tính trên số tiền thực thu của đơn hàng'),
                            TextInput::make('allocated_area_m2')
                                ->label('Định mức diện tích LED bàn giao')
                                ->numeric()
                                ->suffix(' m²')
                                ->default(1000.00)
                                ->required()
                                ->helperText('Tổng diện tích thiết bị tối đa được bàn giao quản lý (VD: 1.000 m²)'),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú hợp đồng hợp tác')
                            ->rows(3)
                            ->placeholder('Các điều khoản thỏa thuận bổ sung...'),
                        Toggle::make('is_active')
                            ->label('Đang hoạt động')
                            ->default(true),
                    ]),
            ]);
    }
}
