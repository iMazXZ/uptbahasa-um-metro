<?php

namespace App\Filament\Resources\EptOnlineResultResource\Pages;

use App\Filament\Resources\EptOnlineResultResource;
use App\Models\EptOnlineResult;
use Filament\Resources\Pages\EditRecord;

class EditEptOnlineResult extends EditRecord
{
    protected static string $resource = EptOnlineResultResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $listeningScaled = isset($data['listening_scaled']) && $data['listening_scaled'] !== ''
            ? (int) $data['listening_scaled']
            : null;
        $structureScaled = isset($data['structure_scaled']) && $data['structure_scaled'] !== ''
            ? (int) $data['structure_scaled']
            : null;
        $readingScaled = isset($data['reading_scaled']) && $data['reading_scaled'] !== ''
            ? (int) $data['reading_scaled']
            : null;

        $autoTotalScaled = EptOnlineResult::calculateTotalScaled(
            $listeningScaled,
            $structureScaled,
            $readingScaled
        );

        if ($autoTotalScaled !== null) {
            $data['total_scaled'] = $autoTotalScaled;
        }

        if (blank($data['scale_version'] ?? null) && $autoTotalScaled !== null) {
            $data['scale_version'] = EptOnlineResult::SCALE_VERSION_AUTO;
        }

        $isPublished = (bool) ($data['is_published'] ?? false);

        if ($isPublished && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (! $isPublished) {
            $data['published_at'] = null;
        }

        return $data;
    }
}
