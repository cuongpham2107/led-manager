<?php

namespace App\Filament\Resources\ReturnBatches\Schemas;

use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\CheckoutBatch;
use App\Models\ReturnBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ReturnBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->columnSpanFull()
                    ->schema([

                        // ================= LEFT COLUMN: Return Batch Info (5 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 5])
                            ->schema([
                                Section::make('Thông tin đợt nhập trả sau sự kiện')
                                    ->description('Quản lý thu hồi và phân loại kiểm tra (Grading) thiết bị sau sự kiện')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('code')
                                                ->label('Mã đợt trả hàng')
                                                ->required()
                                                ->default(function () {
                                                    $batchCount = ReturnBatch::count() + 1;
                                                    $code = 'RET-'.date('ym').'-'.str_pad((string) $batchCount, 2, '0', STR_PAD_LEFT);
                                                    while (ReturnBatch::where('code', $code)->exists()) {
                                                        $batchCount++;
                                                        $code = 'RET-'.date('ym').'-'.str_pad((string) $batchCount, 2, '0', STR_PAD_LEFT);
                                                    }

                                                    return $code;
                                                })
                                                ->placeholder('VD: RET-2608-01'),

                                            Select::make('checkout_batch_id')
                                                ->label('Theo đợt xuất kho')
                                                ->relationship('checkoutBatch', 'code')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, $set) {
                                                    if (! $state) {
                                                        return;
                                                    }

                                                    $checkoutBatch = CheckoutBatch::with('items')->find($state);
                                                    if (! $checkoutBatch) {
                                                        return;
                                                    }

                                                    $items = [];
                                                    foreach ($checkoutBatch->items as $cbItem) {
                                                        $items[] = [
                                                            'asset_id' => $cbItem->asset_id,
                                                            'checkout_batch_item_id' => $cbItem->id,
                                                            'grade' => ReturnGrade::Normal->value,
                                                            'is_received' => true,
                                                            'grade_note' => null,
                                                        ];
                                                    }
                                                    $set('items', $items);
                                                }),
                                        ]),

                                        Grid::make(2)->schema([
                                            Select::make('status')
                                                ->label('Trạng thái thu hồi')
                                                ->options(ReturnBatchStatus::class)
                                                ->required()
                                                ->default(ReturnBatchStatus::Completed),

                                            DatePicker::make('return_date')
                                                ->label('Ngày trả thực tế')
                                                ->default(now()->toDateString())
                                                ->native(false)
                                                ->required(),
                                        ]),

                                        Grid::make(2)->schema([
                                            Select::make('created_by')
                                                ->label('Người tiếp nhận')
                                                ->relationship('creator', 'name')
                                                ->default(fn () => Auth::id())
                                                ->searchable()
                                                ->preload()
                                                ->required(),

                                            DateTimePicker::make('completed_at')
                                                ->label('Thời gian hoàn thành')
                                                ->default(now())
                                                ->native(false),
                                        ]),

                                        Textarea::make('note')
                                            ->label('Ghi chú tình trạng lô hàng trả về')
                                            ->placeholder('Ghi nhận chung tình trạng thiết bị khi thu hồi...')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ================= RIGHT COLUMN: Grading & Quality Check (7 cols) =================
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 7])
                            ->schema([
                                Section::make('Danh sách thiết bị kiểm đếm hoàn trả (Grading & Quality Check)')
                                    ->description('Quét mã QR/chọn thiết bị và chấm điểm phân loại tình trạng vật lý từng thiết bị')
                                    ->collapsible()
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->label('Thiết bị hoàn trả')
                                            ->table([
                                                TableColumn::make('Mã Serial / Thiết bị'),
                                                TableColumn::make('Phân loại chất lượng (Grade)'),
                                                TableColumn::make('Đã nhận kho'),
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
                                                    ->label('Đã nhận')
                                                    ->default(true),
                                                TextInput::make('grade_note')
                                                    ->label('Ghi chú')
                                                    ->placeholder('Mô tả hỏng hóc nếu có...'),
                                            ])
                                            ->addActionLabel('+ Quét / Thêm thiết bị trả về')
                                            ->collapsible(false)
                                            ->reorderable(false),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
