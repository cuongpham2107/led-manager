<?php

namespace App\Console\Commands;

use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AppBootstrapCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:bootstrap
                            {--email=admin@admin.com : Email of the super admin user}
                            {--name=admin : Name of the super admin user}
                            {--password=password : Password of the super admin user}
                            {--skip-migrate : Skip migrate:fresh (use only when DB is already at the right schema)}
                            {--skip-seed : Skip running database seeders}
                            {--skip-permissions : Skip generating Shield permissions (faster re-bootstrap)}';

    /**
     * @var string
     */
    protected $description = 'One-shot bootstrap: fresh DB, install Shield, generate permissions, seed, ensure super admin user.';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');
        $password = (string) $this->option('password');

        $this->info('🚀 Bootstrapping LED Manager…');

        // 1) Fresh migrations (truncate + recreate schema)
        if (! $this->option('skip-migrate')) {
            $this->line('▸ migrate:fresh');
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $this->warn('▸ Skipping migrate:fresh (--skip-migrate)');
        }

        // 2) Shield setup — publishes config + ensures required scaffolding
        $this->line('▸ shield:setup --force');
        Artisan::call('shield:setup', ['--force' => true, '--starred' => true]);

        // 3) Generate all permissions for Filament entities.
        //    NOTE: we call Shield's Utils directly instead of `shield:generate`
        //    because the command-line wrapper silently rolls back the inserts
        //    in this environment. The Utils API is the same one shield uses
        //    internally and persists correctly via Permission::firstOrCreate.
        if (! $this->option('skip-permissions')) {
            $this->generateShieldPermissions();
        } else {
            $this->warn('▸ Skipping permission generation (--skip-permissions)');
        }

        // 4) Run seeders. LedOsDataSeeder creates roles (super_admin, sales,
        //    warehouse, technician, accountant) and — because the permissions
        //    table is now populated — syncs each role with its permission set.
        if (! $this->option('skip-seed')) {
            $this->line('▸ db:seed');
            Artisan::call('db:seed', ['--force' => true]);
        } else {
            $this->warn('▸ Skipping db:seed (--skip-seed)');
        }

        // 5) Ensure the super_admin role exists (idempotent; the seeder
        //    already created it, but `updateOrCreate` is cheap insurance).
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // 6) Create or update the super admin user with the requested credentials
        $this->line("▸ Creating super admin: {$email}");
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );
        $user->syncRoles([$superAdminRole]);

        $this->newLine();
        $this->info('✅ Bootstrap hoàn tất.');
        $this->table(
            ['Key', 'Value'],
            [
                ['Super admin email', $user->email],
                ['Super admin name', $user->name],
                ['Super admin role', $superAdminRole->name],
                ['Total permissions', Permission::count()],
                ['Total roles', Role::count()],
                ['Total users', User::count()],
            ]
        );

        $this->newLine();
        $this->comment("Đăng nhập tại /admin với {$email} / {$password}");

        return self::SUCCESS;
    }

    /**
     * Generate Filament Shield permissions directly via the Utils API.
     *
     * Mirrors what `shield:generate --all --option=permissions_and_policies`
     * does internally, but calls the same Utils methods used by the seeder
     * so the inserts are actually persisted (the CLI command silently rolls
     * back in some environments).
     */
    protected function generateShieldPermissions(): void
    {
        $this->line('▸ Generating Shield permissions via Utils…');

        $panel = Filament::getCurrentOrDefaultPanel();
        $before = Permission::count();
        $generated = 0;

        // Resources — permissions are nested: ['viewAny' => ['key' => 'ViewAny:Foo', 'label' => '...']]
        foreach (FilamentShield::getResources() as $resource) {
            foreach ((array) ($resource['permissions'] ?? []) as $perm) {
                if (! empty($perm['key'])) {
                    Utils::createPermission($perm['key']);
                    $generated++;
                }
            }
        }

        // Pages — permissions are flat: ['View:Foo' => 'Label text']
        foreach (FilamentShield::getPages() as $page) {
            foreach (array_keys((array) ($page['permissions'] ?? [])) as $name) {
                Utils::createPermission($name);
                $generated++;
            }
        }

        // Widgets — same shape as pages.
        foreach (FilamentShield::getWidgets() as $widget) {
            foreach (array_keys((array) ($widget['permissions'] ?? [])) as $name) {
                Utils::createPermission($name);
                $generated++;
            }
        }

        // Custom permissions (from filament-shield.php)
        $custom = (array) FilamentShield::getCustomPermissions();
        foreach (array_keys($custom) as $name) {
            Utils::createPermission($name);
            $generated++;
        }

        $after = Permission::count();
        $panelLabel = $panel?->getId() ?? 'admin';
        $this->line("  Panel [{$panelLabel}]: processed {$generated} names, persisted ".($after - $before).' new permissions (total now: '.$after.').');
    }
}
