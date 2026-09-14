<?php

namespace App\Filament\Pages;

use App\Models\Agency;
use App\Models\Order;
use App\Models\User;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AgencyRevenueReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Doanh thu Đại lý';

    protected static ?string $title = 'Báo Cáo Doanh Thu & Hoa Hồng Đại Lý Tỉnh';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.agency-revenue-report';

    /**
     * @return array{
     *     total_revenue: float,
     *     total_collected: float,
     *     total_commission: float,
     *     total_orders: int,
     *     total_inventory_area: float,
     *     total_allocated_area: float
     * }
     */
    public function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $agencyId = $user?->getScopedAgencyId();

        $query = Order::query()->with('agency')->whereNotNull('agency_id');
        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        $orders = $query->get();
        $totalRevenue = (float) $orders->sum('value');
        $totalCollected = (float) $orders->sum('total_paid');

        // Calculate total commission based on collected money (total_paid)
        $totalCommission = (float) $orders->sum(function (Order $order) {
            $rate = $order->agency ? (float) $order->agency->commission_rate : 0.0;

            return ((float) $order->total_paid * $rate) / 100.0;
        });

        $agenciesQuery = Agency::query()->where('is_active', true);
        if ($agencyId) {
            $agenciesQuery->where('id', $agencyId);
        }
        $agencies = $agenciesQuery->get();

        $totalAllocatedArea = (float) $agencies->sum('allocated_area_m2');
        $totalInventoryArea = (float) $agencies->sum('current_inventory_area');

        return [
            'total_revenue' => $totalRevenue,
            'total_collected' => $totalCollected,
            'total_commission' => $totalCommission,
            'total_orders' => $orders->count(),
            'total_inventory_area' => $totalInventoryArea,
            'total_allocated_area' => $totalAllocatedArea,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAgenciesOverview(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $agencyId = $user?->getScopedAgencyId();

        $query = Agency::query()->where('is_active', true)->with(['warehouse', 'orders']);
        if ($agencyId) {
            $query->where('id', $agencyId);
        }

        return $query->get()->map(function (Agency $agency) {
            $orders = $agency->orders;
            $revenue = (float) $orders->sum('value');
            $collected = (float) $orders->sum('total_paid');
            $rate = (float) $agency->commission_rate;
            $commission = ($collected * $rate) / 100.0;
            $invArea = (float) $agency->current_inventory_area;
            $allocArea = (float) $agency->allocated_area_m2;
            $utilization = $allocArea > 0 ? round(($invArea / $allocArea) * 100, 1) : 0;

            return [
                'id' => $agency->id,
                'name' => $agency->name,
                'code' => $agency->code,
                'province' => $agency->province ?? '—',
                'orders_count' => $orders->count(),
                'revenue' => $revenue,
                'collected' => $collected,
                'commission_rate' => $rate,
                'commission' => $commission,
                'inventory_area' => $invArea,
                'allocated_area' => $allocArea,
                'utilization_percent' => $utilization,
            ];
        })->toArray();
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();
        $agencyId = $user?->getScopedAgencyId();

        $query = Order::query()
            ->with(['agency', 'customer', 'salesUser'])
            ->whereNotNull('agency_id');

        if ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('order_no')
                    ->label('Mã đơn hàng')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('agency.name')
                    ->label('Đại lý')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->hidden(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return (bool) $user?->isAgencyScoped();
                    }),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->description(fn (Order $record): ?string => $record->event)
                    ->searchable(),
                TextColumn::make('request_date')
                    ->label('Ngày bắt đầu')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Doanh thu (VND)')
                    ->money('VND')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('total_paid')
                    ->label('Thực thu (VND)')
                    ->money('VND')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
                TextColumn::make('commission_rate')
                    ->label('% Hoa hồng')
                    ->suffix('%')
                    ->badge()
                    ->color('warning')
                    ->state(fn (Order $record): float => (float) ($record->agency?->commission_rate ?? 0)),
                TextColumn::make('commission_amount')
                    ->label('Hoa hồng thực hưởng')
                    ->money('VND')
                    ->weight('bold')
                    ->color('primary')
                    ->state(function (Order $record): float {
                        $rate = (float) ($record->agency?->commission_rate ?? 0);

                        return ((float) $record->total_paid * $rate) / 100.0;
                    }),
                TextColumn::make('area_m2')
                    ->label('Diện tích LED')
                    ->suffix(' m²')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('agency_id')
                    ->label('Đại lý')
                    ->relationship('agency', 'name')
                    ->hidden(function (): bool {
                        /** @var User|null $user */
                        $user = Auth::user();

                        return (bool) $user?->isAgencyScoped();
                    }),
                Filter::make('request_date')
                    ->form([
                        DatePicker::make('from')->label('Từ ngày'),
                        DatePicker::make('until')->label('Đến ngày'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('request_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('request_date', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->headerActions([
                Action::make('export_csv')
                    ->label('Xuất CSV đối soát')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => $this->exportCsv()),
            ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="agency_revenue_commission_report_'.date('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Mã đơn hàng',
                'Đại lý',
                'Khách hàng',
                'Sự kiện',
                'Ngày bắt đầu',
                'Doanh thu đơn (VND)',
                'Thực thu (VND)',
                'Tỷ lệ hoa hồng (%)',
                'Hoa hồng thực hưởng (VND)',
                'Diện tích màn hình (m2)',
                'Trạng thái',
            ]);

            /** @var User|null $user */
            $user = Auth::user();
            $agencyId = $user?->getScopedAgencyId();

            $query = Order::query()->with(['agency', 'customer'])->whereNotNull('agency_id');
            if ($agencyId) {
                $query->where('agency_id', $agencyId);
            }

            foreach ($query->get() as $order) {
                $rate = (float) ($order->agency?->commission_rate ?? 0);
                $commission = ((float) $order->total_paid * $rate) / 100.0;

                fputcsv($handle, [
                    $order->order_no,
                    $order->agency?->name ?? '—',
                    $order->customer?->name ?? '—',
                    $order->event ?? '—',
                    $order->request_date?->format('d/m/Y') ?? '—',
                    $order->value,
                    $order->total_paid,
                    $rate.'%',
                    $commission,
                    $order->area_m2 ?? 0,
                    $order->status->getLabel(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
