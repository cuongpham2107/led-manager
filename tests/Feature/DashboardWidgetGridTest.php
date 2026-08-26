<?php

use App\Filament\Pages\Dashboard;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('dashboard page with customizable widget grid renders successfully', function () {
    (new LedOsDataSeeder)->run();

    $admin = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($admin);

    Livewire::test(Dashboard::class)
        ->assertSuccessful()
        ->assertSee('110.970.000');
});
