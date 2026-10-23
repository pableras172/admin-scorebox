<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicGenres\Pages;

use App\Filament\Resources\MusicGenres\MusicGenreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMusicGenre extends CreateRecord
{
    protected static string $resource = MusicGenreResource::class;
}
