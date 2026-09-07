<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $salesRole = Role::firstOrCreate(['name' => 'sales_executive', 'guard_name' => 'web']);
        $whRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $techRole = Role::firstOrCreate(['name' => 'technician', 'guard_name' => 'web']);
        $accountantRole = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);

        // 3. Sync Permissions to Roles if Shield permissions exist
        if (Permission::count() > 0) {
            // Super Admin gets all permissions
            $superAdminRole->syncPermissions(Permission::all());

            // Sales Executive permissions
            $salesPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Quotation%')
                    ->orWhere('name', 'like', '%:Customer%')
                    ->orWhere('name', 'like', '%:Contract%')
                    ->orWhere('name', 'like', '%:Order%')
                    ->orWhere('name', 'like', 'View%:Asset%')
                    ->orWhere('name', 'like', 'View%:Warehouse%')
                    ->orWhere('name', 'like', 'View%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:DeviceType%')
                    ->orWhere('name', 'like', 'View%:PricingRule%')
                    ->orWhere('name', 'like', 'View%:Payment%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
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

            // Warehouse Manager permissions
            $whPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Asset%')
                    ->orWhere('name', 'like', '%:CheckinBatch%')
                    ->orWhere('name', 'like', '%:CheckoutBatch%')
                    ->orWhere('name', 'like', '%:ReturnBatch%')
                    ->orWhere('name', 'like', '%:RepairLog%')
                    ->orWhere('name', 'like', '%:Warehouse%')
                    ->orWhere('name', 'like', '%:ProductLine%')
                    ->orWhere('name', 'like', '%:DeviceType%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Customer%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:WarehouseStatusChart%')
                    ->orWhere('name', 'like', 'View%:AssetUtilizationReport%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%');
            })->get();
            $whRole->syncPermissions($whPermissions);

            // Technician permissions
            $techPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:RepairLog%')
                    ->orWhere('name', 'like', 'View%:ReturnBatch%')
                    ->orWhere('name', 'like', 'Update:ReturnBatch%')
                    ->orWhere('name', 'like', 'View%:CheckoutBatch%')
                    ->orWhere('name', 'like', 'View%:Asset%')
                    ->orWhere('name', 'like', 'View%:Warehouse%')
                    ->orWhere('name', 'like', 'View%:ProductLine%')
                    ->orWhere('name', 'like', 'View%:DeviceType%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:EventCalendar%')
                    ->orWhere('name', 'like', 'View%:RepairFrequencyReport%');
            })->get();
            $techRole->syncPermissions($techPermissions);

            // Accountant permissions
            $accountantPermissions = Permission::where(function ($q) {
                $q->where('name', 'like', '%:Contract%')
                    ->orWhere('name', 'like', '%:Payment%')
                    ->orWhere('name', 'like', '%:Customer%')
                    ->orWhere('name', 'like', 'View%:Order%')
                    ->orWhere('name', 'like', 'View%:Quotation%')
                    ->orWhere('name', 'like', 'View%:Dashboard%')
                    ->orWhere('name', 'like', 'View%:RevenueReport%')
                    ->orWhere('name', 'like', 'View%:StatsOverview%')
                    ->orWhere('name', 'like', 'View%:MonthlyRevenueChart%');
            })->get();
            $accountantRole->syncPermissions($accountantPermissions);
        }

        // 4. Assign Roles to Users
        $userRoleMappings = [
            'super_admin' => [
                'admin@ledmanager.com',
                'admin@admin.com',
            ],
            'sales_executive' => [
                'sales1@ledmanager.com',
                'sales2@ledmanager.com',
                'sales3@ledmanager.com',
            ],
            'warehouse_manager' => [
                'khohn@ledmanager.com',
                'khohcm@ledmanager.com',
                'khodn@ledmanager.com',
            ],
            'technician' => [
                'tech@ledmanager.com',
                'tech2@ledmanager.com',
                'tech3@ledmanager.com',
            ],
        ];

        foreach ($userRoleMappings as $roleName => $emails) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            foreach ($emails as $email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    $user->syncRoles([$role]);
                }
            }
        }
    }
}
