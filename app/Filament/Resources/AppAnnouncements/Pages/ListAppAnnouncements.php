<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppAnnouncements\Pages;

use App\Filament\Resources\AppAnnouncements\AppAnnouncementResource;
use Filament\Resources\Pages\ListRecords;

class ListAppAnnouncements extends ListRecords
{
    protected static string $resource = AppAnnouncementResource::class;
}
