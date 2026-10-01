<?php

declare(strict_types=1);

namespace App\Filament\Resources\PromotionalCodes\Pages;

use App\Filament\Resources\PromotionalCodes\PromotionalCodeResource;
use Filament\Resources\Pages\ListRecords;

class ListPromotionalCodes extends ListRecords
{
    protected static string $resource = PromotionalCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
