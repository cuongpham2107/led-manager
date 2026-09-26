---
paths:
  - 'app/Services/**'
  - 'app/Filament/Resources/Orders/**'
  - 'app/Filament/Resources/CheckoutBatches/**'
  - 'app/Filament/Resources/CheckinBatches/**'
  - 'app/Http/Controllers/**'
---

# LED configuration ("đúng bộ")

## Panels of one product line in a checkout batch must share one LedConfiguration
A LedConfiguration = receiving card + scan mode + compatible controller, scoped to a product line. Mixing configurations means the screen cannot be assembled.
Every path that adds an asset to a checkout batch must call `App\Services\LedSetGuard` (`conflict()` / `conflictForBatch()`): Filament create/edit actions, `CheckoutAssetSearchController::dispatchItem`, `CheckoutBatchApiController::scan`. Add the guard to any new path.
Assets without a configuration (e.g. controllers) are not constrained.

## Auto-assign never tops up with other lines/configurations
`CreateCheckoutBatchAction` picks through `PanelSuggestionService::pickAssets()` (prefers one lot; `mixed_lots` → warning). Shortage = warn, never fill from another product line or configuration.

## Check-in batch configuration
`checkin_batches.led_configuration_id` is applied to selected assets that have none via `LedSetGuard::applyBatchConfiguration()`; assets already carrying a different configuration block the save.

## Pricing stays per product line
Configuration never affects price, BOM availability or quotations.
