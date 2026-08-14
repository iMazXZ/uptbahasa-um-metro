<?php

namespace App\Filament\Resources\EptOnlineAccessTokenResource\Pages;

use App\Filament\Resources\EptOnlineAccessTokenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEptOnlineAccessToken extends CreateRecord
{
    protected static string $resource = EptOnlineAccessTokenResource::class;

    protected function afterCreate(): void
    {
        $this->record->proctors()->sync($this->data['proctors'] ?? []);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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
