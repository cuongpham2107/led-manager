<?php

namespace App\Filament\Resources\CheckinBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CheckinBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin đợt nhập kho')
                    ->description('Quản lý nhập mới thiết bị LED hoặc phụ kiện vào kho')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('code')
                                ->label('Mã đợt nhập')
                                ->required()
                                ->placeholder('VD: IN-2608-01'),
                            Select::make('warehouse_id')
                                ->label('Kho nhận hàng')
                                ->relationship('warehouse', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('status')
                                ->label('Trạng thái đợt nhập')
                                ->options([
                                    'pending' => 'Chờ nhận hàng (Pending)',
                                    'in_progress' => 'Đang quét nhập PDA (In Progress)',
                                    'completed' => 'Đã nhập kho hoàn tất (Completed)',
                                    'cancelled' => 'Đã hủy (Cancelled)',
                                ])
                                ->required()
                                ->default('pending'),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('expected_date')
                                ->label('Ngày dự kiến hàng về')
                                ->native(false),
                            Select::make('created_by')
                                ->label('Người tạo phiếu')
                                ->relationship('creator', 'name')
                                ->searchable()
                                ->preload(),
                            DateTimePicker::make('completed_at')
                                ->label('Thời gian hoàn thành nhập')
                                ->native(false),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú đợt nhập hàng')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
