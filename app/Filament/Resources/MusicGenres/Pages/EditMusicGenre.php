<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicGenres\Pages;

use App\Filament\Resources\MusicGenres\MusicGenreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMusicGenre extends EditRecord
{
    protected static string $resource = MusicGenreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
