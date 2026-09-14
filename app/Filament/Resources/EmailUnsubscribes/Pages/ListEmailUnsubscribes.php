<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailUnsubscribes\Pages;

use App\Filament\Resources\EmailUnsubscribes\EmailUnsubscribeResource;
use Filament\Resources\Pages\ListRecords;

class ListEmailUnsubscribes extends ListRecords
{
    protected static string $resource = EmailUnsubscribeResource::class;

    public function getTitle(): string
    {
        return 'Bajas de Marketing';
    }
}

