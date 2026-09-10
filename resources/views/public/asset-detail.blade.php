<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $asset ? 'Thiết bị: ' . $asset->serial_no : 'Tra cứu thiết bị' }} — LED Manager</title>
    <meta name="description" content="{{ $asset ? 'Thông tin chi tiết thiết bị LED ' . $asset->serial_no . ' — ' . ($asset->productLine?->name ?? 'LED Panel') : 'Tra cứu thông tin thiết bị LED bằng mã QR hoặc số Seri.' }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --emerald: #059669;
            --emerald-light: #d1fae5;
            --amber: #d97706;
            --amber-light: #fef3c7;
            --purple: #7c3aed;
            --purple-light: #ede9fe;
            --rose: #e11d48;
            --rose-light: #ffe4e6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --radius: 0.75rem;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 50%, #f0fdf4 100%);
            color: var(--gray-800);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .container { max-width: 640px; margin: 0 auto; padding: 1rem; }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 100%);
            color: #fff;
            padding: 1.5rem 1rem 1.25rem;
            border-radius: 0 0 1.5rem 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .header-logo {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .status-badge .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        .status-ready { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
        .status-ready .dot { background: #6ee7b7; }
        .status-event { background: rgba(96, 165, 250, 0.2); color: #93c5fd; }
        .status-event .dot { background: #93c5fd; }
        .status-repair { background: rgba(251, 146, 60, 0.2); color: #fdba74; }
        .status-repair .dot { background: #fdba74; }
        .status-transit { background: rgba(251, 191, 36, 0.2); color: #fcd34d; }
        .status-transit .dot { background: #fcd34d; }
        .status-missing { background: rgba(248, 113, 113, 0.2); color: #fca5a5; }
        .status-missing .dot { background: #fca5a5; }
        .status-disposed { background: rgba(156, 163, 175, 0.2); color: #d1d5db; }
        .status-disposed .dot { background: #d1d5db; }

        /* Cards */
        .card {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            margin-top: 1rem;
            overflow: hidden;
        }
        .card-header {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--gray-700);
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Metrics Grid */
        .metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.625rem;
            margin-top: 1rem;
        }
        .metric {
            background: #fff;
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
            padding: 0.875rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .metric-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .metric-icon svg { width: 1.25rem; height: 1.25rem; }
        .metric-label { font-size: 0.6875rem; font-weight: 500; color: var(--gray-500); }
        .metric-value { font-size: 1.125rem; font-weight: 800; margin-top: 0.125rem; }
        .metric-unit { font-size: 0.6875rem; font-weight: 600; }

        .metric-primary .metric-icon { background: var(--primary-light); color: var(--primary); }
        .metric-primary .metric-value { color: var(--primary); }
        .metric-emerald .metric-icon { background: var(--emerald-light); color: var(--emerald); }
        .metric-emerald .metric-value { color: var(--emerald); }
        .metric-amber .metric-icon { background: var(--amber-light); color: var(--amber); }
        .metric-amber .metric-value { color: var(--amber); }
        .metric-purple .metric-icon { background: var(--purple-light); color: var(--purple); }
        .metric-purple .metric-value { color: var(--purple); }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 0.25rem;
            padding: 0.25rem;
            background: var(--gray-100);
            border-radius: 0.625rem;
            margin: 0.5rem 1rem;
        }
        .tab-btn {
            flex: 1;
            padding: 0.5rem 0.5rem;
            font-size: 0.6875rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: var(--gray-500);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .tab-btn.active {
            background: #fff;
            color: var(--gray-900);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .tab-content { display: none; padding: 0.75rem 1rem 1rem; }
        .tab-content.active { display: block; }

        /* Info rows */
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 0.8125rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--gray-500); font-weight: 500; flex-shrink: 0; }
        .info-value { color: var(--gray-900); font-weight: 600; text-align: right; word-break: break-word; }

        /* Timeline */
        .timeline { padding: 0; }
        .timeline-item {
            position: relative;
            padding: 0 0 1rem 1.75rem;
            border-left: 2px solid var(--gray-200);
            margin-left: 0.375rem;
        }
        .timeline-item:last-child { border-left-color: transparent; padding-bottom: 0; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 0.25rem;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--primary-light);
        }
        .timeline-title { font-size: 0.8125rem; font-weight: 600; color: var(--gray-800); }
        .timeline-sub { font-size: 0.6875rem; color: var(--gray-500); margin-top: 0.125rem; }
        .timeline-meta { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 0.375rem; }
        .timeline-meta span { font-size: 0.6875rem; color: var(--gray-500); }

        /* Badge inline */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.6875rem;
            font-weight: 600;
        }
        .badge-success { background: var(--emerald-light); color: var(--emerald); }
        .badge-danger { background: var(--rose-light); color: var(--rose); }
        .badge-warning { background: var(--amber-light); color: var(--amber); }
        .badge-info { background: var(--primary-light); color: var(--primary); }
        .badge-gray { background: var(--gray-100); color: var(--gray-500); }

        /* Not Found */
        .not-found {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        .not-found-icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            background: var(--rose-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--rose);
        }
        .not-found h2 { font-size: 1.125rem; font-weight: 700; color: var(--gray-800); }
        .not-found p { font-size: 0.8125rem; color: var(--gray-500); margin-top: 0.5rem; }

        /* Search */
        .search-form {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .search-input {
            flex: 1;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--gray-300);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            outline: none;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
        .search-btn {
            padding: 0.625rem 1rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            font-family: inherit;
        }
        .search-btn:hover { background: #1d4ed8; }

        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem 1rem;
            font-size: 0.6875rem;
            color: var(--gray-400);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--gray-400);
        }
        .empty-state svg { width: 2.5rem; height: 2.5rem; margin: 0 auto 0.5rem; opacity: 0.5; }
        .empty-state p { font-size: 0.8125rem; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">LED Manager</div>
        @if ($asset)
            <h1>{{ $asset->serial_no }}</h1>
            @php
                $statusClass = match ($asset->current_status?->value ?? 'ready') {
                    'ready' => 'status-ready',
                    'in_event' => 'status-event',
                    'in_transit' => 'status-transit',
                    'repairing' => 'status-repair',
                    'missing' => 'status-missing',
                    'disposed' => 'status-disposed',
                    default => 'status-ready',
                };
            @endphp
            <span class="status-badge {{ $statusClass }}">
                <span class="dot"></span>
                {{ $asset->current_status instanceof \App\Enums\AssetStatus ? $asset->current_status->getLabel() : 'Sẵn sàng trong kho' }}
            </span>
        @else
            <h1>Tra cứu thiết bị</h1>
        @endif
    </div>

    <div class="container">
        @if (! $asset)
            {{-- Not Found --}}
            <div class="card">
                <div class="not-found">
                    <div class="not-found-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h2>Không tìm thấy thiết bị</h2>
                    <p>Mã "<strong>{{ e($code) }}</strong>" không khớp với bất kỳ thiết bị nào trong hệ thống.</p>
                    <form class="search-form" method="GET" action="{{ route('asset.public.show', ['code' => '__PLACEHOLDER__']) }}" onsubmit="this.action = this.action.replace('__PLACEHOLDER__', encodeURIComponent(this.querySelector('input').value)); return true;">
                        <input class="search-input" type="text" placeholder="Nhập mã QR hoặc số Seri..." required>
                        <button class="search-btn" type="submit">Tra cứu</button>
                    </form>
                </div>
            </div>
        @else
            {{-- Key Metrics --}}
            <div class="metrics">
                <div class="metric metric-primary">
                    <div class="metric-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="metric-label">Số giờ chạy</div>
                        <div class="metric-value">{{ number_format((int) ($asset->operating_hours ?? 0), 0, ',', '.') }} <span class="metric-unit">h</span></div>
                    </div>
                </div>
                <div class="metric metric-emerald">
                    <div class="metric-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <div class="metric-label">Số lần cho thuê</div>
                        <div class="metric-value">{{ number_format((int) ($asset->rental_count ?? 0), 0, ',', '.') }} <span class="metric-unit">lần</span></div>
                    </div>
                </div>
                <div class="metric metric-amber">
                    <div class="metric-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="metric-label">Tỷ lệ kiểm định đạt</div>
                        <div class="metric-value">{{ $qualityRate !== null ? $qualityRate . '%' : '—' }}</div>
                    </div>
                </div>
                <div class="metric metric-purple">
                    <div class="metric-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <div class="metric-label">Vị trí hiện tại</div>
                        <div class="metric-value" style="font-size: 0.8125rem;">{{ $asset->location_label }}</div>
                    </div>
                </div>
            </div>

            {{-- Tabbed Card --}}
            <div class="card" style="margin-top: 1rem;">
                <div class="tabs" id="tabsContainer">
                    <button type="button" class="tab-btn active" data-tab="specs">Thông số</button>
                    <button type="button" class="tab-btn" data-tab="rental">Cho thuê ({{ $eventsCount }})</button>
                    <button type="button" class="tab-btn" data-tab="repair">Bảo dưỡng ({{ $repairLogs->count() }})</button>
                </div>

                {{-- Tab: Specs --}}
                <div class="tab-content active" id="tab-specs">
                    <div class="info-row">
                        <span class="info-label">Dòng sản phẩm</span>
                        <span class="info-value">{{ $asset->productLine?->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mã dòng</span>
                        <span class="info-value">{{ $asset->productLine?->code ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kích thước</span>
                        <span class="info-value">{{ $asset->size ? str_replace('x', '×', $asset->size) : '—' }}</span>
                    </div>
                    @if ($asset->productLine?->pixel_pitch)
                        <div class="info-row">
                            <span class="info-label">Pixel Pitch</span>
                            <span class="info-value">P{{ number_format((float) $asset->productLine->pixel_pitch, 2) }}mm</span>
                        </div>
                    @endif
                    @if ($asset->productLine?->environment)
                        <div class="info-row">
                            <span class="info-label">Môi trường</span>
                            <span class="info-value">{{ $asset->productLine->environment instanceof \App\Enums\ProductEnvironment ? $asset->productLine->environment->getLabel() : $asset->productLine->environment }}</span>
                        </div>
                    @endif
                    @if ($asset->productLine?->module_width_mm && $asset->productLine?->module_height_mm)
                        <div class="info-row">
                            <span class="info-label">Module (W×H)</span>
                            <span class="info-value">{{ number_format((float) $asset->productLine->module_width_mm, 0) }}×{{ number_format((float) $asset->productLine->module_height_mm, 0) }} mm</span>
                        </div>
                    @endif
                    @if ($asset->productLine?->weight_kg)
                        <div class="info-row">
                            <span class="info-label">Trọng lượng</span>
                            <span class="info-value">{{ number_format((float) $asset->productLine->weight_kg, 1) }} kg</span>
                        </div>
                    @endif
                    @if ($asset->productLine?->power_watt)
                        <div class="info-row">
                            <span class="info-label">Công suất</span>
                            <span class="info-value">{{ number_format((float) $asset->productLine->power_watt, 0) }} W</span>
                        </div>
                    @endif
                    @if ($asset->productLine?->brand)
                        <div class="info-row">
                            <span class="info-label">Thương hiệu</span>
                            <span class="info-value">{{ $asset->productLine->brand }}</span>
                        </div>
                    @endif
                    @if ($asset->productLine?->cabinet_material)
                        <div class="info-row">
                            <span class="info-label">Vật liệu</span>
                            <span class="info-value">{{ $asset->productLine->cabinet_material }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Mã QR</span>
                        <span class="info-value" style="font-family: monospace;">{{ $asset->qr_code ?: $asset->serial_no }}</span>
                    </div>
                    @if ($asset->manufactured_date)
                        <div class="info-row">
                            <span class="info-label">Ngày sản xuất</span>
                            <span class="info-value">{{ $asset->manufactured_date->format('d/m/Y') }}</span>
                        </div>
                    @endif
                </div>

                {{-- Tab: Rental History --}}
                <div class="tab-content" id="tab-rental">
                    @if ($history->isEmpty())
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <p>Chưa có lịch sử cho thuê / sự kiện.</p>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach ($history as $item)
                                @php
                                    $batch = $item->checkoutBatch;
                                    $order = $batch?->order;
                                    $customer = $order?->customer ?? $batch?->customer;
                                    $returnItem = $item->returnBatchItem;
                                    $dispatchedAt = $item->dispatched_at ?? $batch?->dispatched_at ?? $batch?->created_at;
                                    $receivedAt = $returnItem?->received_at;
                                    $isReturned = (bool) ($returnItem?->is_received);
                                @endphp
                                <div class="timeline-item">
                                    <div class="timeline-title">
                                        {{ $order?->event ?: ($order?->order_no ?: ($batch?->code ?? '—')) }}
                                    </div>
                                    <div class="timeline-sub">
                                        @if ($customer?->name)
                                            {{ $customer->name }} •
                                        @endif
                                        {{ $batch?->code ?? '' }}
                                    </div>
                                    <div class="timeline-meta">
                                        <span>Xuất: {{ $dispatchedAt ? $dispatchedAt->format('d/m/Y') : '—' }}</span>
                                        @if ($isReturned && $receivedAt)
                                            <span>Trả: {{ $receivedAt->format('d/m/Y') }}</span>
                                        @endif
                                        @if ($isReturned && $returnItem)
                                            @if ($returnItem->grade?->value === 'normal')
                                                <span class="badge badge-success">Đạt chuẩn</span>
                                            @elseif ($returnItem->grade?->value === 'damaged')
                                                <span class="badge badge-danger">Lỗi / Hỏng</span>
                                            @else
                                                <span class="badge badge-info">Đã nhập kho</span>
                                            @endif
                                        @else
                                            <span class="badge badge-warning">Đang cho thuê</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Tab: Repair History --}}
                <div class="tab-content" id="tab-repair">
                    @if ($repairLogs->isEmpty())
                        <div class="empty-state">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <p>Chưa có lịch sử bảo dưỡng / sửa chữa.</p>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach ($repairLogs as $log)
                                @php
                                    $resultStatus = $log->result_status;
                                    $resultLabel = $resultStatus instanceof \App\Enums\RepairResultStatus ? $resultStatus->getLabel() : (string) ($resultStatus?->value ?? $resultStatus);
                                    $badgeClass = match ($resultStatus?->value ?? (string) $resultStatus) {
                                        'fixed' => 'badge-success',
                                        'disposed' => 'badge-danger',
                                        default => 'badge-warning',
                                    };
                                @endphp
                                <div class="timeline-item">
                                    <div class="timeline-title">
                                        {{ $log->repair_note ?: 'Bảo dưỡng / Sửa chữa' }}
                                    </div>
                                    <div class="timeline-meta">
                                        <span>{{ $log->start_date ? $log->start_date->format('d/m/Y') : '—' }} → {{ $log->end_date ? $log->end_date->format('d/m/Y') : 'Đang xử lý' }}</span>
                                        <span class="badge {{ $badgeClass }}">{{ $resultLabel }}</span>
                                    </div>
                                    @if ($log->creator?->name)
                                        <div class="timeline-sub">Phụ trách: {{ $log->creator->name }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Search Another --}}
            <div class="card">
                <div style="padding: 1rem;">
                    <div style="font-size: 0.8125rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;">Tra cứu thiết bị khác</div>
                    <form class="search-form" method="GET" action="{{ route('asset.public.show', ['code' => '__PLACEHOLDER__']) }}" onsubmit="this.action = this.action.replace('__PLACEHOLDER__', encodeURIComponent(this.querySelector('input').value)); return true;">
                        <input class="search-input" type="text" placeholder="Nhập mã QR hoặc số Seri..." required>
                        <button class="search-btn" type="submit">Tra cứu</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="footer">
            © {{ date('Y') }} LED Manager — Hệ thống quản lý thiết bị LED
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tabBtns = document.querySelectorAll('.tab-btn');
            var tabContents = document.querySelectorAll('.tab-content');
            tabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = btn.getAttribute('data-tab');
                    tabBtns.forEach(function (b) { b.classList.remove('active'); });
                    tabContents.forEach(function (c) { c.classList.remove('active'); });
                    btn.classList.add('active');
                    var el = document.getElementById('tab-' + target);
                    if (el) el.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
