<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppAnnouncements\Tables;

use App\Models\AppAnnouncement;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AppAnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_active')
                    ->label('En vivo')
                    ->boolean()
                    ->trueIcon('heroicon-s-bolt')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),

                ImageColumn::make('image_url')
                    ->label('Banner')
                    ->circular(false)
                    ->square()
                    ->height(40)
                    ->placeholder('-'),

                TextColumn::make('title')
                    ->label('Título / Patrocinador')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AppAnnouncement::TYPE_AD => '📢 Anuncio',
                        AppAnnouncement::TYPE_INFO => 'ℹ️ Informativo',
                        AppAnnouncement::TYPE_WARNING => '⚠️ Aviso',
                        AppAnnouncement::TYPE_SUCCESS => '✨ Novedad',
                        AppAnnouncement::TYPE_PROMO => '💜 Promo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AppAnnouncement::TYPE_AD => 'warning',
                        AppAnnouncement::TYPE_INFO => 'info',
                        AppAnnouncement::TYPE_WARNING => 'danger',
                        AppAnnouncement::TYPE_SUCCESS => 'success',
                        AppAnnouncement::TYPE_PROMO => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(45)
                    ->tooltip(fn (AppAnnouncement $record): ?string => $record->message)
                    ->placeholder('-'),

                IconColumn::make('hide_for_pro')
                    ->label('Sin PRO')
                    ->boolean()
                    ->alignCenter()
                    ->tooltip('Oculto para usuarios PRO'),

                TextColumn::make('action_text')
                    ->label('Botón')
                    ->placeholder('-')
                    ->description(fn (AppAnnouncement $record): ?string => $record->action_url),

                TextColumn::make('synced_to_firestore_at')
                    ->label('Última Sync')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modificado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado en la App')
                    ->placeholder('Todos')
                    ->trueLabel('Solo el publicado en vivo')
                    ->falseLabel('Borradores / Inactivos'),

                SelectFilter::make('type')
                    ->label('Tipo de banner')
                    ->options([
                        AppAnnouncement::TYPE_AD => '📢 Anuncio Publicitario / Patrocinado',
                        AppAnnouncement::TYPE_INFO => 'ℹ️ Informativo',
                        AppAnnouncement::TYPE_WARNING => '⚠️ Aviso Importante',
                        AppAnnouncement::TYPE_SUCCESS => '✨ Novedad',
                        AppAnnouncement::TYPE_PROMO => '💜 Promoción ScoreBox',
                    ]),

                TernaryFilter::make('hide_for_pro')
                    ->label('Exclusión de usuarios PRO')
                    ->placeholder('Todos')
                    ->trueLabel('Oculto a PRO')
                    ->falseLabel('Visible para todos'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo Aviso / Banner')
                    ->icon('heroicon-o-plus'),

                Action::make('importFromFirestore')
                    ->label('Recuperar actual de Firestore')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function (): void {
                        $imported = AppAnnouncement::importFromFirestore();

                        if ($imported !== null) {
                            Notification::make()
                                ->title('Aviso recuperado con éxito')
                                ->body("Se ha importado '{$imported->title}' desde Cloud Firestore.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin avisos en Firestore')
                                ->body('No hay ningún aviso o banner configurado actualmente en Firestore.')
                                ->info()
                                ->send();
                        }
                    }),

                Action::make('deactivateAll')
                    ->label('Apagar banner en la App')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Apagar el banner en la app ScoreBox?')
                    ->modalDescription('El banner dejará de mostrarse a los usuarios en la app móvil. Puedes volver a activarlo en cualquier momento.')
                    ->action(function (): void {
                        AppAnnouncement::deactivateAllAndSync();

                        Notification::make()
                            ->title('Banner desactivado')
                            ->body('Se ha desactivado la visibilidad del banner en la aplicación móvil.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('activate')
                    ->label('Activar en App')
                    ->icon('heroicon-o-bolt')
                    ->color('success')
                    ->visible(fn (AppAnnouncement $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalHeading('¿Publicar este aviso en la app ScoreBox?')
                    ->modalDescription('Este aviso sustituirá al que esté activo actualmente en la app.')
                    ->action(function (AppAnnouncement $record): void {
                        $success = $record->activateAndSync();

                        if ($success) {
                            Notification::make()
                                ->title('Aviso publicado')
                                ->body("'{$record->title}' ya está visible en la app móvil.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error de sincronización')
                                ->body('No se pudo sincronizar con Firestore.')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('deactivate')
                    ->label('Desactivar')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (AppAnnouncement $record): bool => (bool) $record->is_active)
                    ->action(function (AppAnnouncement $record): void {
                        $record->deactivateAndSync();

                        Notification::make()
                            ->title('Aviso desactivado')
                            ->body('El aviso ya no se muestra en la app móvil.')
                            ->info()
                            ->send();
                    }),

                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (AppAnnouncement $record): void {
                        /** @var AppAnnouncement $clone */
                        $clone = $record->replicate();
                        $clone->title = "{$record->title} (Copia)";
                        $clone->is_active = false;
                        $clone->synced_to_firestore_at = null;
                        $clone->save();

                        Notification::make()
                            ->title('Aviso duplicado')
                            ->body("Se ha creado una copia: {$clone->title}")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make()
                    ->after(function (AppAnnouncement $record): void {
                        if ($record->is_active) {
                            AppAnnouncement::deactivateAllAndSync();
                        }
                    }),
            ])
            ->defaultSort('is_active', 'desc');
    }
}
