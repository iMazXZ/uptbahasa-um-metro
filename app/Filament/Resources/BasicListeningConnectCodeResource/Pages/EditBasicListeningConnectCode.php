<?php

namespace App\Filament\Resources\BasicListeningConnectCodeResource\Pages;

use App\Filament\Resources\BasicListeningConnectCodeResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditBasicListeningConnectCode extends EditRecord
{
    protected static string $resource = BasicListeningConnectCodeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 1. Validasi Khusus Tutor saat Edit
        if (auth()->user()?->hasRole('tutor')) {
            $data['restrict_to_prody'] = true;

            // Jika tutor mencoba mengganti prody
            if (isset($data['prody_id'])) {
                $allowed = auth()->user()->assignedProdyIds();
                if (!in_array($data['prody_id'], $allowed)) {
                    throw ValidationException::withMessages([
                        'prody_id' => 'Anda tidak memiliki izin untuk Program Studi ini.',
                    ]);
                }
            }
        }

        // 2. Proses Hashing Kode (Hanya jika diisi baru)
        $plain = data_get($data, 'plain_code');

        if (!empty($plain)) {
            $plain = trim($plain);
            $data['code_hash'] = hash('sha256', $plain);
            
            // Generate Hint baru
            $data['code_hint'] = BasicListeningConnectCodeResource::makeCodeHint($plain);
        }

        // 3. Parsing Rules (Legacy support)
        if (!empty($data['rules']) && is_string($data['rules'])) {
            $decoded = json_decode($data['rules'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data['rules'] = $decoded;
            }
        }

        // Hapus plain_code agar tidak error SQL
        unset($data['plain_code']);

        return $data;
    }
}