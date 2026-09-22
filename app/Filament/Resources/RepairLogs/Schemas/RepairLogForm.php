<?php

namespace App\Filament\Resources\RepairLogs\Schemas;

use App\Enums\AssetStatus;
use App\Models\RepairLog;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

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
                        Grid::make(3)
                            ->columns(fn (string $operation): int => $operation === 'create' ? 2 : 3)
                            ->schema([
                                Select::make('asset_id')
                                    ->label('Thiết bị (Serial No)')
                                    ->relationship('asset', 'serial_no', modifyQueryUsing: function ($query, string $operation, ?RepairLog $record = null) {
                                        $user = Auth::user();
                                        if ($whId = ($user instanceof User ? $user->getScopedWarehouseId() : null)) {
                                            $query->where('current_warehouse_id', $whId);
                                        }

                                        $query->where(function ($sub) use ($operation, $record) {
                                            $sub->where(function ($q) use ($operation, $record) {
                                                $q->where('current_status', '!=', AssetStatus::Repairing)
                                                    ->where('current_status', '!=', AssetStatus::Disposed)
                                                    ->whereDoesntHave('repairLogs', function ($rLogQ) use ($operation, $record) {
                                                        $rLogQ->when($operation === 'edit' && $record?->id, fn ($s) => $s->where('id', '!=', $record->id))
                                                            ->where('result_status', 'pending');
                                                    });
                                            });

                                            if ($operation === 'edit' && $record?->asset_id) {
                                                $sub->orWhere('id', $record->asset_id);
                                            }
                                        });
                                    })
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
                                    ->default('pending')
                                    ->hiddenOn('create')
                                    ->dehydratedWhenHidden(),
                                TextInput::make('repair_cost')
                                    ->label('Chi phí sửa chữa linh kiện')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->suffix(' đ')
                                    ->placeholder('0'),
                            ]),
                        Grid::make(3)
                            ->columns(fn (string $operation): int => $operation === 'create' ? 2 : 3)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Ngày bắt đầu bảo dưỡng')
                                    ->displayFormat('d/m/Y')
                                    ->native(true)
                                    ->required()
                                    ->default(now()->toDateString()),
                                DatePicker::make('end_date')
                                    ->label('Ngày hoàn thành')
                                    ->displayFormat('d/m/Y')
                                    ->native(true),
                                Select::make('created_by')
                                    ->label('Kỹ thuật viên phụ trách')
                                    ->relationship('creator', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => Auth::id())
                                    ->hiddenOn('create')
                                    ->dehydratedWhenHidden(),
                            ]),
                        Textarea::make('repair_note')
                            ->label('Chi tiết lỗi & Phương án khắc phục')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
