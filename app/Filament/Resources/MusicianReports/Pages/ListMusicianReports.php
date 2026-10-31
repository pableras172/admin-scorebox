<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicianReports\Pages;

use App\Filament\Resources\MusicianReports\MusicianReportResource;
use Filament\Resources\Pages\ListRecords;

class ListMusicianReports extends ListRecords
{
    protected static string $resource = MusicianReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
