"""
E2E test tự động cho MỘT luồng nghiệp vụ Đại lý (LED Manager) bằng browser-use
(AI browser agent), chạy trên http://127.0.0.1:8000.

Luồng duy nhất được kiểm tra (end-to-end, xuyên 3 vai trò):

  P0  Đại lý      — chụp số liệu doanh thu TRƯỚC khi chạy (baseline)
  P1  Super Admin — tạo 3 thiết bị mới -> tạo đợt nhập kho -> phân bổ về
                    Kho Hải Phòng của Đại lý Hải Phòng
  P2  Đại lý      — tạo đơn hàng -> xuất kho từ đơn -> thu hồi trả kho
                    (1 thiết bị đánh dấu Hỏng hóc) -> hoàn tất đơn hàng
  P3  Kỹ thuật    — sửa chữa thiết bị hỏng cho tới khi Sẵn sàng
  P4  Đại lý      — chụp lại số liệu doanh thu và ĐỐI CHIẾU với P0

Ngoài phạm vi: đợt xuất kho tạo ra mà KHÔNG gắn đơn hàng thì không được tính
vào doanh thu — script cố ý không đi nhánh đó, và các phép kiểm ở P4 sẽ thất
bại nếu có doanh thu "ma" lọt vào từ đó.

Điểm khác biệt so với bản cũ (bản 3-phase không có verdict):
  * Có VERDICT thật: mọi phép kiểm đều tất định (đọc DOM qua CDP), không phụ
    thuộc vào việc LLM tự thuật. Cuối script in bảng PASS/FAIL và
    `sys.exit(1)` nếu có phép kiểm nào thất bại -> dùng được trong CI.
  * P0/P4 đối chiếu DOANH THU & HOA HỒNG thật trên /agency-revenue-report.
  * Kiểm luôn nhánh sửa chữa qua trang tra cứu công khai /q/{serial}.

Cài đặt:
    pip install -r requirements.txt

    # Chọn 1 trong 2 provider LLM:
    export ANTHROPIC_API_KEY=sk-ant-...          # mặc định (có vision)
    # hoặc
    export LLM_PROVIDER=deepseek
    export DEEPSEEK_API_KEY=sk-...               # dùng deepseek-flash (có vision)
                                                  # đổi model: DEEPSEEK_MODEL=...

    # Tắt/bật vision bằng tay khi cần:
    export USE_VISION=false

Chạy:
    python browser_use_e2e.py
    HEADLESS=true python browser_use_e2e.py    # chạy ẩn trình duyệt

--------------------------------------------------------------------------
Cơ chế đã xác minh trong code (app/Filament/Resources/{CheckinBatches,
Orders, Assets}, app/Enums/OrderStatus.php, app/Models/Agency.php):
- Tạo thiết bị mới (Create:Asset) và tạo đợt nhập kho (Create:CheckinBatch)
  chỉ dành cho super_admin/admin/warehouse_manager — KHÔNG cho agency, vì
  vậy P1 phải dùng tài khoản admin.
- Đợt nhập kho cho kho thuộc đại lý: PHẢI chọn field "Đại lý tiếp nhận" TRƯỚC
  thì field "Kho lưu trữ tiếp nhận" mới tự đổi sang đúng kho của đại lý đó
  (kho riêng của đại lý bị ẩn khỏi dropdown nếu chưa chọn đại lý).
- agency_manager/agency_staff CÓ quyền Create:Order, Create:CheckoutBatch,
  Create:ReturnBatch, Create:Customer — tự vận hành trọn vòng đơn hàng của
  kho đại lý mình mà không cần admin/thủ kho can thiệp.
- Đơn hàng của đại lý dùng khách hàng có sẵn trong seed (CUS-001, đã gắn
  agency_id = Đại lý Hải Phòng) — không cần tạo khách hàng mới.
- OrderForm: `agency_id` mặc định = agency của user đang đăng nhập (và bị
  disable + dehydrated khi user thuộc đại lý), `commission_rate` lấy snapshot
  từ agency, `warehouse_id` tự set theo kho của agency. Đây là lý do P4 có
  thể đối chiếu hoa hồng mà không cần nhập tay tỷ lệ.
- Các action của Đơn hàng (Tạo Đợt Xuất Kho / Thu hồi trả kho / Hoàn tất
  Đơn hàng) nằm dưới dạng NÚT BẤM RIÊNG trên đầu trang chi tiết đơn hàng
  (/orders/{id}/edit), KHÔNG phải dropdown "Thao tác" ở trang danh sách
  /orders — vào đúng trang chi tiết 1 lần rồi làm hết các bước tại đó.
- CreateCheckoutBatchAction (action "Tạo Đợt Xuất Kho" ở Đơn hàng) có toggle
  "Tự động chọn & gán thiết bị từ kho" (mặc định BẬT) và toggle "Đánh dấu đã
  kiểm đếm xong" — bật cả hai để xuất kho xong trong 1 action, không cần
  quét thủ công từng thiết bị.
- ReturnOrderAction (action "Thu hồi trả kho" ở Đơn hàng, chỉ hiện khi
  order.status = Dispatched) chấm điểm TOÀN BỘ thiết bị trong 1 modal, đưa
  đơn hàng sang trạng thái Returned ngay lập tức. Thiết bị chấm "Hỏng hóc"
  tự động tạo RepairLog + chuyển Asset sang trạng thái Repairing.
- Đóng phiếu bảo dưỡng làm NGAY TẠI trang "Bảo trì & Sửa chữa"
  (/repair-logs) — vì phiếu sửa chữa được sinh ra từ luồng nhập trả, nên
  nghiệp vụ bảo trì khép kín ở đó chứ KHÔNG phải mò sang danh sách /assets.
  Action "Hoàn thành sửa chữa" (CompleteRepairAction, ở
  app/Filament/Resources/RepairLogs/Actions) là 1 action RIÊNG trên dòng
  phiếu, chỉ hiện khi RepairLog.result_status = Pending; nó tự đồng bộ cả
  RepairLog (end_date, result_status, chi phí, ghi chú) lẫn Asset (Ready nếu
  sửa xong / Disposed nếu thanh lý) qua app/Services/MaintenanceService.php —
  nên không thể lệch trạng thái giữa hai bên.
- Sau khi Returned, còn action riêng "Hoàn tất Đơn hàng" (CompleteOrderAction,
  chỉ hiện khi status = Returned) mới thực sự đóng đơn (status = Completed).
  Action này CHẶN CỨNG nếu total_paid < value (chưa thu đủ 100% tiền đơn
  hàng) — phải điền "Tổng tiền đã thu" bằng đúng "Tổng giá trị đơn hàng" lúc
  tạo đơn thì mới hoàn tất được. Nếu có thiết bị Hỏng hóc chưa có chi phí sửa
  chữa, action còn hiện thêm 1 bảng cho nhập chi phí dự kiến (không bắt
  buộc phải sửa xong thiết bị mới đóng được đơn).
- Kỹ thuật viên bị scope theo kho/đại lý — dùng đúng tài khoản kỹ thuật của
  đại lý Hải Phòng (tech.haiphong@ledmanager.com).

--------------------------------------------------------------------------
Cơ chế ĐỐI CHIẾU DOANH THU (P0 vs P4):
- Trang /agency-revenue-report (app/Filament/Pages/AgencyRevenueReport.php)
  tính hoa hồng = total_paid * commission_rate / 100 — tức ăn theo SỐ TIỀN
  THỰC THU, không phải theo giá trị đơn. Với user thuộc đại lý,
  `getStats()` bị scope về đúng agency_id của user đó.
- Vì P2 cố ý điền "Tổng tiền đã thu" = "Tổng giá trị đơn hàng", nên sau khi
  chạy xong ta phải có: Δdoanh_thu == Δthực_thu. Nếu agent điền thiếu tiền,
  hoặc nếu có doanh thu "ma" lọt vào từ một đợt xuất kho không gắn đơn, phép
  kiểm này sẽ thất bại.
- Δhoa_hồng phải đúng bằng Δthực_thu * tỷ_lệ_hiển_thị_trên_báo_cáo / 100.
- Tỷ lệ hoa hồng KHÔNG hardcode trong script: nó được đọc từ chính badge
  "% Hoa hồng" trên báo cáo rồi đối chiếu, để phép kiểm không bị lệch khi
  seed đổi tỷ lệ. (Seed hiện tại: DL-HP = 15%.)

Lưu ý kỹ thuật: app chạy Vite dev server (WebSocket HMR luôn mở), nên chờ
"network idle" mặc định của browser-use có thể treo rất lâu khi điều hướng.
Script đặt wait_for_network_idle_page_load_time thấp để tránh việc đó.

Chạy lại nhiều lần (QUAN TRỌNG): script này KHÔNG tự dọn dữ liệu. Một lần chạy
thất bại giữa đường sẽ để lại đơn hàng ở trạng thái Dispatched cùng vài thiết
bị InTransit. Đơn treo đó VẪN GIỮ CHỖ thiết bị trong kho đại lý, nên lần chạy
sau có thể bị chặn tạo đơn bởi OrderForm::validateAvailability() với thông báo
"Thiếu thiết bị khả dụng trong khoảng ngày đã chọn".
Script đã giảm rủi ro này bằng cách đặt lịch sự kiện lùi ~30 ngày, nhưng cách
chắc chắn nhất vẫn là reset DB dev trước khi chạy lại:

    php artisan app:bootstrap      # migrate:fresh + Shield + seed lại

Trang /q/{serial} là trang tra cứu công khai (không cần đăng nhập,
PublicAssetController@show) — dùng để đọc trạng thái thiết bị một cách tất
định thay vì phải dò tìm dòng trong bảng /assets có phân trang.
"""

from __future__ import annotations

import asyncio
import os
import re
import sys
import time
import uuid
from dataclasses import dataclass, field
from datetime import date, timedelta
from typing import Any

from browser_use import Agent, Browser
from browser_use.llm.base import BaseChatModel
from dotenv import load_dotenv

load_dotenv()

BASE_URL = os.getenv("BASE_URL", "http://127.0.0.1:8000").rstrip("/")
HEADLESS = os.getenv("HEADLESS", "false").lower() == "true"

# Mật khẩu của các tài khoản demo do seeder tạo (không phải secret thật —
# mọi user seed đều dùng chung mật khẩu này). Có thể ghi đè bằng env.
PASSWORD = os.getenv("E2E_PASSWORD", "password")

# Serial ngẫu nhiên mỗi lần chạy để không đụng dữ liệu test cũ còn sót lại
# (asset.serial_no là UNIQUE). 3 thiết bị: 2 trả về Bình thường, 1 trả về
# Hỏng hóc (test luôn nhánh sửa chữa).
ASSET_SERIALS = [f"AGY-TEST-{uuid.uuid4().hex[:6].upper()}" for _ in range(3)]
DAMAGED_SERIAL = ASSET_SERIALS[-1]

# Dữ liệu seed mà luồng này bám vào (database/seeders/LedOsDataSeeder.php).
PRODUCT_LINE_NAME = "P2.6 Sự kiện"
AGENCY_NAME = "Đại lý Hải Phòng"
AGENCY_WAREHOUSE_NAME = "Kho Hải Phòng"
CUSTOMER_HINT = "CUS-001"

# Đặt lịch sự kiện lùi xa (~30 ngày) để KHÔNG giao với các đơn còn treo của
# những lần chạy trước: một đơn Dispatched chưa thu hồi vẫn đang giữ chỗ thiết
# bị, khiến OrderForm::validateAvailability() chặn tạo đơn mới với lỗi
# "Thiếu thiết bị khả dụng".
ORDER_START_DATE = date.today() + timedelta(days=30)
ORDER_END_DATE = ORDER_START_DATE + timedelta(days=3)

# Classes CSS của badge trạng thái trên trang công khai /q/{serial}
# (resources/views/public/asset-detail.blade.php).
STATUS_REPAIRING = "status-repair"
STATUS_READY = "status-ready"

# Badge kết quả phiếu bảo dưỡng trên trang công khai /q/{serial}:
# badge-warning = Pending, badge-success = Fixed, badge-danger = Disposed.
REPAIR_BADGE_PENDING = "badge-warning"
REPAIR_BADGE_FIXED = "badge-success"

REPORT_PATH = "/agency-revenue-report"

# Dung sai khi so số tiền (VND là số nguyên, nhưng cột là decimal).
MONEY_TOLERANCE = 0.5


# --------------------------------------------------------------------------
# Verdict: gom mọi phép kiểm rồi in bảng PASS/FAIL và quyết định exit code.
# --------------------------------------------------------------------------
@dataclass
class CheckResult:
    phase: str
    name: str
    ok: bool
    detail: str = ""


@dataclass
class Verdict:
    results: list[CheckResult] = field(default_factory=list)

    def check(self, phase: str, name: str, ok: bool, detail: str = "") -> bool:
        self.results.append(CheckResult(phase, name, bool(ok), detail))
        marker = "PASS" if ok else "FAIL"
        line = f"  [{marker}] {name}"
        if detail:
            line += f" — {detail}"
        print(line, flush=True)
        return bool(ok)

    def equal(
        self,
        phase: str,
        name: str,
        actual: Any,
        expected: Any,
        tolerance: float | None = None,
    ) -> bool:
        if tolerance is not None and isinstance(actual, (int, float)) and isinstance(
            expected, (int, float)
        ):
            try:
                ok = abs(float(actual) - float(expected)) <= tolerance
            except (TypeError, ValueError):
                ok = False
        else:
            ok = actual == expected
        return self.check(phase, name, ok, f"actual={actual!r} expected={expected!r}")

    @property
    def failed(self) -> list[CheckResult]:
        return [r for r in self.results if not r.ok]

    def print_summary(self) -> None:
        print("\n" + "=" * 68, flush=True)
        print("KẾT QUẢ", flush=True)
        print("=" * 68, flush=True)
        for r in self.results:
            marker = "PASS" if r.ok else "FAIL"
            print(f"[{marker}] {r.phase:>3} | {r.name}", flush=True)
            if not r.ok and r.detail:
                print(f"           -> {r.detail}", flush=True)

        total = len(self.results)
        failed = len(self.failed)
        print("-" * 68, flush=True)
        print(
            f"Tổng: {total} phép kiểm | PASS: {total - failed} | FAIL: {failed}",
            flush=True,
        )
        if failed:
            print("\n❌ E2E THẤT BẠI", flush=True)
        else:
            print("\n✅ E2E THÀNH CÔNG", flush=True)
        print("=" * 68, flush=True)


# --------------------------------------------------------------------------
# LLM provider
# --------------------------------------------------------------------------
def _resolve_provider() -> str:
    """LLM_PROVIDER nếu được set rõ, không thì tự chọn theo key nào có sẵn."""
    explicit = os.getenv("LLM_PROVIDER")
    if explicit:
        return explicit.lower()
    if os.getenv("ANTHROPIC_API_KEY"):
        return "anthropic"
    if os.getenv("DEEPSEEK_API_KEY"):
        return "deepseek"
    raise SystemExit(
        "Thiếu API key: set ANTHROPIC_API_KEY hoặc DEEPSEEK_API_KEY trong "
        "biến môi trường / file .env."
    )


def _build_deepseek_llm(model: str, api_key: str) -> BaseChatModel:
    """Dựng ChatDeepSeek đã TẮT thinking mode.

    Vì sao cần: các model DeepSeek ở đây (deepseek-flash, deepseek-v4-pro) BẬT
    thinking mode mặc định ở phía server, mà thinking mode lại từ chối
    `tool_choice` — trong khi browser-use cần `tool_choice` để lấy structured
    output. Kết quả là API trả 400:
        "Thinking mode does not support this tool_choice"
    và agent retry tới chết (đã gặp thực tế: "Result failed 6/6 times").

    ChatDeepSeek gốc chỉ gửi cờ tắt thinking khi tên model chứa 'deepseek-v4'
    (xem `_supports_thinking`), nên với 'deepseek-flash' nó không gửi gì cả.
    Override lại để luôn gửi cờ đó.
    """
    from browser_use.llm.deepseek.chat import ChatDeepSeek

    class NonThinkingChatDeepSeek(ChatDeepSeek):
        def _supports_thinking(self) -> bool:
            return True

    return NonThinkingChatDeepSeek(model=model, api_key=api_key, thinking=False)


def build_llm() -> tuple[BaseChatModel, bool]:
    """Trả về (llm, use_vision) theo provider được chọn.

    Ghi đè được bằng biến môi trường USE_VISION=true|false.
    """
    provider = _resolve_provider()

    if provider == "deepseek":
        api_key = os.getenv("DEEPSEEK_API_KEY")
        if not api_key:
            raise SystemExit("Thiếu DEEPSEEK_API_KEY.")

        # ĐÃ KIỂM CHỨNG TRỰC TIẾP QUA API (không đoán):
        #   * deepseek-flash  -> CÓ vision (đọc đúng chữ trong ảnh).
        #   * deepseek-chat   -> CÓ vision.
        #   * deepseek-v4-pro -> KHÔNG có vision (API trả về [Unsupported Image]).
        model = os.getenv("DEEPSEEK_MODEL", "deepseek-flash")

        llm: BaseChatModel = _build_deepseek_llm(model, api_key)
    else:
        from browser_use import ChatAnthropic

        if not os.getenv("ANTHROPIC_API_KEY"):
            raise SystemExit("Thiếu ANTHROPIC_API_KEY.")

        llm = ChatAnthropic(model="claude-sonnet-4-0", temperature=0.0)

    use_vision = True

    override = os.getenv("USE_VISION")
    if override is not None:
        use_vision = override.strip().lower() == "true"

    return llm, use_vision


ACCOUNTS = {
    "admin": "admin@ledmanager.com",
    "agency": "daily.haiphong@ledmanager.com",
    # Kỹ thuật viên riêng của đại lý Hải Phòng (role: technician + agency_staff,
    # scoped theo warehouse_id/agency_id) — tài khoản "technician" trung tâm
    # không được scope vào kho/đại lý này nên không thấy phiếu sửa chữa.
    "agency_technician": "tech.haiphong@ledmanager.com",
}

LOGIN_TASK = (
    "Truy cập {url}. Đăng nhập bằng email login_email và mật khẩu "
    "login_password (nếu trang có nút đăng nhập nhanh khớp với email này thì "
    "dùng nút đó, không thì điền vào form và bấm nút đăng nhập). Xác nhận đã "
    "vào được trang chủ/dashboard trước khi dừng."
)


# --------------------------------------------------------------------------
# Truy cập DOM tất định qua CDP (không qua LLM)
# --------------------------------------------------------------------------
async def eval_js(browser: Browser, expression: str) -> Any:
    """Chạy JS trong tab đang mở và trả về giá trị (returnByValue)."""
    session = await browser.get_or_create_cdp_session()
    result = await session.cdp_client.send.Runtime.evaluate(
        params={
            "expression": expression,
            "returnByValue": True,
            "awaitPromise": True,
        },
        session_id=session.session_id,
    )
    if result.get("exceptionDetails"):
        raise RuntimeError(
            f"JS lỗi: {result['exceptionDetails'].get('text', 'unknown')}"
        )
    return result.get("result", {}).get("value")


async def try_eval_js(browser: Browser, expression: str) -> Any:
    """Như eval_js nhưng trả None thay vì raise — dùng cho bước đọc dữ liệu."""
    try:
        return await eval_js(browser, expression)
    except Exception as exc:  # noqa: BLE001 - không để 1 lần đọc lỗi làm chết cả run
        print(f"  [!] Không chạy được JS để đọc dữ liệu: {exc}", flush=True)
        return None


async def goto(
    browser: Browser,
    url: str,
    must_contain: str | None = None,
    timeout: float = 40.0,
) -> tuple[bool, str]:
    """Điều hướng rồi chờ tới khi nội dung mong đợi xuất hiện.

    So khớp KHÔNG phân biệt hoa/thường, vì `document.body.innerText` trả về
    chữ đã qua CSS `text-transform`: các nhãn KPI có class Tailwind `uppercase`
    nên khi CSS đã load thì "Doanh Thu Đơn Hàng" hiển thị thành
    "DOANH THU ĐƠN HÀNG" trong innerText.

    Trả về (đã_thấy_nội_dung_mong_đợi, text_toàn_trang).
    """
    await browser.navigate_to(url)
    needle = must_contain.upper() if must_contain else None
    deadline = time.monotonic() + timeout
    text = ""
    while True:
        await asyncio.sleep(0.5)
        try:
            text = await eval_js(browser, "document.body ? document.body.innerText : ''") or ""
        except Exception:  # noqa: BLE001 - trang có thể đang điều hướng
            text = ""
        if needle is None or needle in text.upper():
            return True, text
        if time.monotonic() >= deadline:
            return False, text


# --------------------------------------------------------------------------
# Parse số đã format sẵn trên UI.
#   * Blade `number_format()`  -> C-locale: "105,000,000" / "1,000.0"
#   * Filament `money('VND')` -> kiểu VN:   "105.000.000" / "30,0"
# --------------------------------------------------------------------------
def parse_display_number(raw: Any) -> float | None:
    """Đọc một số đã được format sẵn trên giao diện.

    Hỗ trợ cả hai kiểu phân cách đang cùng tồn tại trong app:
      * Blade `number_format()` -> C-locale: "105,000,000", "1,000.0"
      * Filament `money('VND')` -> kiểu VN:   "105.000.000", "30,0"

    Quy tắc: dấu phân cách CUỐI CÙNG là dấu thập phân nếu sau nó có 1-2 chữ số;
    ngược lại nó là dấu phân cách hàng nghìn. Nhờ vậy "1,000" -> 1000 còn
    "1,000.0" -> 1000.0, và "11.250.000" -> 11250000.
    """
    cleaned = re.sub(r"[^0-9.,]", "", str(raw or ""))
    if not cleaned:
        return None

    last_sep = max(cleaned.rfind("."), cleaned.rfind(","))
    try:
        if last_sep == -1:
            return float(cleaned)

        decimals = cleaned[last_sep + 1 :]
        if re.fullmatch(r"\d{1,2}", decimals):
            integer = re.sub(r"[.,]", "", cleaned[:last_sep]) or "0"

            return float(f"{integer}.{decimals}")

        # Dấu cuối cùng là phân cách hàng nghìn -> cả chuỗi là phần nguyên.
        return float(re.sub(r"[.,]", "", cleaned))
    except ValueError:
        return None


# JS gom các cặp nhãn -> giá trị của 4 thẻ KPI trên /agency-revenue-report.
# Dùng textContent (KHÔNG phải innerText) vì nhãn có class `uppercase` — nếu
# dùng innerText thì CSS text-transform sẽ viết hoa và regex theo nhãn sẽ trượt.
_JS_REPORT_KPIS = """
(() => {
  const kpis = {};
  document.querySelectorAll('h3').forEach((h3) => {
    const wrap = h3.parentElement;
    const label = wrap ? wrap.querySelector('p') : null;
    if (!label) return;
    const name = label.textContent.trim();
    const value = h3.textContent.trim();
    // Chỉ nhận thẻ KPI thật: giá trị phải là tiền (VND) hoặc diện tích (m²).
    // Cách này loại được cặp p/h3 ở tiêu đề mục "Danh Sách Đại Lý Tỉnh...".
    if (!name || !/VND|m²/.test(value)) return;
    kpis[name] = value;
  });
  const spans = [...document.querySelectorAll('span')].map((s) => s.textContent.trim());
  return {
    kpis,
    ordersLabel: spans.find((t) => /^\\d[\\d.,]*\\s*Đơn hàng$/.test(t)) || null,
    rateLabel: spans.find((t) => /^\\d[\\d.,]*\\s*%\\s*Hoa hồng$/.test(t)) || null,
  };
})()
"""

# JS đọc trạng thái thiết bị trên trang công khai /q/{serial}.
# "Bảo dưỡng (N)" là số phiếu RepairLog đã tạo cho thiết bị — dùng để chứng
# minh luồng thu hồi Hỏng hóc thực sự sinh phiếu bảo dưỡng.
_JS_ASSET_STATUS = """
(() => {
  const badge = document.querySelector('.status-badge');
  if (!badge) return null;
  const statusClass = [...badge.classList].find(
    (c) => c.startsWith('status-') && c !== 'status-badge'
  ) || null;
  const heading = document.querySelector('h1');
  const repairTab = document.querySelector('[data-tab="repair"]');
  const repairMatch = repairTab ? repairTab.textContent.match(/\\((\\d+)\\)/) : null;
  const repairPane = document.querySelector('#tab-repair');
  const resultBadge = repairPane ? repairPane.querySelector('.timeline-meta .badge') : null;
  const resultClass = resultBadge
    ? [...resultBadge.classList].find((c) => c.startsWith('badge-') && c !== 'badge') || null
    : null;
  return {
    statusClass,
    label: badge.textContent.trim(),
    serial: heading ? heading.textContent.trim() : null,
    repairLogCount: repairMatch ? Number(repairMatch[1]) : null,
    repairResultClass: resultClass,
    repairResultLabel: resultBadge ? resultBadge.textContent.trim() : null,
  };
})()
"""


@dataclass
class RevenueSnapshot:
    revenue: float
    collected: float
    commission: float
    orders: int
    inventory_area: float
    allocated_area: float
    rate: float


def snapshot_from_report(data: Any) -> RevenueSnapshot | None:
    """Dựng RevenueSnapshot từ output của `_JS_REPORT_KPIS`.

    Tách thành hàm thuần (không cần browser) để có thể kiểm chứng bộ parse
    trực tiếp trên HTML/JS thật — xem phần tự kiểm ở cuối file.
    """
    kpis: dict[str, str] = (data or {}).get("kpis") or {}

    def kpi(*label_fragments: str) -> float | None:
        for label, value in kpis.items():
            if all(fragment in label for fragment in label_fragments):
                return parse_display_number(value)
        return None

    revenue = kpi("Doanh Thu")
    collected = kpi("Thực Thu")
    commission = kpi("Hoa Hồng")

    orders = None
    if (data or {}).get("ordersLabel"):
        orders = parse_display_number(data["ordersLabel"])
    rate = None
    if (data or {}).get("rateLabel"):
        rate = parse_display_number(data["rateLabel"])

    # "Tồn Kho / Hạn Mức Bàn Giao" -> "30.0 / 1,000.0 m²"
    inventory_area = allocated_area = None
    for label, value in kpis.items():
        if "Tồn Kho" in label:
            parts = str(value).split("/")
            if len(parts) == 2:
                inventory_area = parse_display_number(parts[0])
                allocated_area = parse_display_number(parts[1])
            break

    # Narrow tường minh (thay vì `None in (...)`) để type checker biết chắc
    # các giá trị dưới đây không còn None.
    if revenue is None or collected is None or commission is None:
        print(f"  [!] Thiếu chỉ số doanh thu/thực thu/hoa hồng. KPIs: {kpis}")
        return None
    if orders is None or rate is None:
        print(f"  [!] Thiếu số đơn hàng hoặc tỷ lệ hoa hồng. KPIs: {kpis}")
        return None

    try:
        orders_count = int(orders)
    except (TypeError, ValueError):
        print(f"  [!] Số đơn hàng đọc được không hợp lệ: {orders!r}")
        return None

    return RevenueSnapshot(
        revenue=revenue,
        collected=collected,
        commission=commission,
        orders=orders_count,
        inventory_area=inventory_area or 0.0,
        allocated_area=allocated_area or 0.0,
        rate=rate,
    )


async def read_revenue(browser: Browser, phase: str) -> RevenueSnapshot | None:
    """Đọc snapshot doanh thu/hoa hồng của đại lý từ /agency-revenue-report."""
    ok, text = await goto(browser, f"{BASE_URL}{REPORT_PATH}", must_contain="Doanh Thu")
    if not ok:
        print(f"  [!] Không đọc được báo cáo doanh thu. Đầu trang: {text[:300]!r}")
        return None

    snapshot = snapshot_from_report(await try_eval_js(browser, _JS_REPORT_KPIS))
    if snapshot is None:
        return None

    print(
        f"  [{phase}] doanh_thu={snapshot.revenue} thực_thu={snapshot.collected} "
        f"hoa_hồng={snapshot.commission} đơn={snapshot.orders} "
        f"tỷ_lệ={snapshot.rate} "
        f"tồn_kho={snapshot.inventory_area}/{snapshot.allocated_area}",
        flush=True,
    )

    return snapshot


async def read_asset_status(browser: Browser, serial: str) -> dict[str, Any] | None:
    """Đọc trạng thái 1 thiết bị qua trang tra cứu công khai /q/{serial}."""
    ok, _ = await goto(browser, f"{BASE_URL}/q/{serial}", must_contain=serial)
    if not ok:
        return None
    return await try_eval_js(browser, _JS_ASSET_STATUS)


# --------------------------------------------------------------------------
# Browser session cho từng vai trò
# --------------------------------------------------------------------------
def new_browser() -> Browser:
    # wait_for_network_idle thấp vì Vite dev server giữ WebSocket HMR luôn
    # mở, khiến "network idle" mặc định gần như không bao giờ đạt được.
    return Browser(
        headless=HEADLESS,
        keep_alive=True,
        wait_for_network_idle_page_load_time=0.5,
        minimum_wait_page_load_time=0.2,
    )


async def login(
    llm: BaseChatModel,
    use_vision: bool,
    account_key: str,
    label: str,
) -> tuple[Browser, Agent]:
    """Mở browser session và đăng nhập; trả về (browser, agent).

    Tách khỏi phần chạy task để có thể xen các bước kiểm tất định (đọc DOM
    qua CDP) vào giữa, trên cùng một session đã đăng nhập.
    """
    email = ACCOUNTS[account_key]
    print(f"\n--- {label} ({email}) ---", flush=True)

    browser = new_browser()
    await browser.start()
    agent = Agent(
        task=LOGIN_TASK.format(url=f"{BASE_URL}/login"),
        llm=llm,
        browser=browser,
        use_vision=use_vision,
        sensitive_data={"login_email": email, "login_password": PASSWORD},
    )
    await agent.run(max_steps=15)

    return browser, agent


async def run_tasks_on(agent: Agent, tasks: list[str]) -> None:
    """Chạy lần lượt các task trên một agent đã đăng nhập sẵn."""
    browser = agent.browser_session
    for i, task_text in enumerate(tasks, start=1):
        print(f"  Bước {i}/{len(tasks)}: {task_text[:70]}...", flush=True)
        agent.add_new_task(task_text)
        history = await agent.run(max_steps=40)
        if history.has_errors():
            print(f"  [!] Agent báo lỗi ở bước {i}: {history.errors()}", flush=True)
        url = await browser.get_current_page_url()
        print(f"      -> {url}", flush=True)
        print(f"      -> {history.final_result()}", flush=True)


# --------------------------------------------------------------------------
# Nội dung task của từng phase (giữ nguyên kiến thức UI đã kiểm chứng)
# --------------------------------------------------------------------------
PHASE1_TASKS = [
    f"Vào {BASE_URL}/assets (Danh mục thiết bị). Tạo lần lượt 3 "
    "thiết bị mới với Mã Serial No lần lượt là: "
    f"'{ASSET_SERIALS[0]}', '{ASSET_SERIALS[1]}', "
    f"'{ASSET_SERIALS[2]}' (mỗi thiết bị chọn dòng sản phẩm LED "
    f"'{PRODUCT_LINE_NAME}'). Xác nhận cả 3 thiết bị đã được tạo trong danh "
    "sách.",
    f"Vào {BASE_URL}/checkin-batches (Nhập kho / Check-in). Bấm nút "
    "tạo đợt nhập kho mới để mở form. TRONG FORM VỪA MỞ, việc DUY "
    "NHẤT cần làm ở bước này là điền field 'Đại lý tiếp nhận (Nếu "
    "có)' — đây là 1 dropdown/select có ô tìm kiếm (đang hiện "
    "placeholder mờ '— Nhập về Kho Tổng (HQ) —', tức là CHƯA có giá "
    "trị nào được chọn). Làm TỪNG bước: (1) Click/bấm trực tiếp vào "
    "ô input của field 'Đại lý tiếp nhận' để nó mở ra danh sách lựa "
    "chọn (hoặc trở thành ô nhập liệu có thể gõ). (2) Gõ chữ 'Hải "
    "Phòng' vào ô đó để lọc danh sách. (3) Trong danh sách kết quả "
    f"hiện ra bên dưới, CLICK vào đúng dòng '{AGENCY_NAME}' để "
    "chọn nó — không chỉ gõ chữ rồi để đó, PHẢI click chọn dòng kết "
    "quả thì giá trị mới thực sự được set. (4) Sau khi chọn, kiểm "
    "tra lại: field 'Đại lý tiếp nhận' phải hiển thị chữ "
    f"'{AGENCY_NAME}' (không còn placeholder mờ), VÀ field 'Kho lưu trữ "
    f"tiếp nhận' bên cạnh phải tự động đổi thành '{AGENCY_WAREHOUSE_NAME}'. "
    "Nếu vẫn thấy kho khác (VD 'Kho Hà Nội'), nghĩa là chưa chọn "
    "đúng — thử lại bước (1)-(3). KHÔNG chọn thiết bị, KHÔNG bấm nút "
    "Tạo/Lưu ở bước này — chỉ chọn đại lý rồi dừng lại, để form mở "
    "nguyên cho bước sau.",
    "Form tạo đợt nhập kho đang mở sẵn (đã chọn đại lý Hải Phòng ở "
    "bước trước, kho đã tự điền "
    f"'{AGENCY_WAREHOUSE_NAME}'). Nếu field đại lý "
    f"CHƯA có giá trị '{AGENCY_NAME}', chọn lại trước khi làm "
    "tiếp. Điền field 'Ngày dự kiến' = ngày hôm nay. Giờ tick chọn "
    f"cả 3 thiết bị vừa tạo ({', '.join(ASSET_SERIALS)}) trong danh "
    "sách 'Mã seri gợi ý từ kho' rồi bấm nút Tạo để lưu đợt nhập "
    "kho.",
    "Mở đợt nhập kho vừa tạo. Bấm 'Nhận hàng' cho CẢ 3 thiết bị "
    f"({', '.join(ASSET_SERIALS)}) với tình trạng Bình thường, xác "
    "nhận nhận hàng cho từng thiết bị. Sau khi cả 3/3 đã nhận, bấm "
    "'Kết thúc nhận hàng' để hoàn tất đợt nhập kho.",
]

PHASE2_TASKS = [
    f"Vào {BASE_URL}/orders (Đơn hàng) và bấm nút tạo đơn hàng mới. "
    "Điền theo đúng thứ tự sau:\n"
    "(1) Chọn KHÁCH HÀNG là khách có mã "
    f"'{CUSTOMER_HINT}' (Tập đoàn Vingroup — VinFast & Vincom Events "
    "Hải Phòng).\n"
    "(2) Trong bảng 'Danh sách thiết bị định mức xuất kho', ở DÒNG ĐẦU "
    "TIÊN chọn 'Dòng SP LED' = 'P2.6 Sự kiện' và 'SL Cần' = 3. Ngay khi "
    "chọn xong dòng sản phẩm, ô 'Đơn giá' sẽ TỰ ĐỘNG được điền và ô "
    "'Tổng giá trị đơn hàng' sẽ TỰ ĐỘNG được tính. Với tài khoản đại lý, "
    "hai ô 'Tổng giá trị đơn hàng' và 'Tổng diện tích màn hình' bị KHOÁ "
    "(không gõ tay vào được) — điều đó là bình thường, KHÔNG cố gắng gõ "
    "vào chúng.\n"
    "(3) Đặt 'Ngày bắt đầu sự kiện' = "
    f"{ORDER_START_DATE.strftime('%d/%m/%Y')} và 'Ngày dự kiến hoàn trả' = "
    f"{ORDER_END_DATE.strftime('%d/%m/%Y')}. Sau khi đặt xong ngày, NHÌN "
    "LẠI số đang hiển thị ở ô 'Tổng giá trị đơn hàng' và ghi nhớ con số "
    "đó.\n"
    "(4) Trong mục 'Thông tin thanh toán', điền 'Tổng tiền đã thu' bằng "
    "ĐÚNG con số vừa đọc ở 'Tổng giá trị đơn hàng' (đơn phải thu đủ 100% "
    "mới 'Hoàn tất Đơn hàng' được ở bước sau). Nếu 'Tổng giá trị đơn "
    "hàng' vẫn đang là 0, nghĩa là bước (2) chưa chọn đúng dòng sản "
    "phẩm — quay lại bước (2).\n"
    "Cuối cùng bấm nút Tạo/Lưu để lưu đơn hàng. Nếu màn hình báo lỗi "
    "'Thiếu thiết bị khả dụng trong khoảng ngày đã chọn', hãy báo lại "
    "nguyên văn thông báo lỗi đó và DỪNG, không thử lại nhiều lần.",
    f"Ở {BASE_URL}/orders, click vào dòng đơn hàng vừa tạo (click "
    "vào mã đơn hàng hoặc dùng action 'Xem'/'Sửa') để MỞ TRANG CHI "
    "TIẾT đơn hàng đó (URL dạng /orders/{id}/edit). QUAN TRỌNG: các "
    "action tiếp theo (Tạo Đợt Xuất Kho / Thu hồi trả kho / Hoàn "
    "tất Đơn hàng) đều nằm ở đây dưới dạng NÚT BẤM RIÊNG trên đầu "
    "trang chi tiết (không phải dropdown, không phải ở trang danh "
    "sách) — chỉ hiện nút nào phù hợp với trạng thái hiện tại của "
    "đơn. Ở trang chi tiết này, tìm và bấm nút 'Tạo Đợt Xuất Kho'. "
    "Trong modal: giữ nguyên toggle 'Tự động chọn & gán thiết bị từ "
    "kho' đang BẬT, số lượng để mặc định (3), BẬT thêm toggle 'Đánh "
    "dấu đã kiểm đếm xong (Scan to Dispatch)' nếu có, rồi xác nhận. "
    "Sau khi modal đóng, vẫn ở trang chi tiết đơn hàng này, nếu "
    "thấy nút 'Xác nhận xuất kho' hoặc 'Dispatch' xuất hiện (nghĩa "
    "là đơn chưa tự động chuyển sang Dispatched), bấm nút đó để "
    "hoàn tất xuất kho. KHÔNG rời khỏi trang chi tiết đơn hàng này.",
    "Vẫn đang ở trang chi tiết đơn hàng đó (giờ đã xuất kho). Tìm "
    "và bấm nút 'Thu hồi trả kho' ngay trên đầu trang (chỉ hiện khi "
    "đơn đã Dispatched). Trong modal chấm điểm: với 2 thiết bị đầu "
    "tiên trong danh sách chọn tình trạng BÌNH THƯỜNG; với thiết bị "
    f"còn lại (serial '{DAMAGED_SERIAL}' nếu nhìn thấy serial, hoặc "
    "thiết bị cuối cùng trong danh sách) chọn tình trạng HỎNG HÓC "
    "và ghi chú 'vỡ module LED khi vận chuyển'. Đánh dấu 'Đã nhận' "
    "cho cả 3, rồi xác nhận để hoàn tất trả kho. KHÔNG rời khỏi "
    "trang chi tiết đơn hàng này — xác nhận đơn hàng đã chuyển sang "
    "trạng thái 'Đã thu hồi trả kho' (Returned) ngay trên trang.",
    "Vẫn đang ở trang chi tiết đơn hàng đó (giờ ở trạng thái "
    "Returned). Tìm và bấm nút 'Hoàn tất Đơn hàng' ngay trên đầu "
    "trang (chỉ hiện khi đơn ở trạng thái Returned). NẾU modal hiện "
    "ra 1 bảng 'Thiết bị đang sửa chữa chưa có chi phí' (do có "
    "thiết bị vừa đánh dấu Hỏng hóc ở bước trước chưa có chi phí "
    "sửa), điền một chi phí sửa chữa dự kiến hợp lý (VD 500000) "
    "vào ô chi phí của thiết bị đó — không cần chờ sửa xong, chỉ "
    "cần nhập chi phí dự kiến. Sau đó bấm nút xác nhận của modal để "
    "đóng đơn — xác nhận đơn hàng chuyển sang trạng thái 'Hoàn tất "
    "đơn hàng' (Completed) ngay trên trang.",
]

PHASE3_TASKS = [
    # Đóng phiếu bảo dưỡng NGAY TẠI trang "Bảo trì & Sửa chữa" (/repair-logs) —
    # nơi phiếu sửa chữa được sinh ra từ luồng nhập trả. KHÔNG mò sang /assets.
    f"Vào {BASE_URL}/repair-logs (menu 'Bảo trì & Sửa chữa'). Đây là danh "
    "sách các phiếu sửa chữa. Tìm dòng có cột 'Mã Serial Thiết bị' đúng bằng "
    f"'{DAMAGED_SERIAL}' — phiếu này đang ở trạng thái 'Đang chờ xử lý / Đang "
    "sửa' (Pending) và cột 'Ngày hoàn thành' còn trống/ghi 'Đang sửa chữa...', "
    "do thiết bị bị đánh dấu Hỏng hóc khi thu hồi ở bước trước. Trên dòng đó "
    "có 1 action riêng tên 'Hoàn thành sửa chữa' (icon dấu tích/huy hiệu màu "
    "xanh, nằm CẠNH action 'Cập nhật phiếu sửa chữa' — không phải bulk "
    "action, không cần tick checkbox). Bấm action đó. Trong modal 'Hoàn thành "
    "sửa chữa': giữ 'Ngày hoàn thành' = ngày hôm nay, chọn 'Kết quả bảo trì' "
    "= 'Đã sửa xong — Sẵn sàng sử dụng (Ready)', điền 'Tổng chi phí sửa chữa "
    "/ Linh kiện thực tế' = 500000, ghi chú 'Đã thay thế module LED, kiểm tra "
    "nguồn điện ổn định', rồi bấm 'Xác nhận hoàn thành'. Sau đó xác nhận dòng "
    "phiếu đã chuyển cột 'Kết quả' sang 'Đã sửa xong (Sẵn sàng)'. KHÔNG sang "
    "trang /assets để làm việc này.",
]


# --------------------------------------------------------------------------
async def main() -> int:
    llm, use_vision = build_llm()
    verdict = Verdict()

    print("=" * 68, flush=True)
    print("E2E Đại lý: nhập kho -> xuất kho theo đơn -> thu hồi -> sửa chữa -> doanh thu", flush=True)
    print("=" * 68, flush=True)
    print(f"Serial đợt này: {', '.join(ASSET_SERIALS)}", flush=True)
    print(f"Thiết bị sẽ đánh dấu hỏng: {DAMAGED_SERIAL}", flush=True)
    print(f"Lịch sự kiện: {ORDER_START_DATE} -> {ORDER_END_DATE}", flush=True)

    # ---------------- P0: baseline doanh thu ----------------
    print("\n" + "-" * 68, flush=True)
    print("P0 — Đại lý: chụp số liệu doanh thu TRƯỚC khi chạy", flush=True)
    print("-" * 68, flush=True)

    baseline_browser, _ = await login(
        llm, use_vision, "agency", "P0 - Đại lý (baseline)"
    )
    try:
        before = await read_revenue(baseline_browser, "P0")
    finally:
        await baseline_browser.close()

    if before is None:
        verdict.check(
            "P0",
            "Đọc được báo cáo doanh thu đại lý (baseline)",
            False,
            "Không đọc được /agency-revenue-report bằng tài khoản đại lý",
        )
        verdict.print_summary()
        return 1

    verdict.check(
        "P0",
        "Tài khoản đại lý mở được /agency-revenue-report và có tỷ lệ hoa hồng",
        before.rate > 0,
        f"tỷ lệ hiển thị = {before.rate}%",
    )

    admin_browser, admin_agent = await login(
        llm, use_vision, "admin", "P1 - Super Admin"
    )
    try:
        await run_tasks_on(admin_agent, PHASE1_TASKS)
    finally:
        await admin_browser.close()

    # ---------------- P1 (kiểm) + P2: đại lý ----------------
    # Phép kiểm tồn kho PHẢI đọc bằng chính tài khoản đại lý: với user thuộc
    # đại lý, getScopedAgencyId() khiến getStats() chỉ trả số của DL-HP — nhờ
    # vậy mới so trực tiếp được với baseline P0 (cũng đọc bằng tài khoản đại
    # lý). Nếu đọc bằng admin thì con số là tổng của MỌI đại lý và phép so sẽ
    # PASS giả.
    print("\n" + "-" * 68, flush=True)
    print("P2 — Đại lý: kiểm tồn kho -> đơn hàng -> xuất kho -> thu hồi -> hoàn tất", flush=True)
    print("-" * 68, flush=True)

    agency_browser, agency_agent = await login(
        llm, use_vision, "agency", "P2 - Agency Manager"
    )
    try:
        after_checkin = await read_revenue(agency_browser, "P1")
        if after_checkin is None:
            verdict.check(
                "P1",
                "Đọc được tồn kho đại lý sau khi nhập kho",
                False,
                "Không đọc được /agency-revenue-report bằng tài khoản đại lý",
            )
        else:
            verdict.check(
                "P1",
                "Nhập kho đã phân bổ thiết bị vào kho đại lý (tồn kho tăng)",
                after_checkin.inventory_area > before.inventory_area,
                f"tồn kho {before.inventory_area} -> {after_checkin.inventory_area} m²",
            )

        await run_tasks_on(agency_agent, PHASE2_TASKS)

        damaged_after_return = await read_asset_status(agency_browser, DAMAGED_SERIAL)
    finally:
        await agency_browser.close()

    p2_damage_ok = False
    if damaged_after_return is None:
        verdict.check(
            "P2",
            "Đọc được trạng thái thiết bị hỏng sau thu hồi",
            False,
            f"Không mở được /q/{DAMAGED_SERIAL}",
        )
    else:
        p2_damage_ok = verdict.check(
            "P2",
            "Thu hồi thiết bị Hỏng hóc đã chuyển asset sang Đang sửa chữa",
            damaged_after_return.get("statusClass") == STATUS_REPAIRING,
            f"status = {damaged_after_return.get('statusClass')} "
            f"({damaged_after_return.get('label')})",
        )
        verdict.check(
            "P2",
            "Thu hồi thiết bị Hỏng hóc có sinh phiếu bảo dưỡng (RepairLog)",
            (damaged_after_return.get("repairLogCount") or 0) >= 1,
            f"số phiếu bảo dưỡng = {damaged_after_return.get('repairLogCount')}",
        )
        verdict.check(
            "P2",
            "Phiếu bảo dưỡng vừa sinh đang ở trạng thái chờ xử lý (Pending)",
            damaged_after_return.get("repairResultClass") == REPAIR_BADGE_PENDING,
            f"kết quả phiếu = {damaged_after_return.get('repairResultClass')} "
            f"({damaged_after_return.get('repairResultLabel')})",
        )

    # ---------------- P3: sửa chữa ----------------
    print("\n" + "-" * 68, flush=True)
    print("P3 — Kỹ thuật đại lý: hoàn thành sửa chữa", flush=True)
    print("-" * 68, flush=True)

    tech_browser, tech_agent = await login(
        llm, use_vision, "agency_technician", "P3 - Kỹ thuật đại lý"
    )
    try:
        await run_tasks_on(tech_agent, PHASE3_TASKS)
        damaged_after_repair = await read_asset_status(tech_browser, DAMAGED_SERIAL)
    finally:
        await tech_browser.close()

    if damaged_after_repair is None:
        verdict.check(
            "P3",
            "Đọc được trạng thái thiết bị sau sửa chữa",
            False,
            f"Không mở được /q/{DAMAGED_SERIAL}",
        )
    else:
        # P3 CHỈ có nghĩa khi P2 đã thực sự đẩy thiết bị sang Đang sửa chữa.
        # Nếu không, asset vốn đã Sẵn sàng từ đầu và phép kiểm sẽ PASS rỗng —
        # đúng cái bẫy đã xảy ra ở lần chạy 19/09 04:41, khi agent tự tay
        # "Bảo trì" rồi "Hoàn thành sửa chữa" một thiết bị chưa từng hỏng.
        verdict.check(
            "P3",
            "Thiết bị hỏng đã sửa xong và trở về trạng thái Sẵn sàng",
            p2_damage_ok
            and damaged_after_repair.get("statusClass") == STATUS_READY,
            f"status = {damaged_after_repair.get('statusClass')} "
            f"({damaged_after_repair.get('label')}); "
            f"P2 đã đưa vào sửa chữa trước đó = {p2_damage_ok}",
        )
        # Chứng minh phiếu bảo dưỡng đã thực sự được ĐÓNG ở /repair-logs,
        # chứ không chỉ Asset đổi trạng thái.
        verdict.check(
            "P3",
            "Phiếu bảo dưỡng đã được đóng (kết quả = Đã sửa xong)",
            damaged_after_repair.get("repairResultClass") == REPAIR_BADGE_FIXED,
            f"kết quả phiếu = {damaged_after_repair.get('repairResultClass')} "
            f"({damaged_after_repair.get('repairResultLabel')})",
        )

    # ---------------- P4: đối chiếu doanh thu ----------------
    print("\n" + "-" * 68, flush=True)
    print("P4 — Đại lý: đối chiếu doanh thu & hoa hồng sau luồng", flush=True)
    print("-" * 68, flush=True)

    final_browser, _ = await login(
        llm, use_vision, "agency", "P4 - Đại lý (đối chiếu)"
    )
    try:
        after = await read_revenue(final_browser, "P4")
    finally:
        await final_browser.close()

    if after is None:
        verdict.check("P4", "Đọc được báo cáo doanh thu sau luồng", False)
    else:
        delta_orders = after.orders - before.orders
        delta_revenue = after.revenue - before.revenue
        delta_collected = after.collected - before.collected
        delta_commission = after.commission - before.commission

        print(
            f"\n  Δdoanh_thu={delta_revenue} Δthực_thu={delta_collected} "
            f"Δhoa_hồng={delta_commission} Δđơn={delta_orders}\n",
            flush=True,
        )

        # Xuất kho KHÔNG gắn đơn hàng không được sinh doanh thu => đúng 1 đơn
        # mới, không hơn.
        verdict.equal("P4", "Luồng tạo đúng 1 đơn hàng mới", delta_orders, 1)

        if delta_orders != 1:
            print(
                "\n  [!] P2 KHÔNG tạo được đơn hàng mới. Nguyên nhân hay gặp nhất:\n"
                "      Còn đơn E2E của lần chạy TRƯỚC đang ở trạng thái 'Đã xuất kho'\n"
                "      (Dispatched) mà chưa thu hồi. Đơn đó vẫn giữ chỗ thiết bị, nên\n"
                "      OrderForm::validateAvailability() chặn tạo đơn mới và báo\n"
                "      'Thiếu thiết bị khả dụng trong khoảng ngày đã chọn'.\n"
                "      -> Reset DB dev rồi seed lại:  php artisan app:bootstrap\n",
                flush=True,
            )

        verdict.check(
            "P4",
            "Đơn hàng mới đã vào doanh thu đại lý (Δdoanh_thu > 0)",
            delta_revenue > 0,
            f"Δdoanh_thu = {delta_revenue:,.0f}",
        )

        # P2 cố ý điền total_paid == value. Nếu agent điền thiếu, hoặc có
        # doanh thu "ma" từ đợt xuất kho không gắn đơn, phép kiểm này trượt.
        verdict.equal(
            "P4",
            "Δdoanh_thu == Δthực_thu (không có doanh thu ngoài đơn hàng)",
            delta_revenue,
            delta_collected,
            tolerance=MONEY_TOLERANCE,
        )

        expected_commission = delta_collected * after.rate / 100.0
        verdict.equal(
            "P4",
            f"Hoa hồng = Δthực_thu × {after.rate}% (ăn theo tiền thực thu)",
            delta_commission,
            expected_commission,
            tolerance=MONEY_TOLERANCE,
        )

        verdict.check(
            "P4",
            "Tỷ lệ hoa hồng đại lý không đổi trong suốt luồng",
            after.rate == before.rate,
            f"{before.rate}% -> {after.rate}%",
        )

    verdict.print_summary()
    return 1 if verdict.failed else 0


if __name__ == "__main__":
    sys.exit(asyncio.run(main()))
