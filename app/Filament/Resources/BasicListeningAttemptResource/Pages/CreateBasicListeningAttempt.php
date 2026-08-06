<?php

namespace App\Filament\Resources\BasicListeningAttemptResource\Pages;

use App\Filament\Resources\BasicListeningAttemptResource;
use App\Models\BasicListeningQuiz;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;

class CreateBasicListeningAttempt extends CreateRecord
{
    protected static string $resource = BasicListeningAttemptResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan session_id ikut terset sesuai quiz
        if (empty($data['session_id']) && ! empty($data['quiz_id'])) {
            $data['session_id'] = BasicListeningQuiz::find($data['quiz_id'])?->session_id;
        }

        // Jika tidak diisi, asumsikan manual submit sekarang
        if (empty($data['submitted_at'])) {
            $data['submitted_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Hitung ulang skor agar konsisten dengan logika finalize/regrade
        Artisan::call('bl:regrade-attempts', [
            '--attempt'    => $this->record->id,
            '--only-weird' => 0,
        ]);

        Notification::make()
            ->title('Attempt dibuat')
            ->body('Jawaban tersimpan dan skor dihitung otomatis.')
            ->success()
            ->send();
    }
}
