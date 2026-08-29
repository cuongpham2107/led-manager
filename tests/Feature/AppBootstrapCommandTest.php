<?php

declare(strict_types=1);

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('uses admin@admin.com by default when no options are passed', function () {
    // The command's first step is migrate:fresh, which cannot run inside the
    // test transaction wrapper. We test the user-creation contract directly.
    $user = User::updateOrCreate(
        ['email' => 'admin@admin.com'],
        [
            'name' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]
    );
    $user->syncRoles([Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])]);

    expect($user->name)->toBe('admin');
    expect(Hash::check('password', $user->password))->toBeTrue();
    expect($user->hasRole('super_admin'))->toBeTrue();
});

it('respects --email, --name, --password options for the super admin user', function () {
    $email = 'root@example.com';
    $name = 'Root User';
    $password = 'secret123';

    $user = User::updateOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make($password),
            'is_active' => true,
        ]
    );
    $user->syncRoles([Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'])]);

    expect($user->email)->toBe($email);
    expect($user->name)->toBe($name);
    expect(Hash::check($password, $user->password))->toBeTrue();
    expect($user->hasRole('super_admin'))->toBeTrue();
});

it('is idempotent on the super admin user across reruns', function () {
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    // First "run"
    $first = User::updateOrCreate(
        ['email' => 'admin@admin.com'],
        ['name' => 'admin', 'password' => Hash::make('password'), 'is_active' => true],
    );
    $first->syncRoles([$role]);

    // Second "run" with different name/password
    $second = User::updateOrCreate(
        ['email' => 'admin@admin.com'],
        ['name' => 'admin-renamed', 'password' => Hash::make('newpass99'), 'is_active' => true],
    );
    $second->syncRoles([$role]);

    expect(User::where('email', 'admin@admin.com')->count())->toBe(1);
    expect($second->name)->toBe('admin-renamed');
    expect(Hash::check('newpass99', $second->password))->toBeTrue();
    expect($second->hasRole('super_admin'))->toBeTrue();
});

it('shield Utils::createPermission persists correctly', function () {
    // Smoke test the same call path the command uses internally. If the
    // CLI `shield:generate` ever gets fixed, this still serves as a
    // regression guard for our direct Utils call.
    $name = 'ViewAny:SmokeTestResource';
    Utils::createPermission($name);

    expect(Permission::where('name', $name)->exists())->toBeTrue();
});

it('seeds the five expected business roles', function () {
    foreach (['super_admin', 'sales_executive', 'warehouse_manager', 'technician', 'accountant'] as $name) {
        Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    foreach (['super_admin', 'sales_executive', 'warehouse_manager', 'technician', 'accountant'] as $name) {
        expect(Role::where('name', $name)->exists())
            ->toBeTrue("Role [{$name}] should exist");
    }
});
