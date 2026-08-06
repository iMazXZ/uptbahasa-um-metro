<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function the_application_returns_a_successful_response(): void
    {
        // Seed minimal role yang dipakai di cache HomeController
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'tutor', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'pendaftar', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Penerjemah', 'guard_name' => 'web']);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
