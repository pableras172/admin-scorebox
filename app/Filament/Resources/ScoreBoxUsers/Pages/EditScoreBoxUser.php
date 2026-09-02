<?php

namespace App\Filament\Resources\ScoreBoxUsers\Pages;

use App\Filament\Resources\ScoreBoxUsers\ScoreBoxUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScoreBoxUser extends EditRecord
{
    protected static string $resource = ScoreBoxUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
