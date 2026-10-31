<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicianReports\Pages;

use App\Filament\Resources\MusicianReports\MusicianReportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMusicianReport extends EditRecord
{
    protected static string $resource = MusicianReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
