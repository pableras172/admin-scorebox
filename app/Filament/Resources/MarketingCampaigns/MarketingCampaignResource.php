<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketingCampaigns;

use App\Filament\Resources\MarketingCampaigns\Pages\CreateMarketingCampaign;
use App\Filament\Resources\MarketingCampaigns\Pages\EditMarketingCampaign;
use App\Filament\Resources\MarketingCampaigns\Pages\ListMarketingCampaigns;
use App\Filament\Resources\MarketingCampaigns\Pages\ViewMarketingCampaign;
use App\Filament\Resources\MarketingCampaigns\Schemas\MarketingCampaignForm;
use App\Filament\Resources\MarketingCampaigns\Tables\MarketingCampaignsTable;
use App\Models\MarketingCampaign;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketingCampaignResource extends Resource
{
    protected static ?string $model = MarketingCampaign::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationGroup(): ?string
    {
        return 'Marketing';
    }

    public static function getNavigationLabel(): string
    {
        return 'Campañas';
    }

    public static function getModelLabel(): string
    {
        return 'campaña';
    }

    public static function getPluralModelLabel(): string
    {
        return 'campañas';
    }

    public static function form(Schema $schema): Schema
    {
        return MarketingCampaignForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketingCampaignsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarketingCampaigns::route('/'),
            'create' => CreateMarketingCampaign::route('/create'),
            'edit' => EditMarketingCampaign::route('/{record}/edit'),
            'view' => ViewMarketingCampaign::route('/{record}'),
        ];
    }
}
