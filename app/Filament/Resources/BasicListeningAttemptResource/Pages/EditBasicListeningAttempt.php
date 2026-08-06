<?php

namespace App\Filament\Resources\BasicListeningAttemptResource\Pages;

use App\Filament\Resources\BasicListeningAttemptResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Pages\Actions;
use Filament\Notifications\Notification;
use App\Models\BasicListeningQuestion;

class EditBasicListeningAttempt extends EditRecord
{
    protected static string $resource = BasicListeningAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Ajaib: Koreksi Otomatis
            Actions\Action::make('auto_grade')
                ->label('Koreksi Otomatis (Auto-Grade)')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Hitung Ulang Nilai?')
                ->modalSubheading('Sistem akan mencocokkan ulang jawaban teks siswa dengan kunci jawaban dan memperbarui skor.')
                ->action(function () {
                    $this->performAutoGrade();
                }),
                
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Setelah save, hitung ulang skor dari database (bukan dari form state)
     * Ini menghindari bug skor = 0 karena form repeater relationship tidak mengirim data dengan benar
     */
    protected function afterSave(): void
    {
        $attempt = $this->getRecord();
        
        // Reload answers dari database
        $attempt->load('answers');
        
        $total = $attempt->answers->count();
        $correct = $attempt->answers->where('is_correct', true)->count();
        
        // Update skor
        $newScore = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        
        // Hanya update jika berbeda (menghindari infinite loop)
        if ((float)$attempt->score !== (float)$newScore) {
            $attempt->score = $newScore;
            $attempt->saveQuietly(); // saveQuietly agar tidak trigger event lagi
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
    
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Perubahan disimpan & skor diperbarui';
    }

    /**
     * Fungsi Auto-Grade: Mencocokkan Teks vs Kunci
     */
    private function performAutoGrade()
    {
        $attempt = $this->getRecord();
        // Eager load untuk performa
        $attempt->load('answers.question'); 

        $total = 0;
        $correctCount = 0;

        foreach ($attempt->answers as $ans) {
            $q = $ans->question;
            if (!$q) continue;

            $isCorrect = false;
            
            // 1. Logika FIB
            if ($q->type === 'fib_paragraph') {
                $userVal = (string) $ans->answer;
                $idx     = (int) $ans->blank_index;
                $keys    = $q->fib_answer_key ?? [];
                $scoring = $q->fib_scoring ?? [];

                // Deteksi Index 1-based (seperti di Controller)
                $hasKey1 = array_key_exists(1, $keys) || array_key_exists('1', $keys);
                $hasKey0 = array_key_exists(0, $keys) || array_key_exists('0', $keys);
                $isOneBased = $hasKey1 && !$hasKey0;

                $lookupIndex = $isOneBased ? ($idx + 1) : $idx;
                $key = $keys[$lookupIndex] ?? null;

                if ($key) {
                    $isCorrect = $this->checkMatch($userVal, $key, $scoring);
                }
            } 
            // 2. Logika Multiple Choice / True False
            else {
                $isCorrect = ($ans->answer === $q->correct);
            }

            // Simpan status per jawaban
            $ans->is_correct = $isCorrect;
            $ans->save();

            $total++;
            if ($isCorrect) $correctCount++;
        }

        // Simpan Skor Akhir ke Attempt
        $finalScore = $total > 0 ? round(($correctCount / $total) * 100, 2) : 0;
        $attempt->score = $finalScore;
        $attempt->save();

        // Refresh halaman untuk melihat hasil
        $this->fillForm();
        
        Notification::make()
            ->title('Auto-grade selesai')
            ->body("Skor baru: {$finalScore} (Benar: {$correctCount}/{$total})")
            ->success()
            ->send();
    }

    /**
     * Helper: Mencocokkan string dengan opsi konfigurasi
     */
    private function checkMatch($userVal, $key, $scoring): bool
    {
        // Ambil setting penilaian
        $caseSensitive = filter_var($scoring['case_sensitive'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $allowTrim     = filter_var($scoring['allow_trim'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $stripPunct    = filter_var($scoring['strip_punctuation'] ?? true, FILTER_VALIDATE_BOOLEAN);

        // Normalisasi User Input
        $u = (string)$userVal;
        if ($allowTrim) $u = trim($u);
        if (!$caseSensitive) $u = mb_strtolower($u);
        if ($stripPunct) $u = preg_replace('/[\p{P}\p{S}]+/u', '', $u);
        $u = preg_replace('/\s+/u', ' ', $u); // Rapatkan spasi

        // Normalisasi Kunci (bisa string atau array opsi)
        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $k) {
            // Support Regex
            if (is_array($k) && isset($k['regex'])) {
                if (@preg_match('/' . $k['regex'] . '/ui', $userVal)) return true;
                continue;
            }

            $kStr = (string)$k;
            if ($allowTrim) $kStr = trim($kStr);
            if (!$caseSensitive) $kStr = mb_strtolower($kStr);
            if ($stripPunct) $kStr = preg_replace('/[\p{P}\p{S}]+/u', '', $kStr);
            $kStr = preg_replace('/\s+/u', ' ', $kStr);

            if ($u === $kStr) return true;
        }

        return false;
    }
}