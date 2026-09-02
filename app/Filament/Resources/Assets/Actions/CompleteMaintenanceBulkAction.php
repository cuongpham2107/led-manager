<?php

namespace App\Filament\Resources\Assets\Actions;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
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

class CompleteMaintenanceBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'complete_maintenance_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('CompleteMaintenanceBulk:Asset')
            ->label('Hoàn thành sửa chữa')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading('Hoàn thành bảo trì hàng loạt thiết bị')
            ->modalDescription('Cập nhật kết quả bảo dưỡng và chuyển toàn bộ các thiết bị đã chọn về trạng thái "Sẵn sàng" (hoặc "Thanh lý").')
            ->modalWidth(Width::Large)
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('end_date')
                        ->label('Ngày hoàn thành')
                        ->default(now()->toDateString())
                        ->native(false)
                        ->required(),
                    Select::make('result_status')
                        ->label('Kết quả bảo trì chung')
                        ->options([
                            'fixed' => 'Đã sửa xong — Sẵn sàng sử dụng (Ready)',
                            'disposed' => 'Hỏng nặng không thể sửa — Thanh lý (Disposed)',
                        ])
                        ->default('fixed')
                        ->required(),
                ]),
                TextInput::make('repair_cost')
                    ->label('Chi phí sửa chữa trung bình mỗi thiết bị (nếu có)')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->placeholder('0'),
                Textarea::make('repair_note')
                    ->label('Ghi chú hoàn thành chung')
                    ->placeholder('VD: Hoàn thành bảo dưỡng định kỳ, kiểm tra kỹ thuật đạt chuẩn, sẵn sàng xuất kho...')
                    ->rows(3),
            ])
            ->action(function (Collection $records, array $data): void {
                $repairingRecords = $records->filter(
                    fn (Asset $asset): bool => $asset->current_status === AssetStatus::Repairing
                );

                if ($repairingRecords->isEmpty()) {
                    Notification::make()
                        ->title('Không có thiết bị nào đang trong trạng thái "Bảo dưỡng / Sửa chữa"')
                        ->warning()
                        ->send();

                    return;
                }

                $count = 0;

                DB::transaction(function () use ($repairingRecords, $data, &$count): void {
                    $newStatus = ($data['result_status'] === 'disposed') ? AssetStatus::Disposed : AssetStatus::Ready;
                    $resultStatusEnum = ($data['result_status'] === 'disposed') ? RepairResultStatus::Disposed : RepairResultStatus::Fixed;
                    $endDate = $data['end_date'] ?? now()->toDateString();
                    $cost = filled($data['repair_cost'] ?? null) ? (float) $data['repair_cost'] : null;
                    $note = $data['repair_note'];
                    $userId = Auth::id();

                    foreach ($repairingRecords as $asset) {
                        $asset->update([
                            'current_status' => $newStatus,
                        ]);

                        $latestLog = RepairLog::where('asset_id', $asset->id)
                            ->where('result_status', RepairResultStatus::Pending)
                            ->latest()
                            ->first();

                        if ($latestLog) {
                            $latestLog->update([
                                'end_date' => $endDate,
                                'result_status' => $resultStatusEnum,
                                'repair_cost' => $cost ?? $latestLog->repair_cost,
                                'repair_note' => filled($note)
                                    ? ($latestLog->repair_note ? $latestLog->repair_note."\n[Hoàn thành]: ".$note : $note)
                                    : $latestLog->repair_note,
                            ]);
                        } else {
                            RepairLog::create([
                                'asset_id' => $asset->id,
                                'start_date' => $asset->updated_at?->toDateString() ?? $endDate,
                                'end_date' => $endDate,
                                'result_status' => $resultStatusEnum,
                                'repair_cost' => $cost,
                                'repair_note' => $note ?? 'Hoàn thành sửa chữa',
                                'created_by' => $userId,
                            ]);
                        }

                        AssetStatusLog::create([
                            'asset_id' => $asset->id,
                            'from_status' => AssetStatus::Repairing,
                            'to_status' => $newStatus,
                            'from_warehouse_id' => $asset->current_warehouse_id,
                            'to_warehouse_id' => $asset->current_warehouse_id,
                            'source_type' => RepairLog::class,
                            'source_id' => $latestLog?->id,
                            'changed_by' => $userId,
                            'note' => 'Hoàn thành bảo trì (hàng loạt): '.($note ?? ($newStatus === AssetStatus::Ready ? 'Đã sửa xong' : 'Thanh lý')),
                            'created_at' => now(),
                        ]);

                        $count++;
                    }
                });

                Notification::make()
                    ->title("Đã hoàn thành bảo trì cho {$count} thiết bị thành công.")
                    ->success()
                    ->send();
            });
    }
}
