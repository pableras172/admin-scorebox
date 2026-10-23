<?php

declare(strict_types=1);

namespace App\Filament\Resources\BandTypes\Pages;

use App\Filament\Resources\BandTypes\BandTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBandType extends EditRecord
{
    protected static string $resource = BandTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
