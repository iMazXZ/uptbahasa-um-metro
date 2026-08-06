<?php

namespace Tests\Feature;

use App\Imports\InteractiveClassScoreImport;
use App\Models\InteractiveClassScore;
use App\Models\Prody;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractiveClassScoreImportTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_imports_interactive_class_csv_and_syncs_matching_user_semester_score(): void
    {
        $user = User::factory()->create([
            'srn' => '19340001',
        ]);

        $filePath = tempnam(sys_get_temp_dir(), 'interactive-class-');
        file_put_contents($filePath, implode(PHP_EOL, [
            'NO_INDUK;GROUP;No Absen;SRN;NAMA;LISTENING;SPEAKING;READING;WRITING;PHONONLOGY;STRUCTURE;VOCABULARY;TOTAL;AVERAGE;HURUF MUTU;BULAN;TAHUN;SEMESTER;PREDICATE',
            '1;2;1;19340001;Desti Rahma Pratiwi;80;85;90;83;83;78;85;584;83;A;X;2020;2;Excellent',
            '2;2;2;19340002;Baris Gagal;0;0;0;0;0;0;0;0;0;E;X;2020;2;Fail',
            '3;2;3;19340003;Baris Kosong;80;80;80;80;80;80;80;560;;A;X;2020;2;Excellent',
        ]) . PHP_EOL);

        $summary = (new InteractiveClassScoreImport())->import($filePath);

        @unlink($filePath);

        $this->assertSame(1, $summary['imported_rows']);
        $this->assertSame(1, $summary['synced_users']);
        $this->assertSame(2, $summary['skipped_rows']);

        $record = InteractiveClassScore::query()->first();
        $this->assertNotNull($record);
        $this->assertSame('19340001', $record->srn);
        $this->assertSame('Pendidikan Bahasa Inggris', $record->study_program);
        $this->assertSame(2, $record->semester);
        $this->assertSame(2020, $record->source_year);
        $this->assertSame('A', $record->grade);
        $this->assertSame(83.0, (float) $record->score);

        $this->assertDatabaseMissing('interactive_class_scores', [
            'srn' => '19340002',
        ]);
        $this->assertDatabaseMissing('interactive_class_scores', [
            'srn' => '19340003',
        ]);

        $user->refresh();
        $this->assertSame(83.0, (float) $user->interactive_class_2);
        $this->assertNull($user->interactive_class_1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_imports_interactive_bahasa_arab_into_the_same_archive_and_syncs_user_fields(): void
    {
        $user = User::factory()->create([
            'srn' => '21150001',
        ]);

        $filePath = tempnam(sys_get_temp_dir(), 'interactive-arabic-');
        file_put_contents($filePath, implode(PHP_EOL, [
            'SRN;NAMA;PRODI;AVERAGE;HURUF MUTU;TAHUN;SEMESTER',
            '21150001;Mahasiswa Arab;Pendidikan Agama Islam;88;A;2021;1',
            '21150001;Mahasiswa Arab;Pendidikan Agama Islam;90;A;2021;2',
            '21150001;Mahasiswa Arab;Pendidikan Agama Islam;0;E;2021;2',
        ]) . PHP_EOL);

        $summary = (new InteractiveClassScoreImport())->import(
            $filePath,
            track: InteractiveClassScore::TRACK_ARABIC,
        );

        @unlink($filePath);

        $this->assertSame(2, $summary['imported_rows']);
        $this->assertSame(1, $summary['skipped_rows']);

        $this->assertDatabaseHas('interactive_class_scores', [
            'srn' => '21150001',
            'track' => InteractiveClassScore::TRACK_ARABIC,
            'semester' => 1,
            'study_program' => 'Pendidikan Agama Islam',
        ]);

        $this->assertDatabaseMissing('interactive_class_scores', [
            'srn' => '21150001',
            'track' => InteractiveClassScore::TRACK_ARABIC,
            'score' => 0,
        ]);

        $user->refresh();
        $this->assertSame(88.0, (float) $user->interactive_bahasa_arab_1);
        $this->assertSame(90.0, (float) $user->interactive_bahasa_arab_2);
        $this->assertNull($user->interactive_class_1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function biodata_lookup_uses_srn_only_for_existing_interactive_fallback(): void
    {
        $prodyRequest = Prody::query()->create(['name' => 'Pendidikan Bahasa Inggris']);
        $prodyUser = Prody::query()->create(['name' => 'Ilmu Komputer']);

        $user = User::factory()->create([
            'srn' => '20340039',
            'year' => 2018,
            'prody_id' => $prodyUser->id,
            'interactive_class_1' => 84,
            'interactive_class_2' => 85,
            'interactive_class_3' => 87,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.biodata.manual-basic-listening-score', [
            'srn' => '20340039',
            'name' => $user->name,
            'year' => 2020,
            'prody_id' => $prodyRequest->id,
        ]));

        $response->assertOk()
            ->assertJson([
                'interactive_class_found' => true,
                'interactive_class_scores' => [
                    '1' => 84,
                    '2' => 85,
                    '3' => 87,
                ],
            ]);
    }
}
