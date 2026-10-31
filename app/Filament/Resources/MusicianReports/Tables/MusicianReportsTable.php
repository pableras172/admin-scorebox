<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicianReports\Tables;

use App\Models\MusicianReport;
use App\Services\Firestore\FirestoreMusicianReportGateway;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MusicianReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        MusicianReport::STATUS_PENDING => 'Pendiente',
                        MusicianReport::STATUS_REVIEWED => 'En revisión',
                        MusicianReport::STATUS_RESOLVED => 'Resuelta',
                        MusicianReport::STATUS_DISMISSED => 'Descartada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        MusicianReport::STATUS_PENDING => 'danger',
                        MusicianReport::STATUS_REVIEWED => 'info',
                        MusicianReport::STATUS_RESOLVED => 'success',
                        MusicianReport::STATUS_DISMISSED => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('details')
                    ->label('Detalles')
                    ->limit(40)
                    ->tooltip(fn (MusicianReport $record): ?string => $record->details)
                    ->placeholder('-'),

                TextColumn::make('reported_user_id')
                    ->label('Músico Reportado (UID)')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('action_taken')
                    ->label('Acción Aplicada')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        MusicianReport::ACTION_HIDDEN => 'Perfil Ocultado',
                        MusicianReport::ACTION_RESTORED => 'Perfil Restaurado',
                        MusicianReport::ACTION_DISMISSED => 'Desestimado',
                        default => 'Ninguna',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        MusicianReport::ACTION_HIDDEN => 'danger',
                        MusicianReport::ACTION_RESTORED => 'success',
                        MusicianReport::ACTION_DISMISSED => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('reported_at')
                    ->label('Fecha Reporte')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        MusicianReport::STATUS_PENDING => 'Pendiente (Sin revisar)',
                        MusicianReport::STATUS_REVIEWED => 'En revisión',
                        MusicianReport::STATUS_RESOLVED => 'Resuelta',
                        MusicianReport::STATUS_DISMISSED => 'Descartada',
                    ]),

                SelectFilter::make('action_taken')
                    ->label('Acción de Moderación')
                    ->options([
                        MusicianReport::ACTION_HIDDEN => 'Perfil Ocultado',
                        MusicianReport::ACTION_RESTORED => 'Perfil Restaurado',
                        MusicianReport::ACTION_DISMISSED => 'Desestimado',
                    ]),
            ])
            ->headerActions([
                Action::make('syncFirestore')
                    ->label('Sincronizar desde Firestore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (): void {
                        $gateway = app(FirestoreMusicianReportGateway::class);
                        $count = $gateway->syncFromFirestore();

                        if ($count > 0) {
                            Notification::make()
                                ->title('Sincronización completada')
                                ->body("Se han importado {$count} nuevas denuncias desde Firestore.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin nuevas denuncias')
                                ->body('No hay denuncias pendientes de importar en Firestore.')
                                ->info()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('hideMusician')
                    ->label('Ocultar Músico')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ocultar perfil del músico denunciado')
                    ->modalDescription('Esta acción pondrá "isVisible: false" en el documento del directorio de Firestore, ocultando inmediatamente la ficha pública en la aplicación móvil.')
                    ->action(function (MusicianReport $record): void {
                        $gateway = app(FirestoreMusicianReportGateway::class);
                        $success = $gateway->hideReportedMusician($record->reported_user_id);

                        if ($success) {
                            $record->update([
                                'action_taken' => MusicianReport::ACTION_HIDDEN,
                                'status' => MusicianReport::STATUS_RESOLVED,
                                'admin_notes' => trim(($record->admin_notes ?? '')."\n[".now()->format('d/m/Y H:i').'] Perfil ocultado en Firestore.'),
                            ]);

                            Notification::make()
                                ->title('Perfil ocultado con éxito')
                                ->body("El músico {$record->reported_user_id} ha sido ocultado del directorio público.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error al conectar con Firestore')
                                ->body('No se pudo actualizar el estado de visibilidad del perfil.')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('restoreMusician')
                    ->label('Restaurar')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restaurar visibilidad del músico')
                    ->modalDescription('Esta acción reactivará "isVisible: true" en el directorio de Firestore, volviendo a mostrar el perfil en la app.')
                    ->action(function (MusicianReport $record): void {
                        $gateway = app(FirestoreMusicianReportGateway::class);
                        $success = $gateway->restoreReportedMusician($record->reported_user_id);

                        if ($success) {
                            $record->update([
                                'action_taken' => MusicianReport::ACTION_RESTORED,
                                'status' => MusicianReport::STATUS_RESOLVED,
                                'admin_notes' => trim(($record->admin_notes ?? '')."\n[".now()->format('d/m/Y H:i').'] Visibilidad restaurada en Firestore.'),
                            ]);

                            Notification::make()
                                ->title('Perfil restaurado')
                                ->body("El músico {$record->reported_user_id} vuelve a ser visible en el directorio.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->body('No se pudo restaurar la visibilidad en Firestore.')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('changeStatus')
                    ->label('Estado / Notas')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label('Nuevo Estado')
                            ->options([
                                MusicianReport::STATUS_PENDING => 'Pendiente',
                                MusicianReport::STATUS_REVIEWED => 'En revisión',
                                MusicianReport::STATUS_RESOLVED => 'Resuelta',
                                MusicianReport::STATUS_DISMISSED => 'Descartada',
                            ])
                            ->default(fn (MusicianReport $record): string => $record->status)
                            ->required()
                            ->native(false),

                        Textarea::make('admin_notes')
                            ->label('Notas internas del administrador')
                            ->default(fn (MusicianReport $record): ?string => $record->admin_notes)
                            ->rows(3),
                    ])
                    ->action(function (MusicianReport $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);

                        Notification::make()
                            ->title('Denuncia actualizada')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
