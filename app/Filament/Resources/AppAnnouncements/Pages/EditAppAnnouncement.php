<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppAnnouncements\Pages;

use App\Filament\Resources\AppAnnouncements\AppAnnouncementResource;
use App\Models\AppAnnouncement;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppAnnouncement extends EditRecord
{
    protected static string $resource = AppAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(function (AppAnnouncement $record): void {
                    if ($record->is_active) {
                        AppAnnouncement::deactivateAllAndSync();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! empty($data['image_url']) && str_contains((string) $data['image_url'], '/storage/announcements/')) {
            $parts = explode('/storage/', (string) $data['image_url']);
            $data['image_file'] = end($parts);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        /** @var AppAnnouncement $record */
        $record = $this->record;

        if ($record->is_active) {
            $record->activateAndSync();
        } else {
            // If it was deactivated during edit, ensure Firestore knows it's disabled if no other is active
            $hasOtherActive = AppAnnouncement::where('id', '!=', $record->id)->where('is_active', true)->exists();
            if (! $hasOtherActive) {
                $record->deactivateAndSync();
            }
        }
    }
}
