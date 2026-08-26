<?php

namespace App\Filament\Resources\ReturnBatches\Schemas;

use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

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
                                ->options(ReturnBatchStatus::class)
                                ->required()
                                ->default(ReturnBatchStatus::Pending),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('return_date')
                                ->label('Ngày trả thực tế')
                                ->default(now()->toDateString())
                                ->native(false),
                            Select::make('created_by')
                                ->label('Người tiếp nhận')
                                ->relationship('creator', 'name')
                                ->default(fn () => Auth::id())
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

                Section::make('Danh sách thiết bị kiểm đếm hoàn trả (Grading & Quality Check)')
                    ->description('Quét mã QR và chấm điểm phân loại tình trạng vật lý từng thiết bị')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Thiết bị hoàn trả')
                            ->table([
                                TableColumn::make('Mã Serial / Thiết bị'),
                                TableColumn::make('Phân loại chất lượng (Grade)'),
                                TableColumn::make('Hoạt động tốt'),
                                TableColumn::make('Ghi chú lỗi hỏng'),
                            ])
                            ->schema([
                                Select::make('asset_id')
                                    ->label('Thiết bị')
                                    ->relationship('asset', 'serial_no')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('grade')
                                    ->label('Phân loại')
                                    ->options(ReturnGrade::class)
                                    ->default(ReturnGrade::Normal)
                                    ->required(),
                                Toggle::make('is_received')
                                    ->label('Đã nhận kho')
                                    ->default(true),
                                TextInput::make('grade_note')
                                    ->label('Ghi chú')
                                    ->placeholder('Mô tả hỏng hóc nếu có...'),
                            ])
                            ->addActionLabel('+ Quét / Thêm thiết bị trả về')
                            ->collapsible(false)
                            ->reorderable(false),
                    ]),
            ]);
    }
}
