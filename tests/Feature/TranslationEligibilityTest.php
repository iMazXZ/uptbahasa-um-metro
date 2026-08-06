<?php

namespace Tests\Feature;

use App\Models\Prody;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TranslationEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_student_with_manual_basic_listening_score_can_access_translation_page(): void
    {
        $role = Role::firstOrCreate(['name' => 'pendaftar', 'guard_name' => 'web']);

        $prody = Prody::query()->create([
            'name' => 'Teknik Informatika',
        ]);

        $user = User::factory()->create([
            'prody_id' => $prody->id,
            'srn' => '24123456',
            'year' => 2024,
            'nilaibasiclistening' => 78,
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('dashboard.translation'));

        $response->assertOk();
        $response->assertDontSee('Wajib Basic Listening');
        $response->assertSee('Ajukan Sekarang');
    }
}
