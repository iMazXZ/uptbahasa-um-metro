<?php

namespace App\Filament\Resources\BasicListeningConnectCodeResource\Pages;

use App\Filament\Resources\BasicListeningConnectCodeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateBasicListeningConnectCode extends CreateRecord
{
    protected static string $resource = BasicListeningConnectCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Set Pembuat (Wajib)
        $data['created_by'] = auth()->id();

        // 2. Validasi Khusus Tutor (Security Layer)
        if (auth()->user()?->hasRole('tutor')) {
            // Tutor wajib mengaktifkan pembatasan prodi
            $data['restrict_to_prody'] = true;

            // Cek apakah prody yang dipilih valid milik tutor tersebut
            $allowed = auth()->user()->assignedProdyIds(); // Pastikan method ini ada di User Model
            if (!in_array($data['prody_id'], $allowed)) {
                throw ValidationException::withMessages([
                    'prody_id' => 'Anda tidak memiliki izin untuk membuat kode bagi Program Studi ini.',
                ]);
            }
        }

        // 3. Proses Hashing Kode
        // Kita ambil data menggunakan data_get untuk keamanan akses array
        $plain = data_get($data, 'plain_code');

        if ($plain) {
            $plain = trim($plain);
            $data['code_hash'] = hash('sha256', $plain);
            
            // Panggil fungsi helper static dari Resource agar kode rapi (DRY)
            // Jika di Resource belum ada public static, Anda bisa pakai $this->makeCodeHint($plain)
            $data['code_hint'] = BasicListeningConnectCodeResource::makeCodeHint($plain); 
        }

        // 4. Parsing Rules (Legacy support)
        if (!empty($data['rules']) && is_string($data['rules'])) {
            $decoded = json_decode($data['rules'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['rules'] = $decoded;
            }
        }

        // Hapus plain_code agar tidak error SQL "Column not found"
        unset($data['plain_code']); 

        return $data;
    }
}