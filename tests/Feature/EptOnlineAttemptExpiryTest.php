<?php

namespace Tests\Feature;

use App\Models\EptOnlineAttempt;
use App\Models\EptOnlineForm;
use App\Models\EptOnlineSection;
use App\Support\EptOnlineAttemptFinalizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EptOnlineAttemptExpiryTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function expired_closed_attempt_is_caught_up_and_submitted_by_server(): void
    {
        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-EXPIRY-TEST',
            'title' => 'EPT Expiry Test',
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_LISTENING,
            'title' => 'Listening',
            'duration_minutes' => 35,
            'sort_order' => 1,
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_STRUCTURE,
            'title' => 'Structure',
            'duration_minutes' => 25,
            'sort_order' => 2,
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_READING,
            'title' => 'Reading',
            'duration_minutes' => 55,
            'sort_order' => 3,
        ]);

        $startedAt = Carbon::parse('2026-04-23 18:09:00');
        $attempt = EptOnlineAttempt::query()->create([
            'form_id' => $form->id,
            'current_section_type' => EptOnlineSection::TYPE_LISTENING,
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => $startedAt,
            'current_section_started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes(35),
        ]);

        app(EptOnlineAttemptFinalizer::class)->catchUpExpiredAttempt(
            $attempt,
            Carbon::parse('2026-04-23 20:07:00'),
        );

        $attempt->refresh();

        $this->assertSame(EptOnlineAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertEquals('2026-04-23 20:04:00', $attempt->submitted_at->format('Y-m-d H:i:s'));
        $this->assertNull($attempt->expires_at);
        $this->assertDatabaseHas('ept_online_results', [
            'attempt_id' => $attempt->id,
            'listening_raw' => 0,
            'structure_raw' => 0,
            'reading_raw' => 0,
        ]);
    }
}
