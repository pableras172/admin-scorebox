<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScoreDocuments\Pages;

use App\Filament\Resources\ScoreDocuments\ScoreDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditScoreDocument extends EditRecord
{
    protected static string $resource = ScoreDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['file_path'])) {
            $disk = Storage::disk('local');
            if ($disk->exists($data['file_path'])) {
                $data['file_size'] = $disk->size($data['file_path']);
                $data['mime_type'] = $disk->mimeType($data['file_path']) ?: 'application/pdf';
                if (empty($data['file_name'])) {
                    $data['file_name'] = basename($data['file_path']);
                }
            }
        }

        return $data;
    }
}
