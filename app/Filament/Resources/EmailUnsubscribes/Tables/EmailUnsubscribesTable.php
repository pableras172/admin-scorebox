<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailUnsubscribes\Tables;

use App\Models\EmailUnsubscribe;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmailUnsubscribesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('source')
                    ->label('Origen')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        EmailUnsubscribe::SOURCE_LINK => 'Enlace de correo',
                        EmailUnsubscribe::SOURCE_MANUAL => 'Panel administrador',
                        EmailUnsubscribe::SOURCE_HEADER => 'Cabecera (RFC 8058)',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        EmailUnsubscribe::SOURCE_LINK => 'info',
                        EmailUnsubscribe::SOURCE_MANUAL => 'warning',
                        EmailUnsubscribe::SOURCE_HEADER => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('unsubscribed_at')
                    ->label('Fecha de baja')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label('Origen de la baja')
                    ->options([
                        EmailUnsubscribe::SOURCE_LINK => 'Enlace de correo',
                        EmailUnsubscribe::SOURCE_MANUAL => 'Panel administrador',
                        EmailUnsubscribe::SOURCE_HEADER => 'Cabecera RFC 8058',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Añadir Baja Manual')
                    ->modalHeading('Registrar Baja Manual')
                    ->form([
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required()
                            ->unique(EmailUnsubscribe::class, 'email'),
                        TextInput::make('reason')
                            ->label('Motivo')
                            ->placeholder('Ej: Solicitud por soporte')
                            ->maxLength(255),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['email'] = strtolower(trim((string) $data['email']));
                        $data['source'] = EmailUnsubscribe::SOURCE_MANUAL;
                        $data['unsubscribed_at'] = now();

                        return $data;
                    }),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Reactivar suscripción')
                    ->modalDescription('Al eliminar este correo de la lista de bajas, el usuario volverá a recibir comunicaciones de marketing.')
                    ->modalSubmitActionLabel('Sí, reactivar correo'),
            ])
            ->defaultSort('id', 'desc');
    }
}

