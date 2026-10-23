<?php

declare(strict_types=1);

namespace App\Filament\Resources\BandTypes\Pages;

use App\Filament\Resources\BandTypes\BandTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBandType extends CreateRecord
{
    protected static string $resource = BandTypeResource::class;
}
