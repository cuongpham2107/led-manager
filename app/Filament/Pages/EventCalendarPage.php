<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\EventCalendarWidget;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EventCalendarPage extends Page
{
    use HasPageShield;

    protected static string|UnitEnum|null $navigationGroup = 'Tổng quan';

    protected static ?string $navigationLabel = 'Lịch sự kiện & Thi công';

    protected static ?string $title = 'Lịch Trình Sự Kiện & Thi Công Màn Hình LED';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.event-calendar-page';

    protected function getHeaderWidgets(): array
    {
        return [
            EventCalendarWidget::class,
        ];
    }
}
