<?php

namespace App\Filament\Resources\Assets\Actions;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SendToMaintenanceBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'send_to_maintenance_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('SendToMaintenanceBulk:Asset')
            ->label('Gửi bảo trì / Sửa chữa')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('warning')
            ->modalHeading('Gửi bảo trì hàng loạt thiết bị')
            ->modalDescription('Chuyển trạng thái toàn bộ các thiết bị đã chọn sang "Bảo dưỡng / Sửa chữa" và tạo các phiếu nhật ký sửa chữa tương ứng.')
            ->modalWidth(Width::Large)
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('start_date')
                        ->label('Ngày bắt đầu bảo dưỡng')
                        ->default(now()->toDateString())
                        ->native(false)
                        ->required(),
                    Select::make('created_by')
                        ->label('Kỹ thuật viên phụ trách')
                        ->options(User::pluck('name', 'id'))
                        ->default(fn () => Auth::id())
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),
                TextInput::make('repair_cost')
                    ->label('Chi phí dự kiến mỗi thiết bị (nếu có)')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->placeholder('0'),
                Textarea::make('repair_note')
                    ->label('Mô tả sự cố & Lý do bảo dưỡng chung')
                    ->placeholder('VD: Bảo dưỡng định kỳ sau sự kiện, kiểm tra module LED, làm sạch hệ thống tản nhiệt...')
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Collection $records, array $data): void {
                $eligibleRecords = $records->filter(
                    fn (Asset $asset): bool => $asset->current_status !== AssetStatus::Repairing && $asset->current_status !== AssetStatus::Disposed
                );

                if ($eligibleRecords->isEmpty()) {
                    Notification::make()
                        ->title('Không có thiết bị nào hợp lệ để chuyển bảo trì')
                        ->body('Các thiết bị đã chọn có thể đang trong trạng thái sửa chữa hoặc đã thanh lý.')
                        ->warning()
                        ->send();

                    return;
                }

                $count = 0;

                DB::transaction(function () use ($eligibleRecords, $data, &$count): void {
                    $userId = $data['created_by'] ?? Auth::id();
                    $startDate = $data['start_date'] ?? now()->toDateString();
                    $cost = filled($data['repair_cost'] ?? null) ? (float) $data['repair_cost'] : null;
                    $note = $data['repair_note'];

                    foreach ($eligibleRecords as $asset) {
                        $oldStatus = $asset->current_status;

                        $asset->update([
                            'current_status' => AssetStatus::Repairing,
                        ]);

                        $repairLog = RepairLog::create([
                            'asset_id' => $asset->id,
                            'start_date' => $startDate,
                            'repair_note' => $note,
                            'result_status' => RepairResultStatus::Pending,
                            'repair_cost' => $cost,
                            'created_by' => $userId,
                        ]);

                        AssetStatusLog::create([
                            'asset_id' => $asset->id,
                            'from_status' => $oldStatus,
                            'to_status' => AssetStatus::Repairing,
                            'from_warehouse_id' => $asset->current_warehouse_id,
                            'to_warehouse_id' => $asset->current_warehouse_id,
                            'source_type' => RepairLog::class,
                            'source_id' => $repairLog->id,
                            'changed_by' => $userId,
                            'note' => 'Gửi bảo trì/sửa chữa (hàng loạt): '.$note,
                            'created_at' => now(),
                        ]);

                        $count++;
                    }
                });

                Notification::make()
                    ->title("Đã chuyển {$count} thiết bị sang diện bảo dưỡng / sửa chữa thành công.")
                    ->success()
                    ->send();
            });
    }
}
