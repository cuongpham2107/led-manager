<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>BÁO GIÁ DỰ TOÁN MÀN HÌNH LED - {{ $quotation->code }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Arial, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 9pt;
            color: #64748b;
        }
        .doc-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            margin: 15px 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-meta {
            text-align: center;
            font-size: 9.5pt;
            color: #475569;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a8a;
            border-left: 4px solid #2563eb;
            padding-left: 8px;
            margin: 15px 0 10px 0;
            text-transform: uppercase;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 4px 6px;
            font-size: 10pt;
            vertical-align: top;
        }
        .info-label {
            color: #64748b;
            width: 25%;
            font-weight: normal;
        }
        .info-val {
            color: #0f172a;
            font-weight: 500;
            width: 25%;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 15px;
        }
        .table-data th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9.5pt;
            font-weight: bold;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
        }
        .table-data td {
            font-size: 9.5pt;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .summary-box {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .summary-box td {
            padding: 5px 8px;
            font-size: 10pt;
        }
        .summary-total {
            background-color: #eff6ff;
            font-weight: bold;
            font-size: 12pt;
            color: #1e40af;
            border-top: 2px solid #2563eb;
        }
        .footer {
            margin-top: 30px;
            width: 100%;
        }
        .signature-col {
            width: 50%;
            text-align: center;
            font-size: 10pt;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <div class="company-name">LED OS VIỆT NAM</div>
                <div class="company-meta">Hệ Thống Quản Trị Vận Hành Cho Thuê Màn Hình LED Chuyên Nghiệp</div>
                <div class="company-meta">Hotline: 0912 345 678 | Email: sales@ledmanager.com</div>
            </td>
            <td style="width: 35%; text-align: right; vertical-align: middle;">
                <div style="font-size: 12pt; font-weight: bold; color: #2563eb;">MÃ BG: {{ $quotation->code }}</div>
                <div style="font-size: 9pt; color: #64748b;">Ngày tạo: {{ $quotation->created_at->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">BẢNG DỰ TOÁN BÁO GIÁ THUÊ MÀN HÌNH LED</div>
    <div class="doc-meta">Áp dụng cho sự kiện: <strong>{{ $quotation->event_name ?: 'Sự kiện biểu diễn / Hội nghị' }}</strong></div>

    <div class="section-title">1. Thông tin khách hàng & Dự án</div>
    <table class="info-grid">
        <tr>
            <td class="info-label">Khách hàng:</td>
            <td class="info-val" colspan="3"><strong>{{ $quotation->customer->name }}</strong></td>
        </tr>
        <tr>
            <td class="info-label">Điện thoại / Email:</td>
            <td class="info-val">{{ $quotation->customer->phone ?: '—' }} / {{ $quotation->customer->email ?: '—' }}</td>
            <td class="info-label">Địa điểm lắp đặt:</td>
            <td class="info-val">{{ $quotation->location ?: 'Theo yêu cầu của khách hàng' }}</td>
        </tr>
        <tr>
            <td class="info-label">Dòng LED:</td>
            <td class="info-val"><strong>{{ $quotation->productLine?->name ?: 'P2.6 Indoor' }}</strong> (Pixel Pitch: {{ $quotation->productLine?->pixel_pitch ?: '2.6' }}mm)</td>
            <td class="info-label">Thời gian thuê:</td>
            <td class="info-val"><strong>{{ $quotation->rental_days ?: 1 }} ngày</strong></td>
        </tr>
    </table>

    <div class="section-title">2. Thông số kỹ thuật màn hình</div>
    <table class="info-grid" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px;">
        <tr>
            <td class="info-label">Kích thước (R × C):</td>
            <td class="info-val"><strong>{{ $quotation->screen_width_m }}m × {{ $quotation->screen_height_m }}m</strong></td>
            <td class="info-label">Tổng diện tích:</td>
            <td class="info-val"><strong>{{ $quotation->screen_area_m2 }} m²</strong></td>
        </tr>
        <tr>
            <td class="info-label">Số lượng Cabinet:</td>
            <td class="info-val"><strong>{{ $quotation->estimated_cabinet_qty }} tấm</strong></td>
            <td class="info-label">Tổng tải trọng:</td>
            <td class="info-val">~{{ $quotation->estimated_load_kg }} kg</td>
        </tr>
        <tr>
            <td class="info-label">Công suất điện tiêu thụ:</td>
            <td class="info-val">~{{ $quotation->estimated_power_kw }} kW</td>
            <td class="info-label">Video Processor:</td>
            <td class="info-val">{{ $quotation->estimated_processor_qty ?: 2 }} bộ 4K</td>
        </tr>
    </table>

    <div class="section-title">3. Danh mục thiết bị & Vật tư (BOM)</div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 6%;" class="text-center">STT</th>
                <th style="width: 44%;">Tên thiết bị / Quy cách</th>
                <th style="width: 12%;" class="text-center">ĐVT</th>
                <th style="width: 10%;" class="text-center">SL</th>
                <th style="width: 14%;" class="text-right">Đơn giá (đ)</th>
                <th style="width: 14%;" class="text-right">Thành tiền (đ)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->productLine?->name ?? $item->description ?? 'Thiết bị LED' }}</strong>
                        @if($item->productLine && $item->description)
                            <div style="font-size: 8.5pt; color: #64748b;">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">Tấm / Bộ</td>
                    <td class="text-center font-bold">{{ (int) $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_cost, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">{{ number_format($item->line_total, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="color: #94a3b8;">Không có danh mục chi tiết</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">4. Tổng hợp chi phí dự toán</div>
    <table class="summary-box">
        <tr>
            <td class="info-label">Chi phí thiết bị:</td>
            <td class="text-right font-bold">{{ number_format($quotation->equipment_cost, 0, ',', '.') }} đ</td>
        </tr>
        <tr>
            <td class="info-label">Nhân công kỹ thuật:</td>
            <td class="text-right font-bold">{{ number_format($quotation->labour_cost, 0, ',', '.') }} đ</td>
        </tr>
        <tr>
            <td class="info-label">Vận chuyển & Bốc xếp:</td>
            <td class="text-right font-bold">{{ number_format($quotation->transport_cost, 0, ',', '.') }} đ</td>
        </tr>
        <tr>
            <td class="info-label">Phụ kiện & Vật tư:</td>
            <td class="text-right font-bold">{{ number_format($quotation->accessory_cost, 0, ',', '.') }} đ</td>
        </tr>
        @if($quotation->discount_amount > 0)
        <tr>
            <td class="info-label" style="color: #dc2626;">Chiết khấu:</td>
            <td class="text-right font-bold" style="color: #dc2626;">-{{ number_format($quotation->discount_amount, 0, ',', '.') }} đ</td>
        </tr>
        @endif
        <tr class="summary-total">
            <td>TỔNG CỘNG THANH TOÁN:</td>
            <td class="text-right">{{ number_format($quotation->total_price, 0, ',', '.') }} đ</td>
        </tr>
    </table>

    <table class="footer">
        <tr>
            <td class="signature-col">
                <strong>ĐẠI DIỆN KHÁCH HÀNG</strong><br>
                <span style="font-size: 8.5pt; color: #64748b;">(Ký và ghi rõ họ tên)</span>
                <div style="height: 60px;"></div>
            </td>
            <td class="signature-col">
                <strong>ĐẠI DIỆN LED OS VIỆT NAM</strong><br>
                <span style="font-size: 8.5pt; color: #64748b;">(Ký, đóng dấu và ghi rõ họ tên)</span>
                <div style="height: 60px;"></div>
                <strong>{{ $quotation->salesUser?->name ?: 'Bộ Phận Kinh Doanh' }}</strong>
            </td>
        </tr>
    </table>

</body>
</html>
