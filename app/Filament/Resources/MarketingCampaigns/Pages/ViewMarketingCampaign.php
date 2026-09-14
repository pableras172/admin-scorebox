<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketingCampaigns\Pages;

use App\Filament\Resources\MarketingCampaigns\MarketingCampaignResource;
use App\Models\MarketingCampaign;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMarketingCampaign extends ViewRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (MarketingCampaign $record): bool => in_array($record->status, [
                    MarketingCampaign::STATUS_DRAFT,
                    MarketingCampaign::STATUS_TEST_SENT,
                ], true)),
        ];
    }
}

