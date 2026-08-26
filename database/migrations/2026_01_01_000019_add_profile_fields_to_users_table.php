<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('warehouse_id')->nullable()->after('phone')
                ->constrained('warehouses')->nullOnDelete(); // primary warehouse for warehouse staff
            $table->boolean('is_active')->default(true)->after('warehouse_id');
        });

        // Roles & permissions: use spatie/laravel-permission (works natively with Filament Shield)
        // composer require spatie/laravel-permission
        // php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
        // That package's own migration creates: roles, permissions, model_has_roles,
        // model_has_permissions, role_has_permissions — giving the "permission matrix
        // per role and per module" out of the box, wired into Filament via filament-shield.
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['phone', 'warehouse_id', 'is_active']);
        });
    }
};
