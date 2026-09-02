@php
    use App\Models\Customer;
    use App\Models\Order;
    use App\Models\Quotation;
    use App\Models\User;
    use App\Services\VietnameseCurrencyReader;
    use Carbon\Carbon;

    // Fetch reactive form state
    $code = $get('code') ?: 'HD-' . date('ym') . '-01';
    $title = $get('title') ?: 'Hợp đồng dịch vụ cho thuê & lắp đặt màn hình LED sự kiện';
    $status = $get('status') ?: 'draft';

    $customerId = $get('customer_id');
    $customer = $customerId ? Customer::find($customerId) : ($record?->customer ?? null);

    $quotationId = $get('quotation_id');
    $quotation = $quotationId ? Quotation::with('items.deviceType', 'productLine')->find($quotationId) : ($record?->quotation ?? null);

    $orderId = $get('order_id');
    $order = $orderId ? Order::with('items.deviceType')->find($orderId) : ($record?->order ?? null);

    $salesUserId = $get('sales_user_id');
    $salesUser = $salesUserId ? User::find($salesUserId) : ($record?->salesUser ?? auth()->user());

    $contractValue = (float) str_replace(',', '', (string) ($get('contract_value') ?: ($record?->contract_value ?? 0)));
    $depositPercent = (float) ($get('deposit_percent') ?: ($record?->deposit_percent ?? 50));
    $depositAmount = (float) str_replace(',', '', (string) ($get('deposit_amount') ?: round($contractValue * ($depositPercent / 100))));
    $remainingAmount = max(0, $contractValue - $depositAmount);

    $signedDateRaw = $get('signed_date') ?: ($record?->signed_date ?? now());
    $signedDate = $signedDateRaw ? Carbon::parse($signedDateRaw) : now();

    $startDateRaw = $get('start_date') ?: ($record?->start_date ?? null);
    $startDate = $startDateRaw ? Carbon::parse($startDateRaw) : null;

    $endDateRaw = $get('end_date') ?: ($record?->end_date ?? null);
    $endDate = $endDateRaw ? Carbon::parse($endDateRaw) : null;

    $terms = $get('terms') ?: ($record?->terms ?? null);

    // Quotation fallback from order
    if (! $quotation && $order && $order->quotation_id) {
        $quotation = Quotation::with('items.deviceType', 'productLine')->find($order->quotation_id);
    }

    // Items for appendix
    $items = [];
    if ($quotation && $quotation->items->isNotEmpty()) {
        foreach ($quotation->items as $item) {
            $items[] = [
                'name' => $item->deviceType?->name ?: 'Màn hình LED & Thiết bị',
                'description' => $item->description ?: 'Theo tiêu chuẩn kỹ thuật',
                'unit' => $item->deviceType?->unit?->getLabel() ?? 'Cái',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_cost,
                'line_total' => (float) $item->line_total,
            ];
        }
    } elseif ($order && $order->items->isNotEmpty()) {
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->deviceType?->name ?: 'Thiết bị xuất kho',
                'description' => $item->note ?: 'Theo danh mục xuất kho',
                'unit' => $item->deviceType?->unit?->getLabel() ?? 'Cái',
                'quantity' => (float) $item->quantity_required,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) ($item->quantity_required * $item->unit_price),
            ];
        }
    }

    // 2. Add Technician / Crew Labour Line
    if ($quotation) {
        $crewSize = (int) ($quotation->crew_size ?: 4);
        $rentalDays = (int) ($quotation->rental_days ?: 1);
        $crewRate = (float) ($quotation->crew_rate ?: 1600000);
        $labourCost = (float) ($quotation->labour_cost ?: ($crewSize * $rentalDays * $crewRate));

        if ($labourCost > 0 || $crewSize > 0) {
            $items[] = [
                'name' => 'Nhân sự kỹ thuật thi công & Vận hành',
                'description' => "Đội ngũ {$crewSize} thợ kỹ thuật vận hành trực tiếp tại hiện trường ({$rentalDays} ngày)",
                'unit' => 'Công',
                'quantity' => (float) ($crewSize * $rentalDays),
                'unit_price' => (float) $crewRate,
                'line_total' => (float) $labourCost,
            ];
        }

        // 3. Add Transportation Line
        $transportDistanceKm = (float) ($quotation->transport_distance_km ?: 45);
        $transportRate = (float) ($quotation->transport_rate ?: 28000);
        $transportTotalKm = $transportDistanceKm * 2;
        $transportCost = (float) ($quotation->transport_cost ?: ($transportTotalKm * $transportRate));

        if ($transportCost > 0 || $transportDistanceKm > 0) {
            $items[] = [
                'name' => 'Vận chuyển thiết bị & Bốc xếp 2 chiều',
                'description' => "Vận chuyển thiết bị chuyên dụng 2 chiều (cự ly {$transportDistanceKm} km × 2 lượt) và bốc xếp",
                'unit' => 'Km',
                'quantity' => (float) $transportTotalKm,
                'unit_price' => (float) $transportRate,
                'line_total' => (float) $transportCost,
            ];
        }

        // 4. Add Accessories if available
        if ((float) $quotation->accessory_cost > 0) {
            $items[] = [
                'name' => 'Vật tư phụ & Phụ kiện đi kèm',
                'description' => 'Dây nguồn, cáp tín hiệu và vật tư phụ kiện phục vụ lắp đặt, vận hành',
                'unit' => 'Gói',
                'quantity' => 1,
                'unit_price' => (float) $quotation->accessory_cost,
                'line_total' => (float) $quotation->accessory_cost,
            ];
        }

        // 5. Add Discount if available
        if ((float) $quotation->discount_amount > 0) {
            $items[] = [
                'name' => 'Chiết khấu thương mại / Ưu đãi sự kiện',
                'description' => 'Giảm trừ trực tiếp trên tổng giá trị dịch vụ',
                'unit' => 'Gói',
                'quantity' => 1,
                'unit_price' => -(float) $quotation->discount_amount,
                'line_total' => -(float) $quotation->discount_amount,
            ];
        }
    } elseif ($order && $order->assignments->isNotEmpty()) {
        $crewCount = $order->assignments->count();
        $days = max(1, (int) Carbon::parse($order->request_date)->diffInDays(Carbon::parse($order->expected_return_date)));
        $crewRate = 1600000;
        $items[] = [
            'name' => 'Nhân sự kỹ thuật thi công & Vận hành',
            'description' => "Đội ngũ {$crewCount} kỹ thuật viên phân công trực tiếp tại sự kiện ({$days} ngày)",
            'unit' => 'Công',
            'quantity' => (float) ($crewCount * $days),
            'unit_price' => (float) $crewRate,
            'line_total' => (float) ($crewCount * $days * $crewRate),
        ];
    }

    $subtotalItems = array_sum(array_column($items, 'line_total'));
    // ponytail: contract_value is the final payable amount; no VAT math.
    $displayTotal = $contractValue > 0 ? (float) $contractValue : max(0, $subtotalItems);
@endphp

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #official-a4-document, #official-a4-document * {
        visibility: visible !important;
    }
    #official-a4-document {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 10mm 15mm !important;
        border: none !important;
        box-shadow: none !important;
        background: white !important;
        color: black !important;
    }
    @page {
        size: A4 portrait;
        margin: 10mm 15mm;
    }
}
</style>

<div x-data="{ activeTab: 'contract' }"
    x-init="
        window.printContractDoc = function(tab) {
            var doc = document.getElementById('official-a4-document');
            if (!doc) return;
            var el = doc.querySelector('[data-tab-content=' + JSON.stringify(tab) + ']');
            if (!el) return;
            var w = window.open('', '_blank', 'width=900,height=800');
            if (!w) { window.print(); return; }
            var t = { contract: 'Hop-dong-dich-vu-{{ $code }}', appendix: 'Phu-luc-hop-dong-PL-{{ $code }}', handover: 'Bien-ban-nghiem-thu-{{ $code }}' };
            var css = '@page{size:A4 portrait;margin:15mm 20mm}body{background:#fff!important;color:#000!important;font-family:Times New Roman,Times,Georgia,serif!important;font-size:13px!important;line-height:1.6!important;margin:0!important;padding:0!important}table{border-collapse:collapse!important;width:100%!important;margin-top:6px!important}th,td{border:1px solid #000!important;padding:5px 8px!important;font-size:13px!important}*{color:#000!important;box-shadow:none!important}';
            w.document.open();
            w.document.write('<!DOCTYPE html><html lang=vi><head><meta charset=UTF-8><title>' + (t[tab]||'Van-ban') + '<\/title><style>' + css + '<\/style><\/head><body><div style=max-width:800px;margin:0_auto;padding:10px_20px>' + el.innerHTML + '<\/div><\/body><\/html>');
            w.document.close();
            setTimeout(function(){ w.focus(); w.print(); w.close(); }, 300);
        }
    "
    class="w-full bg-zinc-100 dark:bg-zinc-950 p-2 sm:p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-inner">

    <!-- Top Tab Selector -->
    <div
        class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4 border-b border-zinc-200 dark:border-zinc-800">
        <div class="inline-flex rounded-lg bg-zinc-200 dark:bg-zinc-800 p-1 text-xs font-medium font-sans">
            <button type="button" @click="activeTab = 'contract'"
                :class="activeTab === 'contract' ? 'bg-white text-zinc-950 shadow-xs dark:bg-zinc-700 dark:text-white font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'"
                class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                1. Hợp đồng chính
            </button>
            <button type="button" @click="activeTab = 'appendix'"
                :class="activeTab === 'appendix' ? 'bg-white text-zinc-950 shadow-xs dark:bg-zinc-700 dark:text-white font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'"
                class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                2. Phụ lục hợp đồng
            </button>
            <button type="button" @click="activeTab = 'handover'"
                :class="activeTab === 'handover' ? 'bg-white text-zinc-950 shadow-xs dark:bg-zinc-700 dark:text-white font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900'"
                class="px-3 py-1.5 rounded-md transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                3. Biên bản nghiệm thu
            </button>
        </div>

        <div class="flex items-center gap-2 text-xs font-sans text-zinc-500">
            <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-200 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Tự động đồng bộ
            </span>
            <button type="button" @click="window.printContractDoc(activeTab)"
                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:bg-zinc-50 text-zinc-800 dark:text-zinc-200 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                In văn bản
            </button>
        </div>
    </div>

    <!-- Official A4 Document: Uniform Font, Black Text, No Unnecessary Backgrounds -->
    <div id="official-a4-document"
        class="max-w-[850px] mx-auto bg-white text-black shadow-md border border-zinc-300 rounded-none p-8 sm:p-14 font-serif text-[13px] leading-relaxed transition-all">

        <!-- ================= TAB 1: MAIN CONTRACT ================= -->
        <div x-show="activeTab === 'contract'" data-tab-content="contract" x-cloak class="space-y-3.5 text-black">
            <!-- Header -->
            <div class="text-center space-y-0.5">
                <p class="font-bold uppercase tracking-wide">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                <p class="font-bold">Độc lập – Tự do – Hạnh phúc</p>
                <div class="w-36 h-[1px] bg-black mx-auto my-1"></div>
                <p class="pt-1">Số HĐ: <span class="font-bold">{{ $code }}</span></p>
            </div>

            <!-- Title -->
            <div class="text-center pt-2">
                <p class="font-bold uppercase tracking-wide">HỢP ĐỒNG DỊCH VỤ</p>
            </div>

            <!-- Legal Basis -->
            <div class="space-y-0.5 pt-1">
                <p>– Căn cứ Bộ luật dân sự của nước Cộng hòa Xã hội Chủ nghĩa Việt Nam được Quốc hội khóa 11 thông qua
                    ngày 14/6/2006;</p>
                <p>– Căn cứ Luật Thương mại số 36/2005/QH11 của nước Cộng hoà Xã hội Chủ nghĩa Việt Nam số 36/2005/QH11
                    có hiệu lực thi hành từ ngày 01/01/2006;</p>
                <p>– Căn cứ khả năng và nhu cầu của các bên;</p>
            </div>

            <!-- Date -->
            <p class="pt-1">
                Hôm nay, ngày {{ $signedDate->format('d') }} tháng {{ $signedDate->format('m') }} năm
                {{ $signedDate->format('Y') }}, Chúng tôi gồm:
            </p>

            <!-- Party A -->
            <div class="space-y-0.5">
                <p><span class="font-bold">BÊN A</span> : <span
                        class="font-bold">{{ $customer?->name ?: '..................................................................................................................................' }}</span>
                </p>
                <p>Đại diện : <span
                        class="font-bold">{{ $customer?->contact_person ?: ($customer?->name ?: '........................................................') }}</span>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Chức vụ: <span class="font-bold">Đại diện theo pháp luật</span></p>
                <p>Địa chỉ :
                    {{ $customer?->address ?: '..................................................................................................................................' }}
                </p>
                <p>Mã số thuế : <span
                        class="font-bold">{{ $customer?->tax_code ?: '........................................................' }}</span>
                </p>
                <p>Điện thoại : <span
                        class="font-bold">{{ $customer?->phone ?: '........................................................' }}</span>
                </p>
            </div>

            <!-- Party B -->
            <div class="space-y-0.5 pt-1">
                <p><span class="font-bold">BÊN B</span> : <span class="font-bold">CÔNG TY TNHH GIẢI PHÁP MÀN HÌNH LED OS
                        VIỆT NAM</span></p>
                <p>Địa chỉ : Tầng 5, Tòa nhà Văn phòng LED OS, TP. Hà Nội</p>
                <p>Đại diện : <span class="font-bold">{{ $salesUser?->name ?: 'Nguyễn Văn Quản Trị' }}</span>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Chức vụ: <span class="font-bold">Đại diện kinh doanh</span></p>
                <p>MST : <span class="font-bold">0109887766</span></p>
                <p>Điện thoại : <span class="font-bold">0912 345 678</span>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    Website: <span class="font-bold">ledmanager.vn</span></p>
            </div>

            <p class="pt-1">
                Hai bên thống nhất ký kết hợp đồng với các điều khoản sau đây:
            </p>

            <!-- Clause 1 -->
            <div class="space-y-1">
                <p class="font-bold">ĐIỀU 1: NỘI DUNG CUNG CẤP DỊCH VỤ</p>
                <p class="text-justify">
                    Theo Hợp đồng này, Bên B sẽ cung cấp gói dịch vụ cho bên A với chi tiết các hạng mục như trong phụ
                    lục báo giá đính kèm hợp đồng này.
                </p>
                <p>Địa điểm: <span
                        class="font-bold">{{ $order?->event ?: ($quotation?->event_name ?: 'Theo thỏa thuận tại địa điểm của Bên A') }}</span>
                </p>
                <p>Thời gian:
                    @if($startDate && $endDate)
                        Từ ngày <span class="font-bold">{{ $startDate->format('d/m/Y') }}</span> đến ngày <span
                            class="font-bold">{{ $endDate->format('d/m/Y') }}</span>
                    @else
                        Theo lịch trình thống nhất giữa hai bên
                    @endif
                </p>
            </div>

            <!-- Clause 2 -->
            <div class="space-y-1">
                <p class="font-bold">ĐIỀU 2: GIÁ TRỊ HỢP ĐỒNG VÀ ĐIỀU KIỆN THANH TOÁN</p>
                <p class="text-justify">
                    Tổng giá trị hợp đồng: <span
                        class="font-bold">{{ number_format($displayTotal, 0, ',', '.') }} VNĐ</span> (Số tiền bằng chữ:
                    <span class="font-bold">{{ VietnameseCurrencyReader::convert($displayTotal) }}</span>).
                </p>
                <p class="font-bold">Phương thức thanh toán:</p>
                <p class="text-justify">
                    Bên A đặt cọc cho bên B {{ (int) $depositPercent }}% giá trị hợp đồng với số tiền là: <span
                        class="font-bold">{{ number_format($depositAmount, 0, ',', '.') }} VNĐ</span> (Số tiền bằng chữ:
                    <span class="font-bold">{{ VietnameseCurrencyReader::convert($depositAmount) }}</span>).
                </p>
                <p class="text-justify">
                    Bên A thanh toán cho bên B {{ 100 - (int) $depositPercent }}% giá trị hợp đồng còn lại với số tiền
                    là: <span class="font-bold">{{ number_format($remainingAmount, 0, ',', '.') }} VNĐ</span> (Số tiền
                    bằng chữ: <span class="font-bold">{{ VietnameseCurrencyReader::convert($remainingAmount) }}</span>)
                    sau khi kết thúc sự kiện cộng với khoản phát sinh (Nếu có) sau khi kết thúc sự kiện và nhận được hóa
                    đơn của bên B.
                </p>
                <p>Hình thức thanh toán: Chuyển khoản hoặc tiền mặt.</p>
            </div>

            <!-- Clause 3 -->
            <div class="space-y-1">
                <p class="font-bold">ĐIỀU 3: QUYỀN VÀ NGHĨA VỤ CỦA BÊN A</p>
                <p class="text-justify">– Cung cấp thông tin, thông báo thời gian, địa điểm và tiến hành thảo luận về
                    cách thức làm việc cho bên B trước thời gian thực hiện.</p>
                <p class="text-justify">– Tạo điều kiện thuận lợi, bố trí cán bộ hỗ trợ cho Bên B khi có yêu cầu trong
                    việc thực hiện Hợp đồng.</p>
                <p class="text-justify">– Có trách nhiệm bảo mật thông tin các hợp đồng, tài liệu liên quan, đơn giá của
                    hợp đồng.</p>
                <p class="text-justify">– Thanh toán đầy đủ và đúng hạn các khoản chi phí cho bên B như đã nêu tại Điều
                    2 của hợp đồng này.</p>
            </div>

            <!-- Clause 4 -->
            <div class="space-y-1">
                <p class="font-bold">ĐIỀU 4: QUYỀN VÀ NGHĨA VỤ CỦA BÊN B</p>
                <p class="text-justify">– Bên B đảm bảo thực hiện theo đúng yêu cầu nội dung công việc bên A đề ra.</p>
                <p class="text-justify">– Cử 01 (một) người đại diện làm việc với bên A thường xuyên liên lạc với bên A
                    để đảm bảo tiếp nhận thông tin kịp thời.</p>
                <p class="text-justify">– Bên B chỉ tiến hành công việc sau khi đã nhận được khoản thanh toán đợt 1 của
                    bên A cùng với bản hợp đồng được ký kết hợp pháp giữa hai bên.</p>
                <p class="text-justify">– Đảm bảo cung cấp các hạng mục cho bên A theo đúng thời gian và địa điểm quy
                    định trong hợp đồng.</p>
                <p class="text-justify">– Bên B có trách nhiệm bảo mật các thông tin liên quan đến sự kiện của Bên A.
                </p>
            </div>

            <!-- Clause 5 -->
            <div class="space-y-1">
                <p class="font-bold">ĐIỀU 5: TRƯỜNG HỢP BẤT KHẢ KHÁNG</p>
                <p class="text-justify">
                    Các Bên được phép trì hoãn hoặc không thực hiện một phần hay toàn bộ các nghĩa vụ của Hợp Đồng mà
                    nguyên nhân là do bất khả kháng gây ra như chiến tranh, bạo loạn, đình công, hoả hoạn, lũ lụt, dịch
                    bệnh, cấm vận kinh tế, khủng hoảng tài chính, v..v. Bên tuyên bố gặp bất khả kháng phải có công văn
                    gửi đến cho Bên kia ngay sau khi bất khả kháng xảy ra trong vòng 02 ngày.
                </p>
            </div>

            <!-- Clause 6 -->
            <div class="space-y-1">
                <p class="font-bold">ĐIỀU 6: ĐIỀU KHOẢN CHUNG</p>
                <p class="text-justify">– Hợp đồng có hiệu lực sau ngày ký và sau khi bên A thanh toán đủ cho bên B.</p>
                <p class="text-justify">– Hai bên cam kết tôn trọng và thực hiện nghiêm túc các điều khoản đã nêu trong
                    hợp đồng này.</p>
                <p class="text-justify">– Trong quá trình thực hiện nếu gặp khó khăn, vướng mắc thì phải kịp thời thông
                    báo cho bên kia bằng văn bản để cùng bàn bạc giải quyết trên tinh thần hợp tác thương lượng.</p>
                <p class="text-justify">– Trong trường hợp có tranh chấp, hai bên sẽ cùng bàn bạc trên tinh thần hợp
                    tác. Nếu không giải quyết được, vấn đề sẽ được mang ra Tòa án Kinh Tế Tp.Hà Nội để xét xử, theo
                    trình tự tố tụng xét xử các tranh chấp trong hoạt động thương mại.</p>
                <p class="text-justify">– Hợp đồng được tự động thanh lý sau khi các bên đã hoàn thành các quyền và
                    nghĩa vụ quy định tại Hợp đồng này.</p>
                @if($terms)
                    <p class="text-justify"><span class="font-bold">– Điều khoản bổ sung:</span> {{ $terms }}</p>
                @endif
                <p class="text-justify">– Hợp đồng được lập thành 02 bản, Bên A giữ 01 bản, Bên B giữ 01 bản, cùng có
                    giá trị pháp lý như nhau.</p>
            </div>

            <!-- Signatures -->
            <div class="pt-8 grid grid-cols-2 text-center">
                <div>
                    <p class="font-bold uppercase">ĐẠI DIỆN BÊN A</p>
                    <div class="h-24"></div>
                    <p class="font-bold">
                        {{ $customer?->contact_person ?: ($customer?->name ?: '........................................................') }}
                    </p>
                </div>
                <div>
                    <p class="font-bold uppercase">ĐẠI DIỆN BÊN B</p>
                    <div class="h-24"></div>
                    <p class="font-bold">{{ $salesUser?->name ?: 'Nguyễn Văn Quản Trị' }}</p>
                </div>
            </div>
        </div>

        <!-- ================= TAB 2: APPENDIX ================= -->
        <div x-show="activeTab === 'appendix'" data-tab-content="appendix" x-cloak class="space-y-3.5 text-black">
            <!-- Header -->
            <div class="text-center space-y-0.5">
                <p class="font-bold uppercase tracking-wide">PHỤ LỤC HỢP ĐỒNG</p>
                <p>Số: <span class="font-bold">PL-{{ $code }}</span></p>
                <p class="font-bold uppercase pt-1">BÁO GIÁ CÁC HẠNG MỤC THIẾT BỊ & DỊCH VỤ</p>
            </div>

            <div class="space-y-0.5 pt-1">
                <p>– Căn cứ Hợp đồng dịch vụ số: <span class="font-bold">{{ $code }}</span> ký ngày
                    {{ $signedDate->format('d/m/Y') }};</p>
                <p>– Tên chương trình / Sự kiện: <span class="font-bold">{{ $title }}</span></p>
            </div>

            <!-- Table -->
            <div class="pt-1">
                <table class="w-full border-collapse border border-black text-[13px]">
                    <thead>
                        <tr class="font-bold text-center">
                            <th class="border border-black p-1.5 w-10">STT</th>
                            <th class="border border-black p-1.5 text-left">HẠNG MỤC</th>
                            <th class="border border-black p-1.5 text-left">Chi tiết</th>
                            <th class="border border-black p-1.5 w-14">ĐVT</th>
                            <th class="border border-black p-1.5 w-14">SỐ LƯỢNG</th>
                            <th class="border border-black p-1.5 text-right w-24">ĐƠN GIÁ</th>
                            <th class="border border-black p-1.5 text-right w-28">THÀNH TIỀN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($items) > 0)
                            @foreach($items as $idx => $it)
                                <tr>
                                    <td class="border border-black p-1.5 text-center">{{ $idx + 1 }}</td>
                                    <td class="border border-black p-1.5 font-bold">{{ $it['name'] }}</td>
                                    <td class="border border-black p-1.5">{{ $it['description'] ?: 'Theo quy chuẩn kỹ thuật' }}
                                    </td>
                                    <td class="border border-black p-1.5 text-center">{{ $it['unit'] }}</td>
                                    <td class="border border-black p-1.5 text-center font-bold">{{ $it['quantity'] }}</td>
                                    <td class="border border-black p-1.5 text-right">
                                        {{ number_format($it['unit_price'], 0, ',', '.') }}</td>
                                    <td class="border border-black p-1.5 text-right font-bold">
                                        {{ number_format($it['line_total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="border border-black p-1.5 text-center">1</td>
                                <td class="border border-black p-1.5 font-bold">Màn hình LED & Thiết bị trọn gói</td>
                                <td class="border border-black p-1.5">Hệ thống cabinet LED, Processor 4K, khung giàn truss,
                                    cáp nguồn tín hiệu và kỹ thuật vận hành</td>
                                <td class="border border-black p-1.5 text-center">gói</td>
                                <td class="border border-black p-1.5 text-center font-bold">1</td>
                                <td class="border border-black p-1.5 text-right">
                                    {{ number_format($displayTotal, 0, ',', '.') }}</td>
                                <td class="border border-black p-1.5 text-right font-bold">
                                    {{ number_format($displayTotal, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        <!-- Summary row -->
                        <tr>
                            <td colspan="6" class="border border-black p-1.5 text-right font-bold uppercase">TỔNG CỘNG THANH TOÁN</td>
                            <td class="border border-black p-1.5 text-right font-bold">
                                {{ number_format($displayTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="pt-1">
                Số tiền bằng chữ: <span
                    class="font-bold">{{ VietnameseCurrencyReader::convert($displayTotal) }}</span>.
            </p>

            <!-- Signatures -->
            <div class="pt-8 grid grid-cols-2 text-center">
                <div>
                    <p class="font-bold uppercase">ĐẠI DIỆN BÊN A</p>
                    <div class="h-20"></div>
                    <p class="font-bold">
                        {{ $customer?->contact_person ?: ($customer?->name ?: '........................................................') }}
                    </p>
                </div>
                <div>
                    <p class="font-bold uppercase">ĐẠI DIỆN BÊN B</p>
                    <div class="h-20"></div>
                    <p class="font-bold">{{ $salesUser?->name ?: 'Nguyễn Văn Quản Trị' }}</p>
                </div>
            </div>
        </div>

        <!-- ================= TAB 3: ACCEPTANCE PROTOCOL ================= -->
        <div x-show="activeTab === 'handover'" data-tab-content="handover" x-cloak class="space-y-3.5 text-black">
            <!-- Header -->
            <div class="text-center space-y-0.5">
                <p class="font-bold uppercase tracking-wide">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
                <p class="font-bold">Độc lập – Tự do – Hạnh phúc</p>
                <div class="w-36 h-[1px] bg-black mx-auto my-1"></div>
                <p class="font-bold uppercase pt-1">BIÊN BẢN NGHIỆM THU & BÀN GIAO</p>
                <p>Hợp đồng số: <span class="font-bold">{{ $code }}</span></p>
            </div>

            <p class="pt-1">
                Hôm nay, ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}, hai
                bên tiến hành nghiệm thu công việc với các nội dung sau:
            </p>

            <!-- Table -->
            <div class="pt-1">
                <table class="w-full border-collapse border border-black text-[13px]">
                    <thead>
                        <tr class="font-bold text-center">
                            <th class="border border-black p-1.5 w-10">STT</th>
                            <th class="border border-black p-1.5 text-left">HẠNG MỤC</th>
                            <th class="border border-black p-1.5 w-14">ĐVT</th>
                            <th class="border border-black p-1.5 w-14">SỐ LƯỢNG</th>
                            <th class="border border-black p-1.5 text-right w-24">ĐƠN GIÁ</th>
                            <th class="border border-black p-1.5 text-right w-28">THÀNH TIỀN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($items) > 0)
                            @foreach($items as $idx => $it)
                                <tr>
                                    <td class="border border-black p-1.5 text-center">{{ $idx + 1 }}</td>
                                    <td class="border border-black p-1.5 font-bold">{{ $it['name'] }}</td>
                                    <td class="border border-black p-1.5 text-center">{{ $it['unit'] }}</td>
                                    <td class="border border-black p-1.5 text-center font-bold">{{ $it['quantity'] }}</td>
                                    <td class="border border-black p-1.5 text-right">
                                        {{ number_format($it['unit_price'], 0, ',', '.') }}</td>
                                    <td class="border border-black p-1.5 text-right font-bold">
                                        {{ number_format($it['line_total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="border border-black p-1.5 text-center">1</td>
                                <td class="border border-black p-1.5">Hệ thống màn hình LED & thiết bị sự kiện theo hợp đồng
                                </td>
                                <td class="border border-black p-1.5 text-center">gói</td>
                                <td class="border border-black p-1.5 text-center font-bold">1</td>
                                <td class="border border-black p-1.5 text-right">
                                    {{ number_format($displayTotal, 0, ',', '.') }}</td>
                                <td class="border border-black p-1.5 text-right font-bold">
                                    {{ number_format($displayTotal, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="5" class="border border-black p-1.5 text-right font-bold uppercase">TỔNG GIÁ TRỊ NGHIỆM THU</td>
                            <td class="border border-black p-1.5 text-right font-bold">
                                {{ number_format($displayTotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="border border-black p-1.5 text-right font-bold">ĐÃ TẠM ỨNG</td>
                            <td class="border border-black p-1.5 text-right font-bold">
                                {{ number_format($depositAmount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="border border-black p-1.5 text-right font-bold">CÒN PHẢI THANH TOÁN
                            </td>
                            <td class="border border-black p-1.5 text-right font-bold">
                                {{ number_format(max(0, $displayTotal - $depositAmount), 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Signatures -->
            <div class="pt-8 grid grid-cols-2 text-center">
                <div>
                    <p class="font-bold uppercase">ĐẠI DIỆN BÊN A</p>
                    <div class="h-20"></div>
                    <p class="font-bold">
                        {{ $customer?->contact_person ?: ($customer?->name ?: '........................................................') }}
                    </p>
                </div>
                <div>
                    <p class="font-bold uppercase">ĐẠI DIỆN BÊN B</p>
                    <div class="h-20"></div>
                    <p class="font-bold">{{ $salesUser?->name ?: 'Nguyễn Văn Quản Trị' }}</p>
                </div>
            </div>
        </div>

    </div>
</div>
