<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppOtp;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppOtpTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_dispatches_job_when_sending_otp(): void
    {
        Queue::fake();
        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'test-key',
        ]);

        $user = User::factory()->create();
        SiteSetting::updateOrCreate(
            ['key' => 'otp_enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'whatsapp', 'label' => 'OTP Enabled']
        );

        $response = $this
            ->actingAs($user)
            ->post('/api/whatsapp/send-otp', ['whatsapp' => '081234567890']);

        $response->assertStatus(200)->assertJson(['success' => true]);
        Queue::assertPushed(SendWhatsAppOtp::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rejects_duplicate_whatsapp_number(): void
    {
        config([
            'whatsapp.enabled' => true,
            'whatsapp.api_key' => 'test-key',
        ]);

        $existing = User::factory()->create(['whatsapp' => '6281234567890']);
        $user = User::factory()->create();
        SiteSetting::updateOrCreate(
            ['key' => 'otp_enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'whatsapp', 'label' => 'OTP Enabled']
        );

        $response = $this
            ->actingAs($user)
            ->post('/api/whatsapp/send-otp', ['whatsapp' => '081234567890']);

        $response->assertStatus(422);
        $this->assertEquals($existing->id, $existing->fresh()->id); // ensure not touched
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_clears_verification_when_save_only_changes_number(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
            'whatsapp_otp' => '123456',
            'whatsapp_otp_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/api/whatsapp/save-only', ['whatsapp' => '081234567890']);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $user->refresh();

        $this->assertSame('6281234567890', $user->whatsapp);
        $this->assertNull($user->whatsapp_verified_at);
        $this->assertNull($user->whatsapp_otp);
        $this->assertNull($user->whatsapp_otp_expires_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_clears_verification_when_legacy_update_changes_number(): void
    {
        $user = User::factory()->create([
            'whatsapp' => '628111111111',
            'whatsapp_verified_at' => now(),
            'whatsapp_otp' => '123456',
            'whatsapp_otp_expires_at' => now()->addMinutes(5),
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/api/whatsapp/update', ['whatsapp' => '081234567890']);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $user->refresh();

        $this->assertSame('6281234567890', $user->whatsapp);
        $this->assertNull($user->whatsapp_verified_at);
        $this->assertNull($user->whatsapp_otp);
        $this->assertNull($user->whatsapp_otp_expires_at);
    }
}
