# Cấu hình LED & xuất hàng "đúng bộ"

## Bối cảnh
Các tấm LED ghép thành một màn phải cùng cấu hình kỹ thuật; lệch một tấm là không lắp được bộ. Mỗi lô nhập về có thể là một cấu hình khác. Xếp yêu cầu:
- Danh mục thiết bị có thêm: card nhận, kiểu quét, đầu phát.
- Khi xuất: nhập yêu cầu (card nhận, kiểu quét, đầu phát) + kích thước màn → hệ thống gợi ý đúng tấm và số lượng.

## Quyết định đã chốt
| Vấn đề | Quyết định |
|---|---|
| Đầu phát | Cả hai: tấm ghi loại đầu phát tương thích; đầu phát cũng là thiết bị có serial, xuất kèm |
| "Tần số quét" | Kiểu quét (1/8, 1/16, 1/32 scan) |
| Khác lô cùng cấu hình | Ưu tiên cùng lô; thiếu thì cho ghép lô khác kèm cảnh báo |
| Kích thước khi xuất | Rộng × cao (m); m² tự tính |
| Giá thuê | Chỉ theo dòng sản phẩm, không theo cấu hình |
| Mô hình | Danh mục `led_configurations` nằm dưới dòng sản phẩm (phương án B) |

## Mô hình dữ liệu
Sửa migration tại chỗ (quy ước dự án), reset bằng `php artisan app:bootstrap`.

- **`led_configurations`** (mới): `id`, `product_line_id` (FK), `name` (tên hiển thị), `receiving_card` (VD "Novastar A5s Plus"), `scan_mode` (enum `LedScanMode`: 1/4, 1/8, 1/16, 1/32, static), `controller_model` (VD "Novastar VX600"), `note`, `is_active`, timestamps. Unique (`product_line_id`, `receiving_card`, `scan_mode`, `controller_model`).
- **`product_lines.type`**: enum `ProductLineType` = `panel` (Tấm LED, mặc định) | `controller` (Đầu phát).
- **`assets.led_configuration_id`**: nullable FK. Tấm LED nên có; đầu phát để trống.
- **`checkin_batches.led_configuration_id`**: nullable FK — cấu hình cho cả lô; khi tạo đợt nhập, thiết bị được chọn mà chưa có cấu hình sẽ nhận cấu hình này.
- **`order_items.led_configuration_id`**: nullable FK — cấu hình khách chốt cho dòng BOM đó.
- **Lô** của một tấm = đợt nhập kho gần nhất chứa tấm đó (`checkin_batch_items`). Không thêm cột.

## Quy tắc "đúng bộ"
Trong một phiếu xuất, **các tấm cùng dòng sản phẩm phải cùng một cấu hình LED**. Cấu hình của phiếu cho một dòng = cấu hình của tấm đầu tiên thuộc dòng đó đã có trong phiếu. Đầu phát và thiết bị không có cấu hình không bị ràng buộc.

## PanelSuggestionService (mới)
`suggest(ProductLine $line, int $warehouseId, float $widthM, float $heightM, array $filters = [], ?int $excludeBatchId = null): array`
1. `cols = ceil(width*1000 / module_width_mm)`, `rows = ceil(height*1000 / module_height_mm)`, `required = cols*rows`; thiếu kích thước module thì dùng 500×500.
2. Lấy các cấu hình hoạt động của dòng khớp `filters` (`receiving_card`, `scan_mode`, `controller_model`).
3. Với mỗi cấu hình: đếm tấm `Ready` trong kho, chưa bị giữ bởi phiếu xuất đang mở (cùng điều kiện đang dùng ở `CreateCheckoutBatchAction`).
4. Trả về `required`, `cols`, `rows`, `area_m2`, và `options[]` mỗi phần tử: `configuration`, `available`, `is_enough`, `lots` (số tấm theo lô). Sắp: đủ trước, rồi tồn nhiều hơn.
5. `pickAssets(LedConfiguration $config, int $warehouseId, int $qty, array $excludeIds = []): array{assets, mixed_lots: bool}` — ưu tiên lô có nhiều tấm nhất (để 1 lô phủ hết), trong lô sắp theo serial.

## Luồng người dùng
- **Cấu hình LED** (menu Hệ thống): CRUD.
- **Dòng sản phẩm**: thêm trường Loại.
- **Danh mục thiết bị**: form + bảng + bộ lọc có Cấu hình LED; import Excel có cột "Cấu hình LED" (khớp theo tên).
- **Nhập kho**: form tạo đợt có ô "Cấu hình LED cho cả lô" (lọc theo dòng nếu đã chọn); danh sách gợi ý thiết bị lọc theo cấu hình.
- **Đơn hàng**: mỗi dòng BOM có ô "Cấu hình LED" (tuỳ chọn, lọc theo dòng SP).
- **Tạo Đợt Xuất Kho** (trên đơn hàng):
  - Với mỗi dòng BOM loại tấm: nếu chưa chốt cấu hình thì người dùng chọn cấu hình từ bảng gợi ý (lọc card/kiểu quét/đầu phát, rộng × cao). Chốt xong ghi ngược vào `order_items.led_configuration_id`.
  - Tự gán chỉ lấy tấm đúng cấu hình; **bỏ phần "lấy bù" bằng thiết bị khác dòng**. Thiếu thì báo thiếu.
  - Dòng BOM loại đầu phát: gán theo dòng sản phẩm như hiện nay.
  - Ghép lô → thông báo cảnh báo.
- **Phiếu xuất → Thêm từ kho**: API tìm kiếm nhận `led_configuration_id`; thêm tấm lệch cấu hình → 422.
- **PDA quét xuất**: `CheckoutBatchApiController::scan` từ chối tấm lệch cấu hình (422, thông báo nêu cấu hình của phiếu). Mobile hiện cấu hình trên thẻ thiết bị và trang tra cứu.

## Ngoài phạm vi
Báo giá, bảng giá, luồng thu hồi, sửa chữa, báo cáo (trừ việc hiện cấu hình ở danh sách thiết bị). Đổi cấu hình khi sửa chữa: sửa tay ở form thiết bị.

## Kiểm thử
- `PanelSuggestionService`: tính số tấm; lọc; sắp đủ trước; ưu tiên cùng lô; cờ ghép lô; loại tấm đang bị giữ.
- `CreateCheckoutBatchAction`: không trộn cấu hình/dòng; thiếu thì không lấy bù.
- Quét API + dispatch item web: chặn lệch cấu hình.
- Nhập kho: gán cấu hình lô cho thiết bị chưa có cấu hình.
