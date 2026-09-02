<?php

namespace App\Filament\Resources\Assets\Actions;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\User;
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

class SendToMaintenanceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'send_to_maintenance';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('SendToMaintenance:Asset')
            ->label('Bảo trì / Sửa chữa')
            ->icon('heroicon-o-wrench-screwdriver')
            ->color('warning')
            ->visible(fn (Asset $record): bool => $record->current_status !== AssetStatus::Repairing && $record->current_status !== AssetStatus::Disposed)
            ->modalHeading(fn (Asset $record): string => "Gửi bảo trì thiết bị: {$record->serial_no}")
            ->modalDescription('Chuyển trạng thái thiết bị sang Bảo dưỡng / Sửa chữa và ghi nhận phiếu nhật ký kỹ thuật.')
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
                    ->label('Chi phí dự kiến / Linh kiện (nếu có)')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->suffix(' đ')
                    ->placeholder('0'),
                Textarea::make('repair_note')
                    ->label('Mô tả sự cố & Hạng mục kiểm tra')
                    ->placeholder('VD: Chết điểm ảnh LED, hỏng quạt tản nhiệt, kiểm tra jack nguồn...')
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Asset $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $oldStatus = $record->current_status;

                    $record->update([
                        'current_status' => AssetStatus::Repairing,
                    ]);

                    $repairLog = RepairLog::create([
                        'asset_id' => $record->id,
                        'start_date' => $data['start_date'] ?? now()->toDateString(),
                        'repair_note' => $data['repair_note'],
                        'result_status' => RepairResultStatus::Pending,
                        'repair_cost' => filled($data['repair_cost'] ?? null) ? (float) $data['repair_cost'] : null,
                        'created_by' => $data['created_by'] ?? Auth::id(),
                    ]);

                    AssetStatusLog::create([
                        'asset_id' => $record->id,
                        'from_status' => $oldStatus,
                        'to_status' => AssetStatus::Repairing,
                        'from_warehouse_id' => $record->current_warehouse_id,
                        'to_warehouse_id' => $record->current_warehouse_id,
                        'source_type' => RepairLog::class,
                        'source_id' => $repairLog->id,
                        'changed_by' => $data['created_by'] ?? Auth::id(),
                        'note' => 'Gửi bảo trì/sửa chữa: '.$data['repair_note'],
                        'created_at' => now(),
                    ]);
                });

                Notification::make()
                    ->title("Thiết bị [{$record->serial_no}] đã được chuyển sang diện bảo dưỡng / sửa chữa.")
                    ->success()
                    ->send();
            });
    }
}
