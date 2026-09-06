<?php

namespace App\Console\Commands;

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Models\Asset;
use App\Models\RepairLog;
use Illuminate\Console\Command;

class CheckMaintenanceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:check-reminders {--threshold=10 : Số sự kiện để cảnh báo} {--days=90 : Số ngày không bảo trì}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và cảnh báo các thiết bị cần bảo trì định kỳ theo số lần đi sự kiện hoặc số ngày';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $eventThreshold = (int) $this->option('threshold');
        $daysThreshold = (int) $this->option('days');
        $cutoffDate = now()->subDays($daysThreshold);

        $this->info("Đang quét thiết bị cần bảo trì (Ngưỡng: {$eventThreshold} sự kiện hoặc không bảo trì > {$daysThreshold} ngày)...");

        $assets = Asset::where('current_status', '!=', AssetStatus::Disposed)
            ->with(['productLine'])
            ->get();

        $flaggedCount = 0;

        foreach ($assets as $asset) {
            // Count completed event checkouts
            $eventCount = $asset->checkoutBatchItems()->count();

            // Find last repair date
            $lastRepair = $asset->repairLogs()->latest('start_date')->first();
            $lastRepairDate = $lastRepair?->start_date ?? $asset->purchase_date;

            $needsMaintenance = false;
            $reasons = [];

            if ($eventCount >= $eventThreshold) {
                $needsMaintenance = true;
                $reasons[] = "Đã qua {$eventCount} lượt sự kiện (ngưỡng: {$eventThreshold})";
            }

            if ($lastRepairDate && $lastRepairDate < $cutoffDate) {
                $days = (int) $lastRepairDate->diffInDays(now());
                $needsMaintenance = true;
                $reasons[] = "Không bảo trì trong {$days} ngày (ngưỡng: {$daysThreshold} ngày)";
            }

            if ($needsMaintenance) {
                // Check if already has an open pending repair log
                $hasPendingRepair = $asset->repairLogs()
                    ->where('result_status', RepairResultStatus::Pending)
                    ->exists();

                if (! $hasPendingRepair) {
                    RepairLog::create([
                        'asset_id' => $asset->id,
                        'start_date' => now()->toDateString(),
                        'repair_note' => 'Nhắc nhở bảo trì định kỳ tự động: '.implode('; ', $reasons),
                        'result_status' => RepairResultStatus::Pending,
                        'created_by' => null,
                    ]);
                    $flaggedCount++;
                    $this->line("⚠️ Thiết bị [{$asset->serial_no}] ({$asset->productLine?->name}): ".implode('; ', $reasons));
                }
            }
        }

        $this->info("Hoàn tất quét bảo trì. Đã tạo {$flaggedCount} phiếu nhắc bảo trì mới.");

        return Command::SUCCESS;
    }
}
