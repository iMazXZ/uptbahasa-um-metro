<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Jobs\QueueHeartbeatPing;
use App\Models\User;
use App\Models\BasicListeningSurvey;
use App\Models\BasicListeningSurveyQuestion;
use App\Models\BasicListeningSurveyResponse;
use App\Models\BasicListeningSurveyAnswer;
use App\Models\BasicListeningSupervisor;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new QueueHeartbeatPing())
    ->everyMinute()
    ->name('queue-heartbeat-ping');

Schedule::command('ept-online:expire-attempts')
    ->everyMinute()
    ->withoutOverlapping()
    ->name('ept-online-expire-attempts');

Artisan::command('bl:seed-survey-dummy 
    {--count=20 : Jumlah respon per survey}
    {--category= : Hanya untuk kategori tertentu (tutor|supervisor|materi)}
    {--truncate= : ID survey yang ingin dikosongkan dulu (coma-separated)}', function () {
    $count      = max(1, (int) $this->option('count'));
    $category   = $this->option('category');
    $truncateId = $this->option('truncate');

    $surveys = BasicListeningSurvey::query()
        ->when($category, fn ($q) => $q->where('category', $category))
        ->where('is_active', true)
        ->get();

    if ($surveys->isEmpty()) {
        $this->error('Tidak ada survey aktif untuk disemai.');
        return self::FAILURE;
    }

    // Truncate selected survey responses (optional)
    if ($truncateId) {
        $ids = collect(explode(',', $truncateId))->map(fn ($v) => (int) trim($v))->filter()->all();
        if ($ids) {
            BasicListeningSurveyResponse::whereIn('survey_id', $ids)->delete();
            $this->warn('Sudah mengosongkan response untuk survey id: '.implode(', ', $ids));
        }
    }

    $students    = User::query()->role('pendaftar')->get();
    $tutors      = User::query()->role('tutor')->get();
    $supervisors = BasicListeningSupervisor::query()->where('is_active', true)->get();
    $fallbackUser = User::query()->first();

    foreach ($surveys as $survey) {
        $questions = BasicListeningSurveyQuestion::where('survey_id', $survey->id)->get();
        if ($questions->isEmpty()) {
            $this->warn("Skip survey {$survey->id} ({$survey->title}) karena tidak ada pertanyaan.");
            continue;
        }

        $this->info("Menambahkan {$count} response untuk survey {$survey->id} [{$survey->category}] {$survey->title}");

        DB::transaction(function () use ($survey, $questions, $students, $tutors, $supervisors, $fallbackUser, $count) {
            for ($i = 0; $i < $count; $i++) {
                $user = $students->isNotEmpty()
                    ? $students->random()
                    : ($fallbackUser ?? User::create([
                        'name'  => 'Dummy Pendaftar '.Str::random(4),
                        'email' => 'dummy.'.Str::random(6).'@example.test',
                        'password' => 'password',
                    ]));

                $tutorId = $survey->category === 'tutor'
                    ? ($tutors->isNotEmpty() ? $tutors->random()->id : null)
                    : null;

                $supervisorId = $survey->category === 'supervisor'
                    ? ($supervisors->isNotEmpty() ? $supervisors->random()->id : null)
                    : null;

                $submittedAt = now()->subDays(random_int(0, 30))->setTime(random_int(7, 21), random_int(0, 59));

                $response = BasicListeningSurveyResponse::create([
                    'survey_id'     => $survey->id,
                    'user_id'       => $user->id,
                    'submitted_at'  => $submittedAt,
                    'tutor_id'      => $tutorId,
                    'supervisor_id' => $supervisorId,
                    'meta'          => ['dummy' => true],
                ]);

                foreach ($questions as $q) {
                    if ($q->type === 'likert') {
                        BasicListeningSurveyAnswer::create([
                            'response_id' => $response->id,
                            'question_id' => $q->id,
                            'likert_value'=> random_int(4, 5),
                        ]);
                    } else {
                        $textPool = [
                            'Sangat baik dan membantu.',
                            'Perlu sedikit peningkatan.',
                            'Pengalaman cukup menyenangkan.',
                            'Komunikasi jelas dan ramah.',
                            'Terima kasih atas bantuannya.',
                        ];
                        BasicListeningSurveyAnswer::create([
                            'response_id' => $response->id,
                            'question_id' => $q->id,
                            'text_value'  => Arr::random($textPool),
                        ]);
                    }
                }
            }
        });
    }

    $this->info('Selesai menambahkan data dummy.');
    return self::SUCCESS;
})->purpose('Seed data dummy untuk hasil kuesioner Basic Listening');
