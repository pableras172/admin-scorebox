<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketingCampaigns\Tables;

use App\Jobs\SendMarketingCampaignJob;
use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use App\Services\Marketing\CampaignAudienceResolver;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MarketingCampaignsTable
{
    /**
     * @return array{total_recipients: int, needed_fresh: int, available_codes: int, deficit: int}
     */
    public static function calculatePromoStockStats(MarketingCampaign $campaign): array
    {
        $resolver = app(CampaignAudienceResolver::class);
        $recipients = $resolver->resolve($campaign->target_segment, (bool) $campaign->is_test);

        if ($campaign->is_test) {
            return [
                'total_recipients' => $recipients->count(),
                'needed_fresh' => 0,
                'available_codes' => PromotionalCode::available()->count(),
                'deficit' => 0,
            ];
        }

        $previousAssignedCodes = PromotionalCode::query()
            ->whereNotNull('assigned_email')
            ->get(['id', 'code', 'assigned_email'])
            ->keyBy(fn (PromotionalCode $c): string => strtolower(trim((string) $c->assigned_email)));

        if ($campaign->exclude_previous_promo_recipients) {
            $netRecipients = $recipients->reject(
                fn ($recipient): bool => $previousAssignedCodes->has(strtolower($recipient->email))
            )->values();
        } else {
            $netRecipients = $recipients->reject(function ($recipient) use ($previousAssignedCodes): bool {
                $hasCode = $previousAssignedCodes->has(strtolower($recipient->email));

                return $hasCode && $recipient->isPremium;
            })->values();
        }

        $neededFresh = $netRecipients->filter(
            fn ($recipient): bool => ! $previousAssignedCodes->has(strtolower($recipient->email))
        )->count();

        $available = PromotionalCode::available()->count();
        $deficit = max(0, $neededFresh - $available);

        return [
            'total_recipients' => $netRecipients->count(),
            'needed_fresh' => $neededFresh,
            'available_codes' => $available,
            'deficit' => $deficit,
        ];
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (MarketingCampaign $record): string => $record->subject),

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
                    ->color(fn (string $state, MarketingCampaign $record): string => $record->is_test ? 'warning' : match ($state) {
                        MarketingCampaign::SEGMENT_ALL => 'gray',
                        MarketingCampaign::SEGMENT_FREE => 'info',
                        MarketingCampaign::SEGMENT_PREMIUM => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        MarketingCampaign::STATUS_DRAFT => 'Borrador',
                        MarketingCampaign::STATUS_QUEUED => 'En cola',
                        MarketingCampaign::STATUS_SENDING => 'Enviando',
                        MarketingCampaign::STATUS_TEST_SENT => 'Prueba enviada',
                        MarketingCampaign::STATUS_SENT => 'Enviada',
                        MarketingCampaign::STATUS_FAILED => 'Fallida',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        MarketingCampaign::STATUS_DRAFT => 'gray',
                        MarketingCampaign::STATUS_QUEUED => 'warning',
                        MarketingCampaign::STATUS_SENDING => 'info',
                        MarketingCampaign::STATUS_TEST_SENT => 'info',
                        MarketingCampaign::STATUS_SENT => 'success',
                        MarketingCampaign::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('recipients_count')
                    ->label('Destinatarios')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('sent_count')
                    ->label('Enviados')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('failed_count')
                    ->label('Fallidos')
                    ->numeric()
                    ->alignCenter(),

                TextColumn::make('sent_at')
                    ->label('Fecha de envío')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_test')
                    ->label('Modo de campaña')
                    ->placeholder('Todas las campañas')
                    ->trueLabel('Solo campañas de prueba')
                    ->falseLabel('Solo campañas de producción'),

                SelectFilter::make('campaign_type')
                    ->label('Tipo de campaña')
                    ->options([
                        MarketingCampaign::TYPE_STANDARD => 'Estándar',
                        MarketingCampaign::TYPE_PROMOTIONAL_CODE => 'Código Promocional',
                    ]),

                SelectFilter::make('target_segment')
                    ->label('Audiencia')
                    ->options([
                        MarketingCampaign::SEGMENT_ALL => 'Todos',
                        MarketingCampaign::SEGMENT_FREE => 'Free',
                        MarketingCampaign::SEGMENT_PREMIUM => 'Premium',
                    ]),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        MarketingCampaign::STATUS_DRAFT => 'Borrador',
                        MarketingCampaign::STATUS_QUEUED => 'En cola',
                        MarketingCampaign::STATUS_SENDING => 'Enviando',
                        MarketingCampaign::STATUS_TEST_SENT => 'Prueba enviada',
                        MarketingCampaign::STATUS_SENT => 'Enviada',
                        MarketingCampaign::STATUS_FAILED => 'Fallida',
                    ]),
            ])
            ->actions([
                Action::make('send')
                    ->label(fn (MarketingCampaign $record): string => $record->is_test ? 'Enviar Prueba' : 'Enviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color(fn (MarketingCampaign $record): string => $record->is_test ? 'info' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (MarketingCampaign $record): string => $record->is_test ? 'Confirmar Envío de Prueba' : 'Confirmar Envío de Campaña')
                    ->modalDescription(function (MarketingCampaign $record): string {
                        $resolver = app(CampaignAudienceResolver::class);
                        $count = $resolver->count($record->target_segment, (bool) $record->is_test);

                        if ($record->is_test) {
                            $testEmails = implode(', ', (array) config('app.test_emails', [
                                'pableras172@hotmail.com',
                                'pableras172@gmail.com',
                            ]));

                            if ($record->isPromotionalCode()) {
                                return "Esta campaña de códigos promocionales está configurada en MODO PRUEBA. Se enviará un código simulado (PROMO-TEST-XXXXXXXX) a {$count} destinatarios de prueba ({$testEmails}) sin consumir stock real. ¿Deseas enviar el correo de prueba ahora?";
                            }

                            return "Esta campaña está configurada en MODO PRUEBA. Se enviará únicamente a {$count} destinatarios de prueba ({$testEmails}). ¿Deseas enviar el correo de prueba ahora?";
                        }

                        if ($record->isPromotionalCode()) {
                            $stats = static::calculatePromoStockStats($record);

                            if ($stats['deficit'] > 0) {
                                return "⚠️ ATENCIÓN: No hay suficientes códigos promocionales disponibles para realizar este envío.\n\n"
                                    . "• Destinatarios netos a contactar: {$stats['total_recipients']}\n"
                                    . "• Códigos nuevos requeridos: {$stats['needed_fresh']}\n"
                                    . "• Códigos libres disponibles en stock: {$stats['available_codes']}\n"
                                    . "• DÉFICIT: Faltan {$stats['deficit']} códigos promocionales.\n\n"
                                    . "El botón de envío está deshabilitado. Por favor, importa más códigos antes de enviar.";
                            }

                            return "Esta campaña de códigos promocionales despachará el correo a {$stats['total_recipients']} destinatarios netos.\n\n"
                                . "• Códigos nuevos requeridos: {$stats['needed_fresh']}\n"
                                . "• Códigos libres disponibles en stock: {$stats['available_codes']}\n\n"
                                . "¿Deseas poner en cola el envío ahora?";
                        }

                        $segmentLabel = match ($record->target_segment) {
                            MarketingCampaign::SEGMENT_FREE => 'usuarios Free',
                            MarketingCampaign::SEGMENT_PREMIUM => 'usuarios Premium',
                            default => 'todos los usuarios',
                        };

                        return "Esta acción despachará el correo a {$count} destinatarios netos ({$segmentLabel}, excluyendo bajas registradas). ¿Deseas poner en cola el envío ahora?";
                    })
                    ->modalSubmitAction(function (Action $action, MarketingCampaign $record) {
                        if ($record->isPromotionalCode() && ! $record->is_test) {
                            $stats = static::calculatePromoStockStats($record);
                            if ($stats['deficit'] > 0) {
                                return $action->disabled()->color('gray');
                            }
                        }

                        return $action;
                    })
                    ->modalSubmitActionLabel(function (MarketingCampaign $record): string {
                        if ($record->isPromotionalCode() && ! $record->is_test) {
                            $stats = static::calculatePromoStockStats($record);
                            if ($stats['deficit'] > 0) {
                                return "Stock insuficiente (Faltan {$stats['deficit']})";
                            }
                        }

                        return $record->is_test ? 'Sí, Enviar Prueba' : 'Sí, Enviar Campaña';
                    })
                    ->modalCancelActionLabel('Cancelar')
                    ->visible(fn (MarketingCampaign $record): bool => in_array($record->status, [
                        MarketingCampaign::STATUS_DRAFT,
                        MarketingCampaign::STATUS_FAILED,
                        MarketingCampaign::STATUS_TEST_SENT,
                    ], true))
                    ->action(function (MarketingCampaign $record): void {
                        if ($record->isPromotionalCode() && ! $record->is_test) {
                            $stats = static::calculatePromoStockStats($record);
                            if ($stats['deficit'] > 0) {
                                Notification::make()
                                    ->title('Envío bloqueado por stock insuficiente')
                                    ->body("Faltan {$stats['deficit']} códigos promocionales para cubrir los {$stats['needed_fresh']} destinatarios necesarios.")
                                    ->danger()
                                    ->send();

                                return;
                            }
                        }

                        $record->update([
                            'status' => MarketingCampaign::STATUS_QUEUED,
                        ]);

                        SendMarketingCampaignJob::dispatch($record->id);

                        $title = $record->is_test ? 'Envío de prueba en cola' : 'Campaña en cola';
                        $body = $record->is_test
                            ? 'El correo de prueba ha sido programado en segundo plano hacia los destinatarios configurados.'
                            : 'El envío masivo de la campaña ha sido programado en segundo plano.';

                        Notification::make()
                            ->title($title)
                            ->body($body)
                            ->success()
                            ->send();
                    }),

                Action::make('convertToReal')
                    ->label('Pasar a real')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Convertir a Campaña Real')
                    ->modalDescription('Selecciona la audiencia objetivo para convertir esta campaña de prueba en un borrador de producción.')
                    ->modalSubmitActionLabel('Convertir a Real')
                    ->form([
                        Select::make('target_segment')
                            ->label('Audiencia objetivo')
                            ->options([
                                MarketingCampaign::SEGMENT_ALL => 'Todos los usuarios (Free + Premium)',
                                MarketingCampaign::SEGMENT_FREE => 'Solo usuarios Free',
                                MarketingCampaign::SEGMENT_PREMIUM => 'Solo usuarios Premium',
                            ])
                            ->default(MarketingCampaign::SEGMENT_ALL)
                            ->required()
                            ->native(false),
                    ])
                    ->visible(fn (MarketingCampaign $record): bool => $record->is_test && in_array($record->status, [
                        MarketingCampaign::STATUS_DRAFT,
                        MarketingCampaign::STATUS_TEST_SENT,
                        MarketingCampaign::STATUS_FAILED,
                    ], true))
                    ->action(function (MarketingCampaign $record, array $data): void {
                        $record->convertToReal((string) $data['target_segment']);

                        Notification::make()
                            ->title('Campaña convertida a producción')
                            ->body('La campaña ha pasado a ser una campaña real y está lista en borrador para su envío a la audiencia seleccionada.')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (MarketingCampaign $record): bool => in_array($record->status, [
                        MarketingCampaign::STATUS_DRAFT,
                        MarketingCampaign::STATUS_TEST_SENT,
                    ], true)),
                DeleteAction::make()
                    ->visible(fn (MarketingCampaign $record): bool => in_array($record->status, [
                        MarketingCampaign::STATUS_DRAFT,
                        MarketingCampaign::STATUS_TEST_SENT,
                    ], true)),
            ])
            ->defaultSort('id', 'desc');
    }
}
