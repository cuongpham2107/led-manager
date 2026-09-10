<?php

namespace Database\Seeders;

use App\Enums\CustomerType;
use App\Enums\ProductEnvironment;
use App\Models\PricingRule;
use App\Models\ProductLine;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserAndRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 0. Auto-generate all Shield permissions
        $this->generatePermissions();

        // 1. Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $salesRole = Role::firstOrCreate(['name' => 'sales_executive', 'guard_name' => 'web']);
        $whRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $techRole = Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'web']);
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        // Sync Shield Permissions if permissions exist
        if (Permission::count() > 0) {
            $superAdminRole->syncPermissions(Permission::all());
            $adminRole->syncPermissions(Permission::all());

            $salesPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Quotation%')
                    ->orWhere('name', 'like', '%:Customer%')
                    ->orWhere('name', 'like', '%:Contract%')
                    ->orWhere('name', 'like', '%:Order%')
                    ->orWhere('name', 'like', 'View%:Asset%')
                    ->orWhere('name', 'like', 'View%:Warehouse%')
                    ->orWhere('name', 'like', 'View%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:PricingRule%')
                    ->orWhere('name', 'like', 'View%:Payment%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
                    ->orWhere('name', 'like', 'View%:RevenueReport%')
                    ->orWhere('name', 'like', 'View%:InventoryReport%')
                    ->orWhere('name', 'like', 'View%:MovementHistoryReport%')
                    ->orWhere('name', 'like', 'View%:AssetUtilizationReport%')
                    ->orWhere('name', 'like', 'View%:StatsOverview%')
                    ->orWhere('name', 'like', 'View%:LatestOrders%')
                    ->orWhere('name', 'like', 'View%:MonthlyRevenueChart%');
            })->whereNotIn('name', [
                'ForceDelete:Quotation', 'ForceDeleteAny:Quotation',
                'ForceDelete:Contract', 'ForceDeleteAny:Contract',
                'ForceDelete:Order', 'ForceDeleteAny:Order',
                'ForceDelete:Customer', 'ForceDeleteAny:Customer',
            ])->get();
            $salesRole->syncPermissions($salesPermissions);

            $whPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Asset%')
                    ->orWhere('name', 'like', '%:CheckinBatch%')
                    ->orWhere('name', 'like', '%:CheckoutBatch%')
                    ->orWhere('name', 'like', '%:ReturnBatch%')
                    ->orWhere('name', 'like', '%:RepairLog%')
                    ->orWhere('name', 'like', '%:Warehouse%')
                    ->orWhere('name', 'like', '%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Customer%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:WarehouseStatusChart%')
                    ->orWhere('name', 'like', 'View%:InventoryReport%')
                    ->orWhere('name', 'like', 'View%:MovementHistoryReport%')
                    ->orWhere('name', 'like', 'View%:AssetUtilizationReport%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%');
            })->get();
            $whRole->syncPermissions($whPermissions);

            $techPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:RepairLog%')
                    ->orWhere('name', 'like', 'View%:ReturnBatch%')
                    ->orWhere('name', 'like', 'Update:ReturnBatch%')
                    ->orWhere('name', 'like', 'View%:CheckoutBatch%')
                    ->orWhere('name', 'like', 'View%:Asset%')
                    ->orWhere('name', 'like', 'View%:Warehouse%')
                    ->orWhere('name', 'like', 'View%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
                    ->orWhere('name', 'like', 'View%:InventoryReport%')
                    ->orWhere('name', 'like', 'View%:MovementHistoryReport%')
                    ->orWhere('name', 'like', 'View%:AssetUtilizationReport%');
            })->get();
            $techRole->syncPermissions($techPermissions);

            $accountantPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Contract%')
                    ->orWhere('name', 'like', '%:Payment%')
                    ->orWhere('name', 'like', '%:Customer%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Quotation%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:RevenueReport%')
                    ->orWhere('name', 'like', 'View%:InventoryReport%')
                    ->orWhere('name', 'like', 'View%:MovementHistoryReport%')
                    ->orWhere('name', 'like', 'View%:AssetUtilizationReport%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
                    ->orWhere('name', 'like', 'View%:StatsOverview%')
                    ->orWhere('name', 'like', 'View%:MonthlyRevenueChart%');
            })->get();
            $accountantRole->syncPermissions($accountantPermissions);
        }

        // 2. Users & Personnel
        $admin = User::updateOrCreate(['email' => 'admin@ledmanager.com'], [
            'name' => 'Đỗ Quang Huy',
            'password' => Hash::make('password'),
            'phone' => '0912 345 678',
            'is_active' => true,
        ]);
        $admin->syncRoles([$superAdminRole]);

        $sales1 = User::updateOrCreate(['email' => 'sales1@ledmanager.com'], [
            'name' => 'Trần Minh Tuấn',
            'password' => Hash::make('password'),
            'phone' => '0988 112 233',
            'is_active' => true,
        ]);
        $sales1->syncRoles([$salesRole]);

        $whStaff1 = User::updateOrCreate(['email' => 'khohn@ledmanager.com'], [
            'name' => 'Lê Hoàng Nam',
            'password' => Hash::make('password'),
            'phone' => '0903 667 788',
            'is_active' => true,
        ]);
        $whStaff1->syncRoles([$whRole]);

        $techUser1 = User::updateOrCreate(['email' => 'tech@ledmanager.com'], [
            'name' => 'Vũ Đình Trọng',
            'password' => Hash::make('password'),
            'phone' => '0918 990 011',
            'is_active' => true,
        ]);
        $techUser1->syncRoles([$techRole]);

        $accountantUser = User::updateOrCreate(['email' => 'ketoan@ledmanager.com'], [
            'name' => 'Nguyễn Thị Mai',
            'password' => Hash::make('password'),
            'phone' => '0933 445 566',
            'is_active' => true,
        ]);
        $accountantUser->syncRoles([$accountantRole]);

        // Link accounts to admin for quick switching without password
        $admin->linkAccount($sales1, label: 'Sales Executive (Trần Minh Tuấn)', requiresPassword: false);
        $admin->linkAccount($whStaff1, label: 'Quản lý kho (Lê Hoàng Nam)', requiresPassword: false);
        $admin->linkAccount($techUser1, label: 'Kỹ thuật viên (Vũ Đình Trọng)', requiresPassword: false);
        $admin->linkAccount($accountantUser, label: 'Kế toán (Nguyễn Thị Mai)', requiresPassword: false);

        // 3. Product Lines (P1.5, P2.6, P2.9, P3.9, P4.8)
        $plP15 = ProductLine::updateOrCreate(['code' => 'P1.5'], [
            'name' => 'P1.5 Trong nhà cố định',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 1.50,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 6.50,
            'power_watt' => 450.00,
            'brand' => 'Gloshine',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP26 = ProductLine::updateOrCreate(['code' => 'P2.6'], [
            'name' => 'P2.6 Sự kiện',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 2.60,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 6.80,
            'power_watt' => 380.00,
            'brand' => 'Gloshine',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP26Fix = ProductLine::updateOrCreate(['code' => 'P2.6-FIX'], [
            'name' => 'P2.6 Trong nhà cố định',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 2.60,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 500.00,
            'weight_kg' => 6.80,
            'power_watt' => 380.00,
            'brand' => 'Gloshine',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP29 = ProductLine::updateOrCreate(['code' => 'P2.9'], [
            'name' => 'P2.9 Sự kiện',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 2.90,
            'environment' => ProductEnvironment::Indoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 13.00,
            'power_watt' => 750.00,
            'brand' => 'Unilumin',
            'cabinet_material' => 'Die-cast Aluminum',
            'is_active' => true,
        ]);

        $plP39 = ProductLine::updateOrCreate(['code' => 'P3.9'], [
            'name' => 'P3.9 Outdoor',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 3.91,
            'environment' => ProductEnvironment::Outdoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 13.50,
            'power_watt' => 850.00,
            'brand' => 'Absen',
            'cabinet_material' => 'Die-cast Aluminum IP65',
            'is_active' => true,
        ]);

        $plP48 = ProductLine::updateOrCreate(['code' => 'P4.8'], [
            'name' => 'P4.8 Outdoor',
            'pixel_pitch_unit' => 'mm',
            'pixel_pitch' => 4.81,
            'environment' => ProductEnvironment::Outdoor,
            'module_width_mm' => 500.00,
            'module_height_mm' => 1000.00,
            'weight_kg' => 14.00,
            'power_watt' => 900.00,
            'brand' => 'Dicolor',
            'cabinet_material' => 'Die-cast Aluminum IP65',
            'is_active' => true,
        ]);

        // 4. Pricing Rules
        $pricingRulesData = [
            // P2.6 standard tiers
            [
                'product_line_id' => $plP26->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 400000,
                'min_days' => 1,
                'max_days' => 1,
                'discount_percent' => 0,
            ],
            [
                'product_line_id' => $plP26->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 350000,
                'min_days' => 2,
                'max_days' => 3,
                'discount_percent' => 12.5,
            ],
            [
                'product_line_id' => $plP26->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 300000,
                'min_days' => 4,
                'max_days' => null,
                'discount_percent' => 25,
            ],
            // P2.6 Agency discount
            [
                'product_line_id' => $plP26->id,
                'customer_type' => CustomerType::Agency->value,
                'base_price_per_unit_per_day' => 350000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 10,
            ],
            // P1.5 High-end standard
            [
                'product_line_id' => $plP15->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 700000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
            // P2.9 Indoor
            [
                'product_line_id' => $plP29->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 450000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
            // P3.9 Outdoor
            [
                'product_line_id' => $plP39->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 380000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
            // P4.8 Outdoor
            [
                'product_line_id' => $plP48->id,
                'customer_type' => null,
                'base_price_per_unit_per_day' => 320000,
                'min_days' => 1,
                'max_days' => null,
                'discount_percent' => 0,
            ],
        ];

        foreach ($pricingRulesData as $pr) {
            PricingRule::updateOrCreate([
                'product_line_id' => $pr['product_line_id'],
                'customer_type' => $pr['customer_type'],
                'min_days' => $pr['min_days'],
            ], [
                'base_price_per_unit_per_day' => $pr['base_price_per_unit_per_day'],
                'max_days' => $pr['max_days'],
                'discount_percent' => $pr['discount_percent'],
                'crew_rate_per_person_per_day' => 1600000,
                'transport_rate_per_km' => 28000,
                'accessory_rate_per_m2' => 50000,
                'is_active' => true,
            ]);
        }
    }

    protected function generatePermissions(): void
    {
        try {
            // Resources
            foreach (FilamentShield::getResources() as $resource) {
                foreach ((array) ($resource['permissions'] ?? []) as $perm) {
                    if (! empty($perm['key'])) {
                        Utils::createPermission($perm['key']);
                    }
                }
            }

            // Pages
            foreach (FilamentShield::getPages() as $page) {
                foreach (array_keys((array) ($page['permissions'] ?? [])) as $name) {
                    Utils::createPermission($name);
                }
            }

            // Widgets
            foreach (FilamentShield::getWidgets() as $widget) {
                foreach (array_keys((array) ($widget['permissions'] ?? [])) as $name) {
                    Utils::createPermission($name);
                }
            }

            // Custom permissions
            $custom = (array) FilamentShield::getCustomPermissions();
            foreach (array_keys($custom) as $name) {
                Utils::createPermission($name);
            }
        } catch (\Throwable) {
            $customPerms = config('filament-shield.custom_permissions', []);
            foreach (array_keys($customPerms) as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }
        }
    }
}
