---
paths:
  - 'app/**/*Return*'
---

# App

## Return flows must reuse an open RepairLog, never create a second one
A damaged return item is graded first (web ReturnAssetController::receiveItem or mobile ReturnBatchApiController scan) and then ReturnBatch::complete() runs over the same items. Each step used RepairLog::create, so one fault produced 2 repair tickets. Always use RepairLog::firstOrCreate(['asset_id' => $id, 'end_date' => null], [...]) so an open ticket is reused. Covered by tests/Feature/ReturnBatchReceivingActionTest.php and tests/Feature/Api/ReturnBatchApiTest.php.
