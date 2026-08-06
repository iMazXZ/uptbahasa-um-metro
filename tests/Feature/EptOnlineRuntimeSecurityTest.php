<?php

namespace Tests\Feature;

use App\Models\EptOnlineAnswer;
use App\Models\EptOnlineAttempt;
use App\Models\EptOnlineForm;
use App\Models\EptOnlineQuestion;
use App\Models\EptOnlineSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EptOnlineRuntimeSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function listening_audio_is_served_through_an_authenticated_signed_route(): void
    {
        Storage::disk('local')->put('ept-online/audio/security-test.mp3', 'fake-audio-content');

        $user = User::factory()->create();

        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-AUDIO-TEST',
            'title' => 'EPT Audio Test',
            'listening_audio_path' => 'ept-online/audio/security-test.mp3',
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_LISTENING,
            'title' => 'Listening',
            'duration_minutes' => 35,
            'sort_order' => 1,
            'audio_path' => 'ept-online/audio/security-test.mp3',
        ]);

        $attempt = EptOnlineAttempt::query()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'current_section_type' => EptOnlineSection::TYPE_LISTENING,
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'current_section_started_at' => now(),
            'expires_at' => now()->addMinutes(35),
        ]);

        $url = URL::temporarySignedRoute(
            'ept-online.attempt.audio',
            now()->addMinutes(10),
            ['attempt' => $attempt->public_id],
        );

        $response = $this->actingAs($user)->get($url);

        $response
            ->assertOk()
            ->assertHeader('Accept-Ranges', 'bytes');

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function integrity_events_are_saved_as_aggregated_flags(): void
    {
        $user = User::factory()->create();

        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-INTEGRITY-TEST',
            'title' => 'EPT Integrity Test',
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_LISTENING,
            'title' => 'Listening',
            'duration_minutes' => 35,
            'sort_order' => 1,
        ]);

        $attempt = EptOnlineAttempt::query()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'current_section_type' => EptOnlineSection::TYPE_LISTENING,
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'current_section_started_at' => now(),
            'expires_at' => now()->addMinutes(35),
        ]);

        $this->actingAs($user)
            ->postJson(route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]), [
                'event' => 'shortcut_blocked',
                'context' => [
                    'page' => 'quiz',
                    'section' => 'listening',
                    'shortcut' => 'mod+c',
                    'user_agent' => str_repeat('x', 200),
                ],
            ])
            ->assertOk();

        $attempt->refresh();
        $flags = $attempt->integrity_flags ?? [];

        $this->assertIsArray($flags);
        $this->assertArrayHasKey('shortcut_blocked', $flags);
        $this->assertSame(1, $flags['shortcut_blocked']['count']);
        $this->assertSame('quiz', $flags['shortcut_blocked']['last_context']['page']);
        $this->assertSame('listening', $flags['shortcut_blocked']['last_context']['section']);
        $this->assertSame('mod+c', $flags['shortcut_blocked']['last_context']['shortcut']);
        $this->assertSame(160, strlen($flags['shortcut_blocked']['last_context']['user_agent']));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function repeated_tab_switch_cycles_auto_submit_the_attempt_at_the_limit(): void
    {
        $user = User::factory()->create();

        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-TAB-TEST',
            'title' => 'EPT Tab Test',
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_LISTENING,
            'title' => 'Listening',
            'duration_minutes' => 35,
            'sort_order' => 1,
        ]);

        $attempt = EptOnlineAttempt::query()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'current_section_type' => EptOnlineSection::TYPE_LISTENING,
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'current_section_started_at' => now(),
            'expires_at' => now()->addMinutes(35),
        ]);

        $this->actingAs($user)
            ->postJson(route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]), [
                'event' => 'tab_switch_violation',
                'context' => [
                    'page' => 'quiz',
                    'section' => 'listening',
                    'reason' => 'tab_switch',
                    'cycle_id' => 'cycle-1',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'tab_switch_count' => 1,
                'tab_switch_limit' => EptOnlineAttempt::TAB_SWITCH_LIMIT,
            ]);

        $this->actingAs($user)
            ->postJson(route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]), [
                'event' => 'tab_switch_violation',
                'context' => [
                    'page' => 'quiz',
                    'section' => 'listening',
                    'reason' => 'tab_switch',
                    'cycle_id' => 'cycle-2',
                ],
            ])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'tab_switch_count' => 2,
                'tab_switch_limit' => EptOnlineAttempt::TAB_SWITCH_LIMIT,
            ]);

        $response = $this->actingAs($user)
            ->postJson(route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]), [
                'event' => 'tab_switch_violation',
                'context' => [
                    'page' => 'quiz',
                    'section' => 'listening',
                    'reason' => 'tab_switch',
                    'cycle_id' => 'cycle-3',
                ],
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'tab_switch_count' => EptOnlineAttempt::TAB_SWITCH_LIMIT,
                'tab_switch_limit' => EptOnlineAttempt::TAB_SWITCH_LIMIT,
                'submitted' => true,
            ]);

        $attempt->refresh();

        $this->assertSame(EptOnlineAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_tab_switch_cycle_ids_do_not_increment_the_violation_counter(): void
    {
        $user = User::factory()->create();

        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-TAB-DEDUP',
            'title' => 'EPT Tab Dedup Test',
        ]);

        EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_LISTENING,
            'title' => 'Listening',
            'duration_minutes' => 35,
            'sort_order' => 1,
        ]);

        $attempt = EptOnlineAttempt::query()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'current_section_type' => EptOnlineSection::TYPE_LISTENING,
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'current_section_started_at' => now(),
            'expires_at' => now()->addMinutes(35),
        ]);

        $payload = [
            'event' => 'tab_switch_violation',
            'context' => [
                'page' => 'quiz',
                'section' => 'listening',
                'reason' => 'tab_switch',
                'cycle_id' => 'cycle-same',
            ],
        ];

        $this->actingAs($user)
            ->postJson(route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]), $payload)
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'tab_switch_count' => 1,
            ]);

        $this->actingAs($user)
            ->postJson(route('ept-online.attempt.integrity', ['attempt' => $attempt->public_id]), $payload)
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'tab_switch_count' => 1,
            ]);

        $attempt->refresh();

        $this->assertSame(EptOnlineAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertSame(1, $attempt->integrity_flags['tab_switch_violation']['count'] ?? null);
        $this->assertSame(['cycle-same'], $attempt->meta['tab_switch_cycles'] ?? []);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function answer_submission_without_an_option_does_not_create_a_placeholder_row(): void
    {
        [$user, $attempt, $question] = $this->createAttemptWithSingleQuestion();

        $response = $this->actingAs($user)
            ->postJson(route('ept-online.attempt.answer', ['attempt' => $attempt->public_id]), [
                'question_id' => $question->id,
                'q' => 0,
            ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => 'redirect',
            ]);

        $this->assertDatabaseCount('ept_online_answers', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function answer_submission_uses_selected_answer_fallback_when_submitter_value_is_missing(): void
    {
        [$user, $attempt, $question] = $this->createAttemptWithSingleQuestion();

        $response = $this->actingAs($user)
            ->postJson(route('ept-online.attempt.answer', ['attempt' => $attempt->public_id]), [
                'question_id' => $question->id,
                'q' => 0,
                'selected_answer' => 'C',
            ]);

        $response->assertOk();

        $answer = EptOnlineAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_id', $question->id)
            ->first();

        $this->assertNotNull($answer);
        $this->assertSame('C', $answer->selected_option);
        $this->assertNotNull($answer->answered_at);
    }

    /**
     * @return array{0: \App\Models\User, 1: \App\Models\EptOnlineAttempt, 2: \App\Models\EptOnlineQuestion}
     */
    private function createAttemptWithSingleQuestion(): array
    {
        $user = User::factory()->create();

        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-ANSWER-TEST-' . str()->upper(str()->random(6)),
            'title' => 'EPT Answer Test',
        ]);

        $section = EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_LISTENING,
            'title' => 'Listening',
            'duration_minutes' => 35,
            'sort_order' => 1,
        ]);

        $question = EptOnlineQuestion::query()->create([
            'form_id' => $form->id,
            'section_id' => $section->id,
            'number_in_section' => 1,
            'sort_order' => 1,
            'prompt' => 'Sample question',
            'option_a' => 'Option A',
            'option_b' => 'Option B',
            'option_c' => 'Option C',
            'option_d' => 'Option D',
            'correct_option' => 'A',
        ]);

        $attempt = EptOnlineAttempt::query()->create([
            'form_id' => $form->id,
            'user_id' => $user->id,
            'current_section_type' => EptOnlineSection::TYPE_LISTENING,
            'status' => EptOnlineAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'current_section_started_at' => now(),
            'expires_at' => now()->addMinutes(35),
        ]);

        return [$user, $attempt, $question];
    }
}
