<?php

namespace App\Filament\Resources\EptOnlineAccessTokenResource\Pages;

use App\Filament\Resources\EptOnlineAccessTokenResource;
use Filament\Resources\Pages\EditRecord;

class EditEptOnlineAccessToken extends EditRecord
{
    protected static string $resource = EptOnlineAccessTokenResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $plain = trim((string) ($data['plain_token'] ?? ''));
        if ($plain !== '') {
            $data['token_hash'] = hash('sha256', $plain);
            $data['token_hint'] = EptOnlineAccessTokenResource::makeTokenHint($plain);
        }

        unset($data['plain_token']);

        return $data;
    }
}
