<?php

namespace Tests\Feature;

use App\Models\EptOnlineForm;
use App\Models\EptOnlineQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EptOnlineShieldPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function ept_online_resources_require_shield_permissions(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', EptOnlineForm::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', EptOnlineQuestion::class));

        $user->givePermissionTo('view_any_ept::online::form');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', EptOnlineForm::class));
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', EptOnlineQuestion::class));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function internal_roles_receive_default_ept_online_permissions(): void
    {
        foreach (['super_admin', 'Admin', 'Staf Administrasi', 'Kepala Lembaga'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();

            $this->assertNotNull($role);
            $this->assertTrue($role->hasPermissionTo('view_any_ept::online::form'));
            $this->assertTrue($role->hasPermissionTo('update_ept::online::question'));
            $this->assertTrue($role->hasPermissionTo('delete_any_ept::online::attempt'));
        }
    }
}
