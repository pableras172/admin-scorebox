<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScoreDocuments\Pages;

use App\Filament\Resources\ScoreDocuments\ScoreDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScoreDocuments extends ListRecords
{
    protected static string $resource = ScoreDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva Partitura / Documento')
                ->icon('heroicon-o-plus'),
        ];
    }
}
