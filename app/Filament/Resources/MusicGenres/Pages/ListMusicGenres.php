<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicGenres\Pages;

use App\Filament\Resources\MusicGenres\MusicGenreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMusicGenres extends ListRecords
{
    protected static string $resource = MusicGenreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Género')
                ->icon('heroicon-o-plus'),
        ];
    }
}
