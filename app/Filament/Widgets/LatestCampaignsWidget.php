<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\MarketingCampaigns\MarketingCampaignResource;
use App\Models\MarketingCampaign;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestCampaignsWidget extends TableWidget
{
    protected static ?string $heading = 'Últimas Campañas de Marketing';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MarketingCampaign::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('subject')
                    ->label('Asunto')
                    ->limit(40),

                TextColumn::make('campaign_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        MarketingCampaign::TYPE_PROMOTIONAL_CODE => 'Código Promo',
                        default => 'Estándar',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        MarketingCampaign::TYPE_PROMOTIONAL_CODE => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('target_segment')
                    ->label('Audiencia')
                    ->badge()
                    ->formatStateUsing(function (string $state, MarketingCampaign $record): string {
                        if ($record->is_test) {
                            return 'Prueba (Test)';
                        }

                        return match ($state) {
                            MarketingCampaign::SEGMENT_ALL => 'Todos',
                            MarketingCampaign::SEGMENT_FREE => 'Free',
                            MarketingCampaign::SEGMENT_PREMIUM => 'Premium',
                            default => $state,
                        };
                    })
                    ->color(fn (string $state, MarketingCampaign $record): string => $record->is_test ? 'warning' : 'gray'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        MarketingCampaign::STATUS_DRAFT => 'Borrador',
                        MarketingCampaign::STATUS_SCHEDULED => 'Programada',
                        MarketingCampaign::STATUS_QUEUED => 'En cola',
                        MarketingCampaign::STATUS_SENDING => 'Enviando',
                        MarketingCampaign::STATUS_TEST_SENT => 'Prueba enviada',
                        MarketingCampaign::STATUS_SENT => 'Enviada',
                        MarketingCampaign::STATUS_FAILED => 'Fallida',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        MarketingCampaign::STATUS_DRAFT => 'gray',
                        MarketingCampaign::STATUS_SCHEDULED => 'primary',
                        MarketingCampaign::STATUS_QUEUED => 'warning',
                        MarketingCampaign::STATUS_SENDING => 'info',
                        MarketingCampaign::STATUS_TEST_SENT => 'info',
                        MarketingCampaign::STATUS_SENT => 'success',
                        MarketingCampaign::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('sent_count')
                    ->label('Enviados')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (MarketingCampaign $record): string => MarketingCampaignResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
