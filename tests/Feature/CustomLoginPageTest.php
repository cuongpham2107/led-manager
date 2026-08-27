<?php

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\get;

test('login page can be rendered with custom split layout', function () {
    $response = get('/login');

    $response->assertSuccessful();
    $response->assertSee('Đăng nhập tài khoản');
    $response->assertSee('Nhập thông tin đăng nhập của bạn để tiếp tục');
});

test('users can authenticate via custom login page', function () {
    $user = User::factory()->create([
        'email' => 'admin_test@example.com',
        'password' => bcrypt('password123'),
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin_test@example.com',
            'password' => 'password123',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/');

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});
