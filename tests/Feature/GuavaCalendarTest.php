<?php

use App\Filament\Pages\EventCalendarPage;
use App\Filament\Widgets\EventCalendarWidget;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('event calendar page and fullcalendar widget render successfully with event data and actions', function () {
    (new LedOsDataSeeder)->run();

    $admin = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($admin);

    Livewire::test(EventCalendarPage::class)
        ->assertSuccessful();

    Livewire::test(EventCalendarWidget::class)
        ->assertSuccessful();

    $widget = new EventCalendarWidget;
    $events = $widget->fetchEvents([
        'start' => now()->startOfMonth()->toDateString(),
        'end' => now()->endOfMonth()->toDateString(),
    ]);

    expect($events)->toBeArray()
        ->and(count($events))->toBeGreaterThan(0);
});
