---
paths:
  - 'database/seeders/**,tests/**,app/Services/**'
---

# Services

## Seeded draft order swallows all P2.6 stock in the Hai Phong agency warehouse
LedOsDataSeeder creates ORD-DL-HP-02 as a Draft with quantity_required = 120 of P2.6 for warehouse WH-HP, whose window is now()+4 -> now()+6 days at seed time. AvailabilityService::getAvailableCount clamps the booking with min(reqQty, totalStock), so that one order consumes the ENTIRE P2.6 stock of that warehouse while its window overlaps.
Consequence: anything checking availability of P2.6 at WH-HP for a date range touching that window sees available = 0 and gets "Thiếu thiết bị khả dụng trong khoảng ngày đã chọn". This made the E2E flaky depending on the day it ran. Book far-future or non-overlapping windows in tests, or reseed.
