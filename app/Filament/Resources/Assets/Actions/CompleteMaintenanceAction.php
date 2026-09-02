<?php

namespace App\Filament\Resources\Assets\Actions;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompleteMaintenanceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete_maintenance';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('CompleteMaintenance:Asset')
            ->label('Hoàn thành sửa chữa')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Asset $record): bool => $record->current_status === AssetStatus::Repairing)
            ->modalHeading(fn (Asset $record): string => "Hoàn thành bảo trì thiết bị: {$record->serial_no}")
            ->modalDescription('Cập nhật kết quả bảo dưỡng và đưa thiết bị về kho sẵn sàng (hoặc thanh lý nếu hỏng nặng).')
            ->modalWidth(Width::Large)
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('end_date')
                        ->label('Ngày hoàn thành')
                        ->default(now()->toDateString())
                        ->native(false)
                        ->required(),
                    Select::make('result_status')
                        ->label('Kết quả bảo trì')
                        ->options([
                            'fixed' => 'Đã sửa xong — Sẵn sàng sử dụng (Ready)',
                            'disposed' => 'Hỏng nặng không thể sửa — Thanh lý (Disposed)',
                        ])
                        ->default('fixed')
                        ->required(),
                ]),
                TextInput::make('repair_cost')
                    ->label('Tổng chi phí sửa chữa / Linh kiện thực tế')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->placeholder('0'),
                Textarea::make('repair_note')
                    ->label('Ghi chú hoàn thành / Nội dung đã khắc phục')
                    ->placeholder('VD: Đã thay thế module LED, kiểm tra nguồn điện ổn định, chạy test 2h...')
                    ->rows(3),
            ])
            ->action(function (Asset $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $newStatus = ($data['result_status'] === 'disposed') ? AssetStatus::Disposed : AssetStatus::Ready;
                    $resultStatusEnum = ($data['result_status'] === 'disposed') ? RepairResultStatus::Disposed : RepairResultStatus::Fixed;

                    $record->update([
                        'current_status' => $newStatus,
                    ]);

                    // Update latest pending repair log if exists
                    $latestLog = RepairLog::where('asset_id', $record->id)
                        ->where('result_status', RepairResultStatus::Pending)
                        ->latest()
                        ->first();

                    $cost = filled($data['repair_cost'] ?? null) ? (float) $data['repair_cost'] : null;

                    if ($latestLog) {
                        $latestLog->update([
                            'end_date' => $data['end_date'] ?? now()->toDateString(),
                            'result_status' => $resultStatusEnum,
                            'repair_cost' => $cost ?? $latestLog->repair_cost,
                            'repair_note' => filled($data['repair_note'] ?? null)
                                ? ($latestLog->repair_note ? $latestLog->repair_note."\n[Hoàn thành]: ".$data['repair_note'] : $data['repair_note'])
                                : $latestLog->repair_note,
                        ]);
                    } else {
                        RepairLog::create([
                            'asset_id' => $record->id,
                            'start_date' => $record->updated_at?->toDateString() ?? now()->toDateString(),
                            'end_date' => $data['end_date'] ?? now()->toDateString(),
                            'result_status' => $resultStatusEnum,
                            'repair_cost' => $cost,
                            'repair_note' => $data['repair_note'] ?? 'Hoàn thành sửa chữa',
                            'created_by' => Auth::id(),
                        ]);
                    }

                    AssetStatusLog::create([
                        'asset_id' => $record->id,
                        'from_status' => AssetStatus::Repairing,
                        'to_status' => $newStatus,
                        'from_warehouse_id' => $record->current_warehouse_id,
                        'to_warehouse_id' => $record->current_warehouse_id,
                        'source_type' => RepairLog::class,
                        'source_id' => $latestLog?->id,
                        'changed_by' => Auth::id(),
                        'note' => 'Hoàn thành bảo trì/sửa chữa: '.($data['repair_note'] ?? ($newStatus === AssetStatus::Ready ? 'Đã sửa xong' : 'Thanh lý')),
                        'created_at' => now(),
                    ]);
                });

                Notification::make()
                    ->title("Đã hoàn thành bảo trì cho thiết bị [{$record->serial_no}].")
                    ->success()
                    ->send();
            });
    }
}
