<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>BÁO GIÁ DỰ TOÁN - {{ $quotation->code }}</title>
    <style>
        @page {
            margin: 7mm 11mm 7mm 11mm;
            size: A4 portrait;
        }
        * {
            font-family: 'DejaVu Sans', sans-serif !important;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif !important;
            font-size: 8pt;
            color: #111;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td, p, div, span, strong, b, em {
            font-family: 'DejaVu Sans', sans-serif !important;
        }
        .header-table {
            width: 100%;
            margin-bottom: 5px;
            border-bottom: 1.5px solid #111;
            padding-bottom: 4px;
        }
        .company-name {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0b328f;
        }
        .company-meta {
            font-size: 7.5pt;
            color: #333;
            line-height: 1.25;
        }
        .national-title {
            text-align: center;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .national-sub {
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
        }
        .national-line {
            width: 90px;
            height: 1px;
            background-color: #111;
            margin: 2px auto;
        }
        .doc-no {
            text-align: center;
            font-size: 7.5pt;
            color: #222;
            margin-top: 2px;
        }
        .doc-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4px;
            margin-bottom: 2px;
            letter-spacing: 0.2px;
            color: #0b328f;
        }
        .doc-sub {
            text-align: center;
            font-size: 8pt;
            font-style: italic;
            margin-bottom: 5px;
        }
        .section-header {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 4px;
            margin-bottom: 2px;
            color: #0b328f;
        }
        .border-table {
            width: 100%;
            border: 1px solid #333;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .border-table th {
            border: 1px solid #333;
            padding: 2.5px 4px;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            background-color: #f1f5f9;
            text-transform: uppercase;
        }
        .border-table td {
            border: 1px solid #333;
            padding: 2px 4px;
            font-size: 7.5pt;
            vertical-align: middle;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .currency-words {
            font-size: 7.5pt;
            margin-top: 2px;
            margin-bottom: 4px;
            padding: 2px 4px;
            background-color: #f8fafc;
            border-left: 2px solid #0b328f;
        }
        .terms-block {
            font-size: 7.5pt;
            line-height: 1.25;
            margin-bottom: 4px;
        }
        .terms-block p {
            margin: 1px 0;
        }
        .sign-table {
            width: 100%;
            margin-top: 4px;
            page-break-inside: avoid;
        }
        .sign-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 8pt;
        }
        .sign-space {
            height: 38px;
        }
    </style>
</head>
<body>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="company-name">CÔNG TY TNHH GIẢI PHÁP MÀN HÌNH LED OS VIỆT NAM</div>
                <div class="company-meta">Hệ thống Quản Trị Vận Hành Cho Thuê Màn Hình LED Chuyên Nghiệp</div>
                <div class="company-meta">Hotline: 0912 345 678 &nbsp;|&nbsp; Email: sales@ledmanager.vn &nbsp;|&nbsp; MST: 0109876543</div>
            </td>
            <td style="width: 42%; vertical-align: top; text-align: center;">
                <div class="national-title">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="national-sub">Độc lập – Tự do – Hạnh phúc</div>
                <div class="national-line"></div>
                <div class="doc-no">Số: <strong>{{ $quotation->code }}</strong> &nbsp;|&nbsp; Ngày: {{ $quotation->created_at ? $quotation->created_at->format('d/m/Y') : now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <!-- Title -->
    <div class="doc-title">BẢNG DỰ TOÁN BÁO GIÁ DỊCH VỤ MÀN HÌNH LED</div>
    <div class="doc-sub">Kính gửi: <strong>{{ $quotation->customer->company_name ?: $quotation->customer->name }}</strong></div>

    <!-- 1. Customer and Project Info -->
    <div class="section-header">I. THÔNG TIN KHÁCH HÀNG & DỰ ÁN (BÊN A)</div>
    <table class="border-table">
        <tr>
            <td style="width: 17%; font-weight: bold; background-color: #f8fafc;">Khách hàng:</td>
            <td style="width: 43%; font-weight: bold;">{{ $quotation->customer->company_name ?: $quotation->customer->name }}</td>
            <td style="width: 17%; font-weight: bold; background-color: #f8fafc;">Tên sự kiện:</td>
            <td style="width: 23%; font-weight: bold;">{{ $quotation->event_name ?: 'Sự kiện biểu diễn / Hội nghị' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #f8fafc;">Người liên hệ:</td>
            <td>{{ $quotation->customer->contact_person ?: $quotation->customer->name }}</td>
            <td style="font-weight: bold; background-color: #f8fafc;">Thời gian thuê:</td>
            <td><strong>{{ $quotation->rental_days ?: 1 }} ngày</strong></td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #f8fafc;">Điện thoại / Email:</td>
            <td>{{ $quotation->customer->phone ?: '—' }} &nbsp;/&nbsp; {{ $quotation->customer->email ?: '—' }}</td>
            <td style="font-weight: bold; background-color: #f8fafc;">Địa điểm lắp đặt:</td>
            <td>{{ $quotation->location ?: 'Theo địa điểm thỏa thuận của Bên A' }}</td>
        </tr>
    </table>

    <!-- 2. Screen Specifications -->
    <div class="section-header">II. THÔNG SỐ KỸ THUẬT HỆ THỐNG MÀN HÌNH LED</div>
    <table class="border-table">
        <tr style="background-color: #f8fafc;">
            <td style="width: 18%; font-weight: bold;">Dòng LED:</td>
            <td style="width: 32%;"><strong>{{ $quotation->productLine?->name ?: 'P2.6 Sự kiện' }}</strong> (Pixel Pitch: {{ $quotation->productLine?->pixel_pitch ?: '2.6' }}mm)</td>
            <td style="width: 18%; font-weight: bold;">Kích thước (R × C):</td>
            <td style="width: 32%;"><strong>{{ $quotation->screen_width_m }}m × {{ $quotation->screen_height_m }}m</strong></td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Tổng diện tích:</td>
            <td><strong>{{ number_format((float) $quotation->screen_area_m2, 2, ',', '.') }} m²</strong></td>
            <td style="font-weight: bold;">Số lượng Cabinet:</td>
            <td><strong>{{ $quotation->estimated_cabinet_qty }} tấm</strong> (chuẩn 500mm × 500mm)</td>
        </tr>
        <tr style="background-color: #f8fafc;">
            <td style="font-weight: bold;">Tổng tải trọng:</td>
            <td>~{{ number_format((float) $quotation->estimated_load_kg, 0, ',', '.') }} kg</td>
            <td style="font-weight: bold;">Công suất điện tiêu thụ:</td>
            <td>~{{ number_format((float) $quotation->estimated_power_kw, 1, ',', '.') }} kW</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Bộ xử lý hình ảnh:</td>
            <td>{{ $quotation->estimated_processor_qty ?: 2 }} bộ Video Processor 4K</td>
            <td style="font-weight: bold;">Hệ thống khung giàn:</td>
            <td>Trọn bộ khung giàn truss chịu lực, cáp nguồn tín hiệu</td>
        </tr>
    </table>

    <!-- 3. BOM & Cost Details Table -->
    <div class="section-header">III. DỰ TOÁN KINH PHÍ CHI TIẾT</div>
    <table class="border-table">
        <thead>
            <tr>
                <th style="width: 5%;">STT</th>
                <th style="width: 48%; text-align: left;">HẠNG MỤC THIẾT BỊ & DỊCH VỤ</th>
                <th style="width: 9%;">ĐVT</th>
                <th style="width: 8%;">SL</th>
                <th style="width: 14%; text-align: right;">ĐƠN GIÁ (VNĐ)</th>
                <th style="width: 16%; text-align: right;">THÀNH TIỀN (VNĐ)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $stt = 1;
            @endphp

            <!-- Equipment Items -->
            @if($quotation->items->isNotEmpty())
                @foreach($quotation->items as $item)
                    <tr>
                        <td class="text-center">{{ $stt++ }}</td>
                        <td>
                            <strong>{{ $item->productLine?->name ?? 'Màn hình LED' }}</strong>
                            <div style="font-size: 7pt; color: #444;">{{ $item->description ?: 'Cabinet LED độ nét cao, chuẩn sự kiện chuyên nghiệp' }}</div>
                        </td>
                        <td class="text-center">Tấm / Bộ</td>
                        <td class="text-center font-bold">{{ (int) $item->quantity }}</td>
                        <td class="text-right">{{ number_format((float) $item->unit_cost, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td class="text-center">{{ $stt++ }}</td>
                    <td>
                        <strong>Hệ thống Màn hình LED {{ $quotation->productLine?->name ?: 'P2.6 Sự kiện' }}</strong>
                        <div style="font-size: 7pt; color: #444;">Kích thước: {{ $quotation->screen_width_m }}m × {{ $quotation->screen_height_m }}m ({{ $quotation->screen_area_m2 }} m²), thời gian thuê: {{ $quotation->rental_days }} ngày</div>
                    </td>
                    <td class="text-center">Trọn gói</td>
                    <td class="text-center font-bold">1</td>
                    <td class="text-right">{{ number_format((float) $quotation->equipment_cost, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) $quotation->equipment_cost, 0, ',', '.') }}</td>
                </tr>
            @endif

            <!-- Labour / Crew -->
            @if($quotation->labour_cost > 0)
                <tr>
                    <td class="text-center">{{ $stt++ }}</td>
                    <td>
                        <strong>Nhân công kỹ thuật lắp đặt, tháo dỡ & trực vận hành sự kiện</strong>
                        <div style="font-size: 7pt; color: #444;">Đội ngũ kỹ thuật viên chuyên nghiệp ({{ $quotation->crew_size ?: 2 }} nhân sự) phụ trách toàn bộ thời gian diễn ra sự kiện</div>
                    </td>
                    <td class="text-center">Trọn gói</td>
                    <td class="text-center font-bold">1</td>
                    <td class="text-right">{{ number_format((float) $quotation->labour_cost, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) $quotation->labour_cost, 0, ',', '.') }}</td>
                </tr>
            @endif

            <!-- Transport -->
            @if($quotation->transport_cost > 0)
                <tr>
                    <td class="text-center">{{ $stt++ }}</td>
                    <td>
                        <strong>Vận chuyển thiết bị 2 chiều & bốc xếp hiện trường</strong>
                        <div style="font-size: 7pt; color: #444;">Xe tải chuyên dụng vận chuyển an toàn tận nơi sự kiện{{ $quotation->transport_distance_km ? ' (cự ly ~' . $quotation->transport_distance_km . ' km)' : '' }}</div>
                    </td>
                    <td class="text-center">Chuyến</td>
                    <td class="text-center font-bold">1</td>
                    <td class="text-right">{{ number_format((float) $quotation->transport_cost, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) $quotation->transport_cost, 0, ',', '.') }}</td>
                </tr>
            @endif

            <!-- Accessories -->
            @if($quotation->accessory_cost > 0)
                <tr>
                    <td class="text-center">{{ $stt++ }}</td>
                    <td>
                        <strong>Phụ kiện khung giàn truss, cáp nguồn tín hiệu & vật tư phụ trợ</strong>
                        <div style="font-size: 7pt; color: #444;">Cáp truyền hình ảnh HDMI/SDI, cáp nguồn tải cao cấp, phụ kiện gia cố an toàn</div>
                    </td>
                    <td class="text-center">Gói</td>
                    <td class="text-center font-bold">1</td>
                    <td class="text-right">{{ number_format((float) $quotation->accessory_cost, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format((float) $quotation->accessory_cost, 0, ',', '.') }}</td>
                </tr>
            @endif

            <!-- Subtotal & Discount if applicable -->
            @if($quotation->discount_amount > 0)
                <tr>
                    <td colspan="5" class="text-right font-bold">Tổng cộng chi phí trước chiết khấu:</td>
                    <td class="text-right font-bold">{{ number_format((float) ($quotation->equipment_cost + $quotation->labour_cost + $quotation->transport_cost + $quotation->accessory_cost), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right font-bold">Chiết khấu thương mại:</td>
                    <td class="text-right font-bold">-{{ number_format((float) $quotation->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif

            <!-- Final Grand Total -->
            <tr style="background-color: #f1f5f9;">
                <td colspan="5" class="text-right font-bold" style="font-size: 8.5pt; color: #0b328f;">TỔNG CỘNG THANH TOÁN (VNĐ):</td>
                <td class="text-right font-bold" style="font-size: 9pt; color: #0b328f;">{{ number_format((float) $quotation->total_price, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="currency-words">
        Số tiền bằng chữ: <strong>{{ \App\Services\VietnameseCurrencyReader::convert($quotation->total_price) }}</strong>.
    </div>

    <!-- 4. Commercial Terms -->
    <div class="section-header">IV. ĐIỀU KHOẢN THƯƠNG MẠI & THỰC HIỆN</div>
    <div class="terms-block">
        <p>– Đơn giá trên đã bao gồm toàn bộ chi phí thiết bị, phụ kiện, nhân công kỹ thuật trực vận hành và vận chuyển 2 chiều.</p>
        <p>– Đơn giá trên <strong>chưa bao gồm thuế giá trị gia tăng (VAT 10%)</strong>.</p>
        <p>– <strong>Hiệu lực báo giá:</strong> Báo giá có hiệu lực trong vòng 15 ngày kể từ ngày ban hành.</p>
        <p>– <strong>Phương thức thanh toán:</strong> Tạm ứng 50% ngay sau khi ký hợp đồng dịch vụ / đơn đặt hàng; 50% còn lại thanh toán sau khi kết thúc sự kiện và ký biên bản nghiệm thu.</p>
        @if($quotation->note)
            <p>– <strong>Ghi chú bổ sung:</strong> {{ $quotation->note }}</p>
        @endif
    </div>

    <!-- 5. Signatures -->
    <table class="sign-table">
        <tr>
            <td>
                <div style="font-weight: bold; text-transform: uppercase;">ĐẠI DIỆN KHÁCH HÀNG (BÊN A)</div>
                <div style="font-size: 7.5pt; font-style: italic; color: #555;">(Ký và ghi rõ họ tên)</div>
                <div class="sign-space"></div>
                <div style="font-weight: bold;">
                    {{ $quotation->customer->contact_person ?: ($quotation->customer->name ?: '........................................................') }}
                </div>
            </td>
            <td>
                <div style="font-weight: bold; text-transform: uppercase;">ĐẠI DIỆN ĐƠN VỊ CUNG CẤP (BÊN B)</div>
                <div style="font-size: 7.5pt; font-style: italic; color: #555;">(Ký, đóng dấu và ghi rõ họ tên)</div>
                <div class="sign-space"></div>
                <div style="font-weight: bold;">
                    {{ $quotation->salesUser?->name ?: 'Đỗ Quang Huy' }}
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
