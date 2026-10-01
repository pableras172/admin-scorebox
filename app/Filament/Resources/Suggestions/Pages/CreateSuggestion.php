<?php

namespace App\Filament\Resources\Suggestions\Pages;

use App\Filament\Resources\Suggestions\SuggestionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSuggestion extends CreateRecord
{
    protected static string $resource = SuggestionResource::class;
}
