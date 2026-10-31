<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicianReports\Pages;

use App\Filament\Resources\MusicianReports\MusicianReportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMusicianReport extends ViewRecord
{
    protected static string $resource = MusicianReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
