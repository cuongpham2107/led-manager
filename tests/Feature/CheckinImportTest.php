<?php

use App\Enums\AssetStatus;
use App\Exports\CheckinBatchTemplate;
use App\Models\Asset;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    config(['excel.temporary_files.local_path' => sys_get_temp_dir()]);
    (new LedOsDataSeeder)->run();
});

test('checkin import endpoint requires authentication', function () {
    $response = $this->postJson(route('filament.checkin-import'), []);
    $response->assertUnauthorized();
});

test('checkin import allows pasting serials to quickly select and match assets', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    actingAs($user);

    $existing = Asset::firstOrFail();
    $newSerialA = 'PASTE-CHECKIN-001';
    $newSerialB = 'PASTE-CHECKIN-002';

    $pasted = "Số serial\n{$existing->serial_no}\n{$newSerialA}\n{$newSerialB}";

    $response = $this->postJson(route('filament.checkin-import'), [
        'serials_text' => $pasted,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $data = $response->json();
    expect($data['assets'])->toHaveCount(3);

    $assetIds = collect($data['assets'])->pluck('id');
    expect($assetIds)->toContain($existing->id);

    $createdA = Asset::where('serial_no', $newSerialA)->first();
    expect($createdA)->not->toBeNull()
        ->and($createdA->current_status)->toBe(AssetStatus::NewlyAdded);
});

test('checkin import accepts the 1-column CheckinBatchTemplate Excel file', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    actingAs($user);

    $filePath = storage_path('framework/testing/test_checkin_batch_template.xlsx');
    if (! file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0755, true);
    }
    Excel::store(new CheckinBatchTemplate, 'test_checkin_batch_template.xlsx', 'local');
    $storedPath = storage_path('app/private/test_checkin_batch_template.xlsx');
    if (! file_exists($storedPath)) {
        $storedPath = storage_path('app/test_checkin_batch_template.xlsx');
    }

    $uploadedFile = new UploadedFile($storedPath, 'test_checkin_batch_template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->postJson(route('filament.checkin-import'), [
        'excel_file' => $uploadedFile,
    ]);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $data = $response->json();
    expect($data['assets'])->toHaveCount(3);
});

test('checkin assets selector view renders quick import file options', function () {
    $selector = file_get_contents(resource_path('views/filament/components/checkin-batch-assets-selector.blade.php'));

    expect($selector)->toContain('triggerImportFile()')
        ->and($selector)->toContain('importFileInput')
        ->and($selector)->toContain('Import Excel tích chọn')
        ->and($selector)->toContain('filament.checkin-batch-template');
});
