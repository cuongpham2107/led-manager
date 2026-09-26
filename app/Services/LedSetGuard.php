<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\CheckoutBatch;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quy tắc "đúng bộ": trong một phiếu xuất, các tấm cùng dòng sản phẩm phải cùng cấu hình LED.
 * Thiết bị không có cấu hình (VD đầu phát) không bị ràng buộc.
 */
class LedSetGuard
{
    /**
     * Cấu hình chuẩn của từng dòng = cấu hình chiếm đa số (hoà thì lấy cấu hình gặp trước).
     *
     * @param  iterable<Asset>  $assets
     * @return array<int, int> product_line_id => led_configuration_id
     */
    public static function referenceConfigurations(iterable $assets): array
    {
        return collect($assets)
            ->filter(fn (Asset $asset) => $asset->led_configuration_id && $asset->product_line_id)
            ->groupBy('product_line_id')
            ->map(fn ($group) => (int) $group->countBy('led_configuration_id')->sortDesc()->keys()->first())
            ->all();
    }

    /**
     * @param  iterable<Asset>  $assets
     * @return string|null Thông báo lỗi, hoặc null nếu hợp lệ.
     */
    public static function conflict(iterable $assets): ?string
    {
        $assets = (new Collection(collect($assets)->all()))->loadMissing(['ledConfiguration', 'productLine']);
        $reference = static::referenceConfigurations($assets);

        $odd = $assets->first(fn (Asset $asset) => isset($reference[$asset->product_line_id])
            && $asset->led_configuration_id
            && $asset->led_configuration_id !== $reference[$asset->product_line_id]);

        if (! $odd) {
            return null;
        }

        $expected = $assets->firstWhere('led_configuration_id', $reference[$odd->product_line_id])->ledConfiguration;

        return "Thiết bị {$odd->serial_no} thuộc cấu hình \"{$odd->ledConfiguration->label}\", "
            ."khác cấu hình \"{$expected->label}\" của dòng {$odd->productLine?->name} trong phiếu. "
            .'Các tấm cùng dòng phải cùng cấu hình để lắp đúng bộ.';
    }

    public static function conflictForBatch(CheckoutBatch $batch, Asset $candidate): ?string
    {
        $existing = Asset::query()
            ->whereIn('id', $batch->items()->select('asset_id'))
            ->whereKeyNot($candidate->id)
            ->get();

        return static::conflict($existing->push($candidate));
    }

    /**
     * Gán cấu hình của lô nhập cho các thiết bị chưa có cấu hình.
     *
     * @param  array<int, int>  $assetIds
     * @return string|null Thông báo lỗi nếu có thiết bị đã mang cấu hình khác.
     */
    public static function applyBatchConfiguration(?int $configurationId, array $assetIds): ?string
    {
        if (! $configurationId || $assetIds === []) {
            return null;
        }

        $mismatch = Asset::query()
            ->whereIn('id', $assetIds)
            ->whereNotNull('led_configuration_id')
            ->where('led_configuration_id', '!=', $configurationId)
            ->with('ledConfiguration')
            ->first();

        if ($mismatch) {
            return "Thiết bị {$mismatch->serial_no} đã thuộc cấu hình \"{$mismatch->ledConfiguration->label}\", "
                .'khác cấu hình của lô nhập này. Bỏ chọn thiết bị hoặc đổi cấu hình lô.';
        }

        Asset::query()->whereIn('id', $assetIds)->whereNull('led_configuration_id')
            ->update(['led_configuration_id' => $configurationId]);

        return null;
    }
}
