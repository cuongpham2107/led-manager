# Quy trình test tay: Đại lý nhập kho → bán hàng → thu hồi → sửa chữa

Tài liệu này mô tả các bước THAO TÁC TRÊN TRÌNH DUYỆT để kiểm tra một luồng
nghiệp vụ đầy đủ của LED Manager, xuyên 3 vai trò. Không cần biết code, không
cần chạy lệnh — chỉ cần đăng nhập và bấm theo đúng thứ tự, rồi đối chiếu với
"Kết quả mong đợi" ở mỗi bước.

Địa chỉ web: `http://127.0.0.1:8000` (thay bằng địa chỉ thật nếu khác).

Mật khẩu chung cho mọi tài khoản bên dưới: `password`.

---

## Chuẩn bị

Trước khi bắt đầu, nhờ người quản trị hệ thống xác nhận 2 việc:

1. Website đang chạy được (mở `http://127.0.0.1:8000/login` thấy trang đăng nhập bình thường).
2. Dữ liệu test đang sạch (không còn đơn hàng "Đã xuất kho" bị treo từ lần
   test trước). Nếu không chắc, cứ thử làm — nếu ở Bước 6 báo lỗi "Thiếu
   thiết bị khả dụng trong khoảng ngày đã chọn", nghĩa là cần nhờ người quản
   trị reset dữ liệu test rồi làm lại từ đầu.

Ghi lại 3 mã serial ngẫu nhiên để dùng xuyên suốt, ví dụ: `TEST-001`,
`TEST-002`, `TEST-003` (đặt tên gì cũng được, miễn KHÔNG trùng serial đã có
trong hệ thống).

---

## PHASE 0 — Đại lý: chụp số liệu doanh thu TRƯỚC khi test (baseline)

1. Đăng nhập bằng tài khoản `daily.haiphong@ledmanager.com`.
2. Vào menu **Báo cáo → Doanh thu đại lý**.
3. Ghi lại 4 số: **Doanh Thu**, **Thực Thu**, **Hoa Hồng**, **Số Đơn Hàng**, và **% Hoa hồng** hiển thị trên trang.
4. Đăng xuất.

**Kết quả mong đợi:** trang mở được bình thường, hiển thị đủ các số trên (không lỗi, không trắng trang).

---

## PHASE 1 — Super Admin: tạo thiết bị mới + nhập kho về đại lý

Đăng nhập bằng tài khoản `admin@ledmanager.com`.

### Bước 1: Tạo 3 thiết bị mới

1. Vào menu **Danh mục thiết bị** (`/assets`).
2. Bấm nút thêm mới, tạo lần lượt 3 thiết bị với 3 mã serial đã chuẩn bị, mỗi
   thiết bị chọn dòng sản phẩm LED **"P2.6 Sự kiện"**.
3. Dùng ô tìm kiếm, gõ từng mã serial để xác nhận cả 3 đã xuất hiện trong danh sách.

**Kết quả mong đợi:** cả 3 thiết bị tạo thành công, tìm thấy đủ 3.

### Bước 2: Tạo đợt nhập kho, gán về đại lý Hải Phòng

1. Vào menu **Nhập kho / Check-in** (`/checkin-batches`), bấm tạo đợt mới.
2. Trong form: bấm vào ô **"Đại lý tiếp nhận (Nếu có)"**, gõ "Hải Phòng", **click chọn đúng dòng "Đại lý Hải Phòng"** trong danh sách gợi ý (không chỉ gõ chữ rồi bỏ đó).
3. Kiểm tra: ô **"Kho lưu trữ tiếp nhận"** bên cạnh phải tự động đổi thành **"Kho Hải Phòng"**. Nếu vẫn thấy kho khác, quay lại bước trên và chọn lại.
4. Điền "Ngày dự kiến" = hôm nay.
5. Tick chọn cả 3 thiết bị vừa tạo trong danh sách "Mã seri gợi ý từ kho".
6. Bấm **Tạo** để lưu đợt nhập kho.

**Kết quả mong đợi:** đợt nhập kho được tạo, chứa đủ 3 serial.

### Bước 3: Nhận hàng cho cả 3 thiết bị

1. Mở đợt nhập kho vừa tạo.
2. Với **từng thiết bị một** (không làm hàng loạt), bấm nút **"Nhận hàng"**, chọn tình trạng **"Bình thường"**, xác nhận.
3. Lặp lại cho đến khi cả 3/3 thiết bị đều đã nhận — kiểm tra trạng thái mỗi dòng đã đổi từ "Chưa nhận" sang đã nhận trước khi làm dòng tiếp theo.
4. Sau khi 3/3 xong, bấm **"Kết thúc nhận hàng"**.

**Kết quả mong đợi:** cả 3 thiết bị hiển thị đã nhận, đợt nhập kho đã kết thúc (không còn ở trạng thái dở dang).

⚠️ **Lưu ý quan trọng:** phải đợi trang phản hồi xong (nút đổi trạng thái,
thông báo "Đã nhận" hiện ra) rồi mới bấm sang thiết bị kế tiếp. Bấm liên tục
nhiều nút cùng lúc khi trang chưa kịp cập nhật là nguyên nhân phổ biến nhất
khiến bước này bị dở dang.

Đăng xuất tài khoản admin.

---

## PHASE 2 — Đại lý: tạo đơn hàng → xuất kho → thu hồi

Đăng nhập bằng tài khoản `daily.haiphong@ledmanager.com`.

### Bước 4: Kiểm tra tồn kho đã tăng

Vào lại **Báo cáo → Doanh thu đại lý**, xem chỉ số "Tồn Kho" — phải **tăng
lên** so với số ghi ở Phase 0 (vì vừa nhập thêm 3 thiết bị).

### Bước 5: Tạo đơn hàng mới

1. Vào menu **Đơn hàng** (`/orders`), bấm tạo đơn mới.
2. Chọn khách hàng có mã **CUS-001** (Tập đoàn Vingroup).
3. Trong bảng thiết bị, dòng đầu tiên: chọn "Dòng SP LED" = **P2.6 Sự kiện**, "SL Cần" = **3**.
4. Kiểm tra ngay: ô **"Đơn giá"** phải tự động điền một số tiền (không phải
   trống hoặc 0). Ô **"Tổng giá trị đơn hàng"** cũng phải tự động hiện một số
   tiền tương ứng. → Nếu 2 ô này vẫn trống/0 sau khi đã chọn dòng sản phẩm và
   số lượng, đây là **lỗi cần báo lại ngay**, không tự sửa hay đoán số.
5. Đặt "Ngày bắt đầu sự kiện" và "Ngày dự kiến hoàn trả" cách nhau vài ngày (ví dụ hôm nay và 3 ngày sau).
6. Nhìn lại số ở ô "Tổng giá trị đơn hàng", điền đúng số đó vào ô **"Tổng tiền đã thu"** trong mục "Thông tin thanh toán".
7. Bấm **Tạo/Lưu**.

**Kết quả mong đợi:** đơn hàng lưu thành công, không có thông báo lỗi "Thiếu thiết bị khả dụng".

### Bước 6: Xuất kho theo đơn

1. Mở lại đơn hàng vừa tạo (bấm vào mã đơn trong danh sách).
2. Trên đầu trang chi tiết đơn hàng, bấm nút **"Tạo Đợt Xuất Kho"**.
3. Trong hộp thoại: giữ nguyên bật "Tự động chọn & gán thiết bị từ kho", bật thêm "Đánh dấu đã kiểm đếm xong" nếu có, xác nhận.
4. Nếu sau đó còn thấy nút xác nhận xuất kho nào khác trên trang, bấm tiếp để hoàn tất — đến khi trạng thái đơn chuyển thành **"Đã xuất kho"**.

**Kết quả mong đợi:** trạng thái đơn hàng = Đã xuất kho.

### Bước 7: Thu hồi trả kho (1 thiết bị đánh dấu hỏng)

1. Vẫn ở trang chi tiết đơn hàng, bấm **"Thu hồi trả kho"**.
2. Trong hộp thoại chấm điểm từng thiết bị: 2 thiết bị đầu chọn **"Bình thường"**, thiết bị còn lại chọn **"Hỏng hóc"** kèm ghi chú (ví dụ "vỡ module LED khi vận chuyển").
3. Đánh dấu "Đã nhận" cho cả 3, xác nhận.

**Kết quả mong đợi:** trạng thái đơn chuyển thành **"Đã thu hồi trả kho"**.

Đăng xuất tài khoản đại lý.

---

## PHASE 3 — Kỹ thuật đại lý: sửa xong thiết bị hỏng

Đăng nhập bằng tài khoản `tech.haiphong@ledmanager.com`.

1. Vào menu **Bảo trì & Sửa chữa** (`/repair-logs`).
2. Tìm đúng dòng có **mã serial của thiết bị vừa đánh dấu Hỏng hóc** ở Bước 7
   (không phải serial nào khác trong danh sách, kể cả nếu danh sách có sẵn
   phiếu cũ khác trông giống). Nếu không tìm thấy đúng serial đó, **dừng lại
   và báo lỗi**, không tự ý sửa nhầm phiếu khác.
3. Trên dòng đó, bấm action **"Hoàn thành sửa chữa"**.
4. Trong hộp thoại: giữ ngày hôm nay, chọn kết quả **"Đã sửa xong — Sẵn sàng sử dụng"**, điền chi phí sửa chữa (ví dụ 500.000), ghi chú, bấm xác nhận.

**Kết quả mong đợi:** cột "Kết quả" của dòng phiếu chuyển thành **"Đã sửa xong (Sẵn sàng)"**.

Đăng xuất.

---

## PHASE 4 — Đại lý: đối chiếu doanh thu sau cùng

Đăng nhập lại bằng `daily.haiphong@ledmanager.com`, vào **Báo cáo → Doanh thu đại lý**, ghi lại 4 số như Phase 0.

So sánh với số đã ghi ở Phase 0:

| Kiểm tra | Đúng khi |
| --- | --- |
| Số đơn hàng | tăng đúng **1** (không hơn) |
| Doanh thu | tăng lên **đúng bằng** số tiền đơn hàng vừa tạo ở Bước 5 |
| Doanh thu tăng == Thực thu tăng | hai số này phải **bằng nhau tuyệt đối** |
| Hoa hồng tăng | bằng (Thực thu tăng) × (% Hoa hồng hiển thị) ÷ 100 |
| % Hoa hồng | không đổi so với Phase 0 |

Nếu bất kỳ dòng nào trong bảng trên **sai** → ghi lại chính xác số liệu thực
tế thấy được (không làm tròn, không đoán) và báo lại kèm ảnh chụp màn hình.

---

## Khi nào là lỗi thật cần báo, khi nào chỉ cần làm lại

- **Chỉ cần làm lại từ Phase 1** (không cần báo lỗi) nếu: bấm nhầm nút, trang
  load chậm nên bấm hụt, hoặc quên chờ trang phản hồi trước khi bấm bước
  tiếp theo.
- **Phải báo lỗi ngay, kèm ảnh chụp màn hình và mô tả đúng những gì thấy trên
  màn hình** (không tự suy diễn, không tự sửa số liệu cho khớp) nếu:
  - Một ô đáng lẽ tự động điền số (Đơn giá, Tổng giá trị đơn hàng, %Hoa hồng...) lại để trống hoặc hiện sai.
  - Xuất hiện thông báo lỗi màu đỏ mà làm đúng các bước ở trên vẫn không hết.
  - Bảng đối chiếu ở Phase 4 lệch, dù đã làm đúng trình tự và không bấm nhầm.
  - Cùng một lỗi lặp lại y hệt ở **2 lần thử lại liên tiếp** (đã làm lại từ đầu, đổi serial mới).
