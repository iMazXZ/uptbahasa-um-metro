<?php

namespace Tests\Feature;

use App\Imports\BasicListeningLegacyScoreImport;
use App\Models\Prody;
use App\Models\User;
use App\Support\LegacyBasicListeningScores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyBasicListeningScoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_suffix_when_normalizing_srn(): void
    {
        $this->assertSame('21430001P', LegacyBasicListeningScores::normalizeSrn('21430001P'));
        $this->assertSame('21430001', LegacyBasicListeningScores::normalizeSrn('21430001'));
        $this->assertNotSame(
            LegacyBasicListeningScores::normalizeSrn('21430001P'),
            LegacyBasicListeningScores::normalizeSrn('21430001')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_applies_legacy_score_rules_for_expected_programs(): void
    {
        $this->assertTrue(LegacyBasicListeningScores::requiresLegacyScore(2024, 'Ilmu Komputer'));
        $this->assertTrue(LegacyBasicListeningScores::requiresLegacyScore(2021, 'Pendidikan Agama Islam'));
        $this->assertTrue(LegacyBasicListeningScores::requiresLegacyScore(2021, 'Komunikasi dan Penyiaran Islam'));
        $this->assertTrue(LegacyBasicListeningScores::requiresLegacyScore(2021, 'Pendidikan Islam Anak Usia Dini'));

        $this->assertFalse(LegacyBasicListeningScores::requiresLegacyScore(2024, 'Pendidikan Bahasa Inggris'));
        $this->assertFalse(LegacyBasicListeningScores::requiresLegacyScore(2024, 'S2 Pendidikan Bahasa Inggris'));
        $this->assertFalse(LegacyBasicListeningScores::requiresLegacyScore(2024, 'Umum'));
        $this->assertFalse(LegacyBasicListeningScores::requiresLegacyScore(2024, 'Program Studi Umum'));
        $this->assertFalse(LegacyBasicListeningScores::requiresLegacyScore(2025, 'Ilmu Komputer'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function preview_detects_duplicate_identity_conflicts_in_csv(): void
    {
        $path = storage_path('app/temp-imports/test-legacy-bl-preview.csv');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, implode(PHP_EOL, [
            'NPM;NAMA;PRODI;TAHUN;NILAI',
            '21430001;ANISA;Ilmu Komputer;2021;88',
            '21430001;ANISA LAIN;Ilmu Komputer;2021;90',
            ';;Ilmu Komputer;2021;77',
        ]) . PHP_EOL);

        $preview = (new BasicListeningLegacyScoreImport())->preview($path);

        @unlink($path);

        $this->assertSame(3, $preview['total_rows']);
        $this->assertSame(1, $preview['valid_rows']);
        $this->assertSame(2, $preview['skipped_rows']);
        $this->assertSame(1, $preview['conflict_rows']);
        $this->assertSame(1, $preview['reason_counts']['Duplikat identitas pada CSV'] ?? 0);
        $this->assertSame(1, $preview['reason_counts']['SRN/NPM dan nama sama-sama kosong'] ?? 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function preview_skips_non_passing_scores(): void
    {
        $path = storage_path('app/temp-imports/test-legacy-bl-fail.csv');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, implode(PHP_EOL, [
            'NPM;NAMA;PRODI;TAHUN;NILAI;GRADE',
            '21430001;ANISA;Ilmu Komputer;2021;54;D',
            '21430002;BUDI;Ilmu Komputer;2021;78;A',
            '21430003;CICI;Ilmu Komputer;2021;88;E',
        ]) . PHP_EOL);

        $preview = (new BasicListeningLegacyScoreImport())->preview($path);

        @unlink($path);

        $this->assertSame(3, $preview['total_rows']);
        $this->assertSame(1, $preview['valid_rows']);
        $this->assertSame(2, $preview['skipped_rows']);
        $this->assertSame(0, $preview['conflict_rows']);
        $this->assertSame(2, $preview['reason_counts']['Tidak lulus (nilai di bawah 55 / grade E)'] ?? 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function lookup_uses_existing_user_manual_score_when_import_record_is_absent(): void
    {
        $prody = Prody::query()->create(['name' => 'Ilmu Komputer']);
        $user = User::factory()->create([
            'prody_id' => $prody->id,
            'srn' => '21430058',
            'year' => 2021,
            'nilaibasiclistening' => 77,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard.biodata.manual-basic-listening-score', [
                'srn' => '21430058',
                'name' => $user->name,
                'year' => 2021,
                'prody_id' => $prody->id,
                'allow_existing_user_score' => 1,
            ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'applicable' => true,
                'found' => true,
                'score' => 77,
                'source' => 'existing_user_manual',
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function lookup_does_not_reuse_existing_user_manual_score_for_other_srn(): void
    {
        $prody = Prody::query()->create(['name' => 'Ilmu Komputer']);
        $user = User::factory()->create([
            'prody_id' => $prody->id,
            'srn' => '21430058',
            'year' => 2021,
            'nilaibasiclistening' => 99,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard.biodata.manual-basic-listening-score', [
                'srn' => '21430200',
                'name' => $user->name,
                'year' => 2021,
                'prody_id' => $prody->id,
                'allow_existing_user_score' => 0,
            ]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'applicable' => true,
                'found' => false,
                'score' => null,
                'source' => null,
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function submit_keeps_existing_user_manual_score_when_identity_stays_the_same(): void
    {
        $prody = Prody::query()->create(['name' => 'Ilmu Komputer']);
        $user = User::factory()->create([
            'name' => 'MAULANA',
            'email' => 'maulana@example.com',
            'prody_id' => $prody->id,
            'srn' => '21430058',
            'year' => 2021,
            'nilaibasiclistening' => 79,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('bl.profile.complete.submit'), [
                'name' => 'Maulana',
                'email' => 'maulana@example.com',
                'prody_id' => $prody->id,
                'srn' => '21430058',
                'year' => 2021,
            ]);

        $response->assertRedirect(route('bl.index'));
        $this->assertSame(79.0, (float) $user->fresh()->nilaibasiclistening);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function submit_rejects_srn_with_less_than_eight_digits(): void
    {
        $prody = Prody::query()->create(['name' => 'Ilmu Komputer']);
        $user = User::factory()->create([
            'name' => 'MAULANA',
            'email' => 'maulana2@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('dashboard.biodata'))
            ->post(route('bl.profile.complete.submit'), [
                'name' => 'Maulana',
                'email' => 'maulana2@example.com',
                'prody_id' => $prody->id,
                'srn' => '2143005',
                'year' => 2025,
            ]);

        $response->assertRedirect(route('dashboard.biodata'));
        $response->assertSessionHasErrors([
            'srn' => 'NPM minimal 8 digit.',
        ]);
    }
}
