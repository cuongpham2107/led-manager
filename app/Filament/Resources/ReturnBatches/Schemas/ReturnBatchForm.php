<?php

namespace App\Filament\Resources\ReturnBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReturnBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin đợt nhập trả sau sự kiện')
                    ->description('Quản lý thu hồi và phân loại kiểm tra (Grading) thiết bị sau sự kiện')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('code')
                                ->label('Mã đợt trả hàng')
                                ->required()
                                ->placeholder('VD: RET-2608-01'),
                            Select::make('checkout_batch_id')
                                ->label('Theo đợt xuất kho')
                                ->relationship('checkoutBatch', 'code')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('status')
                                ->label('Trạng thái thu hồi')
                                ->options([
                                    'pending' => 'Chờ nhận hàng (Pending)',
                                    'in_progress' => 'Đang phân loại kiểm tra (In Progress)',
                                    'completed' => 'Đã hoàn tất nhập kho (Completed)',
                                ])
                                ->required()
                                ->default('pending'),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('return_date')
                                ->label('Ngày trả thực tế')
                                ->native(false),
                            Select::make('created_by')
                                ->label('Người tiếp nhận')
                                ->relationship('creator', 'name')
                                ->searchable()
                                ->preload(),
                            DateTimePicker::make('completed_at')
                                ->label('Thời gian hoàn thành')
                                ->native(false),
                        ]),
                        Textarea::make('note')
                            ->label('Ghi chú tình trạng lô hàng trả về')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
