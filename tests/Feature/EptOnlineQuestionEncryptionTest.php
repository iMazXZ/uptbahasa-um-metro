<?php

namespace Tests\Feature;

use App\Models\EptOnlineForm;
use App\Models\EptOnlineQuestion;
use App\Models\EptOnlineSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EptOnlineQuestionEncryptionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function correct_option_is_encrypted_at_rest(): void
    {
        $form = EptOnlineForm::query()->create([
            'code' => 'EPT-SECURITY-TEST',
            'title' => 'EPT Security Test',
        ]);

        $section = EptOnlineSection::query()->create([
            'form_id' => $form->id,
            'type' => EptOnlineSection::TYPE_STRUCTURE,
            'title' => 'Structure',
            'duration_minutes' => 25,
            'sort_order' => 1,
        ]);

        $question = EptOnlineQuestion::query()->create([
            'form_id' => $form->id,
            'section_id' => $section->id,
            'number_in_section' => 1,
            'sort_order' => 1,
            'prompt' => 'Test prompt',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_option' => 'B',
        ]);

        $rawCorrectOption = DB::table('ept_online_questions')
            ->where('id', $question->id)
            ->value('correct_option');

        $this->assertSame('B', $question->refresh()->correct_option);
        $this->assertNotSame('B', $rawCorrectOption);
        $this->assertSame('B', Crypt::decryptString($rawCorrectOption));
    }
}
