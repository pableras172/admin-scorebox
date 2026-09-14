<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketingCampaigns\Pages;

use App\Filament\Resources\MarketingCampaigns\MarketingCampaignResource;
use App\Models\MarketingCampaign;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (MarketingCampaign $record): bool => in_array($record->status, [
                    MarketingCampaign::STATUS_DRAFT,
                    MarketingCampaign::STATUS_TEST_SENT,
                ], true)),
        ];
    }
}

