<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusiciansDirectory\Pages;

use App\Filament\Resources\MusiciansDirectory\MusiciansDirectoryResource;
use Filament\Resources\Pages\ListRecords;

class ListMusiciansDirectory extends ListRecords
{
    protected static string $resource = MusiciansDirectoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
