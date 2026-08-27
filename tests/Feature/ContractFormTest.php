<?php

use App\Filament\Resources\Contracts\Pages\CreateContract;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use App\Services\VietnameseCurrencyReader;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('vietnamese currency reader converts numbers to words correctly', function () {
    expect(VietnameseCurrencyReader::convert(1415183000))
        ->toBe('Một tỷ bốn trăm mười lăm triệu một trăm tám mươi ba nghìn đồng chẵn');

    expect(VietnameseCurrencyReader::convert(50000000))
        ->toBe('Năm mươi triệu đồng chẵn');

    expect(VietnameseCurrencyReader::convert(0))
        ->toBe('Không đồng');
});

test('create contract page can render with live document preview', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();

    Livewire::test(CreateContract::class)
        ->assertSuccessful()
        ->fillForm([
            'customer_id' => $customer->id,
            'contract_value' => 100000000,
            'deposit_percent' => 50,
            'deposit_amount' => 50000000,
        ])
        ->assertSee('CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM')
        ->assertSee('HỢP ĐỒNG DỊCH VỤ')
        ->assertSee($customer->name)
        ->assertSee('100.000.000')
        ->assertSee('Một trăm triệu đồng chẵn');
});

test('contract preview renders technician labour and transport rows when quotation is selected', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $quotation = Quotation::first();

    Livewire::test(CreateContract::class)
        ->assertSuccessful()
        ->fillForm([
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
        ])
        ->assertSee('Nhân sự kỹ thuật thi công & Vận hành')
        ->assertSee('Vận chuyển thiết bị & Bốc xếp 2 chiều')
        ->assertSee('PHỤ LỤC HỢP ĐỒNG')
        ->assertSee('BIÊN BẢN NGHIỆM THU');
});
