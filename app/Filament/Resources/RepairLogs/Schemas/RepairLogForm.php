<?php

namespace App\Filament\Resources\RepairLogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class RepairLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nhật ký sửa chữa & bảo dưỡng thiết bị')
                    ->description('Theo dõi lỗi kỹ thuật, chi phí thay thế linh kiện và trạng thái xử lý')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('asset_id')
                                ->label('Thiết bị (Serial No)')
                                ->relationship('asset', 'serial_no')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('result_status')
                                ->label('Kết quả sửa chữa')
                                ->options([
                                    'pending' => 'Đang chờ sửa / Đang xử lý (Pending)',
                                    'fixed' => 'Đã sửa xong - Sẵn sàng sử dụng (Fixed)',
                                    'disposed' => 'Hỏng nặng không thể sửa - Thanh lý (Disposed)',
                                ])
                                ->default('pending'),
                            TextInput::make('repair_cost')
                                ->label('Chi phí sửa chữa linh kiện')
                                ->mask(RawJs::make('$money($input)'))
                                ->stripCharacters(',')
                                ->numeric()
                                ->suffix(' đ')
                                ->placeholder('0'),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label('Ngày bắt đầu bảo dưỡng')
                                ->native(false)
                                ->required(),
                            DatePicker::make('end_date')
                                ->label('Ngày hoàn thành')
                                ->native(false),
                            Select::make('created_by')
                                ->label('Kỹ thuật viên phụ trách')
                                ->relationship('creator', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                        Textarea::make('repair_note')
                            ->label('Chi tiết lỗi & Phương án khắc phục')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
