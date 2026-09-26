<?php

namespace Database\Seeders;

use App\Enums\AssetStatus;
use App\Enums\LedScanMode;
use App\Enums\ProductLineType;
use App\Models\Asset;
use App\Models\CheckoutBatchItem;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * Cấu hình LED mẫu: mỗi dòng tấm có 2 cấu hình (A: Novastar, B: Colorlight) và một dòng đầu phát.
 * Tấm đang nằm trong phiếu xuất giữ cấu hình A để dữ liệu mẫu không bị lệch bộ.
 */
class LedConfigurationSeeder extends Seeder
{
    private const ALTERNATE_SHARE = 5; // ~1/5 số tấm rảnh chuyển sang cấu hình B

    public function run(): void
    {
        ProductLine::query()->where('type', ProductLineType::Panel)->each(function (ProductLine $line): void {
            $configA = LedConfiguration::updateOrCreate(
                ['product_line_id' => $line->id, 'receiving_card' => 'Novastar A5s Plus', 'scan_mode' => LedScanMode::Sixteenth, 'controller_model' => 'Novastar VX600'],
                ['name' => "{$line->code} · Novastar", 'is_active' => true],
            );
            $configB = LedConfiguration::updateOrCreate(
                ['product_line_id' => $line->id, 'receiving_card' => 'Colorlight 5A-75B', 'scan_mode' => LedScanMode::ThirtySecond, 'controller_model' => 'Colorlight X4'],
                ['name' => "{$line->code} · Colorlight", 'is_active' => true],
            );

            Asset::where('product_line_id', $line->id)->update(['led_configuration_id' => $configA->id]);

            $alternateIds = Asset::where('product_line_id', $line->id)
                ->where('current_status', AssetStatus::Ready)
                ->whereNotIn('id', CheckoutBatchItem::query()->select('asset_id'))
                ->orderBy('id')
                ->pluck('id')
                ->filter(fn (int $id, int $index) => $index % self::ALTERNATE_SHARE === 0);

            Asset::whereIn('id', $alternateIds)->update(['led_configuration_id' => $configB->id]);
        });

        $controllerLine = ProductLine::updateOrCreate(['code' => 'CTRL-VX600'], [
            'name' => 'Đầu phát Novastar VX600',
            'type' => ProductLineType::Controller,
            'brand' => 'Novastar',
            'is_active' => true,
        ]);

        Warehouse::query()->whereDoesntHave('agency')->where('is_active', true)->each(function (Warehouse $warehouse) use ($controllerLine): void {
            foreach ([1, 2] as $n) {
                Asset::updateOrCreate(['serial_no' => "VX600-{$warehouse->code}-0{$n}"], [
                    'product_line_id' => $controllerLine->id,
                    'size' => '1U Rack',
                    'current_status' => AssetStatus::Ready,
                    'current_warehouse_id' => $warehouse->id,
                    'manufactured_date' => now()->subMonths(6)->toDateString(),
                ]);
            }
        });
    }
}
