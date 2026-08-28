<?php

namespace App\Console\Commands;

use App\Services\DepreciationService;
use Illuminate\Console\Command;

class CalculateDepreciation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:calculate-depreciation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tính toán trích khấu hao tài sản thiết bị định kỳ hàng tháng';

    /**
     * Execute the console command.
     */
    public function handle(DepreciationService $service): int
    {
        $this->info('Đang trích khấu hao tài sản định kỳ tháng...');

        $count = $service->processMonthlyDepreciation();

        $this->info("Đã hoàn tất trích khấu hao cho {$count} thiết bị.");

        return Command::SUCCESS;
    }
}
