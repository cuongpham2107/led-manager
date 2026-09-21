<?php

namespace App\Filament\Resources\RepairLogs\Actions;

use App\Enums\RepairResultStatus;
use App\Models\RepairLog;
use App\Services\MaintenanceService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;

/**
 * Đóng phiếu bảo dưỡng / sửa chữa NGAY TẠI trang "Bảo trì & Sửa chữa"
 * (/repair-logs) — nơi nghiệp vụ bảo trì thực sự diễn ra, thay vì phải mò sang
 * danh sách thiết bị.
 *
 * Action thao tác trên chính RepairLog đang xử lý và đồng bộ cả RepairLog lẫn
 * Asset (via MaintenanceService), nên không thể lệch trạng thái giữa hai bên.
 */
class CompleteRepairAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete_repair';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->authorize('CompleteMaintenance:Asset')
            ->label('Hoàn thành sửa chữa')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (RepairLog $record): bool => $record->result_status === RepairResultStatus::Pending)
            ->modalHeading(fn (RepairLog $record): string => 'Hoàn thành sửa chữa: '.$record->asset?->serial_no)
            ->modalDescription('Ghi nhận kết quả khắc phục. Thiết bị sẽ được đưa về kho sẵn sàng, hoặc chuyển sang thanh lý nếu hỏng nặng không thể sửa.')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Xác nhận hoàn thành')
            ->form([
                Grid::make(2)->schema([
                    DatePicker::make('end_date')
                        ->label('Ngày hoàn thành')
                        ->default(now()->toDateString())
                        ->displayFormat('d/m/Y')
                        ->native(true)
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
            ->action(function (RepairLog $record, array $data): void {
                app(MaintenanceService::class)->complete($record, $data);

                Notification::make()
                    ->title("Đã hoàn thành sửa chữa thiết bị [{$record->asset?->serial_no}].")
                    ->success()
                    ->send();
            });
    }
}
