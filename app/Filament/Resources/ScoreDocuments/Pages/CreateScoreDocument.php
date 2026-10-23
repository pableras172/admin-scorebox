<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScoreDocuments\Pages;

use App\Filament\Resources\ScoreDocuments\ScoreDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateScoreDocument extends CreateRecord
{
    protected static string $resource = ScoreDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
