<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppAnnouncements\Pages;

use App\Filament\Resources\AppAnnouncements\AppAnnouncementResource;
use App\Models\AppAnnouncement;
use Filament\Resources\Pages\CreateRecord;

class CreateAppAnnouncement extends CreateRecord
{
    protected static string $resource = AppAnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['image_file'])) {
            $data['image_url'] = asset('storage/'.ltrim((string) $data['image_file'], '/'));
        } elseif (! empty($data['image_url'])) {
            $data['image_url'] = trim((string) $data['image_url']);
        } else {
            $data['image_url'] = null;
        }
        unset($data['image_file']);

        if (($data['type'] ?? '') === AppAnnouncement::TYPE_AD) {
            $data['hide_for_pro'] = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var AppAnnouncement $record */
        $record = $this->record;

        if ($record->is_active) {
            $record->activateAndSync();
        } else {
            $record->syncToFirestore();
        }
    }
}
