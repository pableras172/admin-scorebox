<?php

declare(strict_types=1);

namespace App\Filament\Resources\BandTypes\Pages;

use App\Filament\Resources\BandTypes\BandTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBandTypes extends ListRecords
{
    protected static string $resource = BandTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Tipo de Banda')
                ->icon('heroicon-o-plus'),
        ];
    }
}
