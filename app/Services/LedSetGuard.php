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
     * @param  iterable<Asset>  $assets
     * @return string|null Thông báo lỗi, hoặc null nếu hợp lệ.
     */
    public static function conflict(iterable $assets): ?string
    {
        /** @var array<int, Asset> $firstByLine */
        $firstByLine = [];

        foreach ((new Collection(collect($assets)->all()))->loadMissing(['ledConfiguration', 'productLine']) as $asset) {
            if (! $asset->led_configuration_id || ! $asset->product_line_id) {
                continue;
            }

            $first = $firstByLine[$asset->product_line_id] ??= $asset;

            if ($first->led_configuration_id !== $asset->led_configuration_id) {
                return "Thiết bị {$asset->serial_no} thuộc cấu hình \"{$asset->ledConfiguration->label}\", "
                    ."khác cấu hình \"{$first->ledConfiguration->label}\" của dòng {$asset->productLine?->name} trong phiếu. "
                    .'Các tấm cùng dòng phải cùng cấu hình để lắp đúng bộ.';
            }
        }

        return null;
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
