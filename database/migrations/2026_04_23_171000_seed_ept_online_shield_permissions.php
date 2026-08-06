<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $actions = [
        'view',
        'view_any',
        'create',
        'update',
        'restore',
        'restore_any',
        'replicate',
        'reorder',
        'delete',
        'delete_any',
        'force_delete',
        'force_delete_any',
    ];

    /**
     * @var array<int, string>
     */
    private array $resources = [
        'ept::online::form',
        'ept::online::section',
        'ept::online::passage',
        'ept::online::question',
        'ept::online::access::token',
        'ept::online::attempt',
        'ept::online::result',
    ];

    /**
     * @var array<int, string>
     */
    private array $defaultRoleNames = [
        'super_admin',
        'Admin',
        'Staf Administrasi',
        'Kepala Lembaga',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($this->resources)
            ->flatMap(fn (string $resource): array => array_map(
                fn (string $action): string => $action . '_' . $resource,
                $this->actions,
            ))
            ->values();

        $permissionModels = $permissions->map(fn (string $name): Permission => Permission::query()->firstOrCreate(
            ['name' => $name, 'guard_name' => 'web'],
        ));

        foreach ($this->defaultRoleNames as $roleName) {
            Role::query()
                ->firstOrCreate(['name' => $roleName, 'guard_name' => 'web'])
                ->givePermissionTo($permissionModels);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        collect($this->resources)
            ->flatMap(fn (string $resource): array => array_map(
                fn (string $action): string => $action . '_' . $resource,
                $this->actions,
            ))
            ->each(fn (string $name): ?bool => Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first()
                ?->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
