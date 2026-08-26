<?php

use App\Enums\ContractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Models\Contract;
use App\Models\Payment;
use Database\Seeders\LedOsDataSeeder;

test('contracts and payments track financial commitments and debt correctly', function () {
    (new LedOsDataSeeder)->run();

    $contract = Contract::where('code', 'HD-2608-01')->first();
    expect($contract)->not->toBeNull()
        ->and($contract->status)->toBe(ContractStatus::Active)
        ->and($contract->total_paid)->toEqual($contract->deposit_amount)
        ->and($contract->remaining_debt)->toEqual($contract->contract_value - $contract->deposit_amount);

    // Make final payment
    $finalPayment = Payment::create([
        'code' => 'PAY-TEST-FINAL',
        'contract_id' => $contract->id,
        'customer_id' => $contract->customer_id,
        'type' => PaymentType::Final,
        'method' => PaymentMethod::BankTransfer,
        'amount' => $contract->remaining_debt,
        'payment_date' => now()->toDateString(),
    ]);

    expect($contract->fresh()->total_paid)->toEqual($contract->contract_value)
        ->and($contract->fresh()->remaining_debt)->toEqual(0.0);
});
