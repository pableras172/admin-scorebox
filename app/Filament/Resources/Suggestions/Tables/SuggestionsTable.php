<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suggestions\Tables;

use App\Mail\UserSupportMailable;
use App\Models\PromotionalCode;
use App\Models\Suggestion;
use App\Services\Firestore\FirestoreSuggestionGateway;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class SuggestionsTable
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
                        Suggestion::STATUS_NEW => 'Nueva',
                        Suggestion::STATUS_IN_REVIEW => 'En revisión',
                        Suggestion::STATUS_PLANNED => 'Aceptada',
                        Suggestion::STATUS_COMPLETED => 'Implementada',
                        Suggestion::STATUS_DISMISSED => 'Descartada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Suggestion::STATUS_NEW => 'danger',
                        Suggestion::STATUS_IN_REVIEW => 'info',
                        Suggestion::STATUS_PLANNED => 'primary',
                        Suggestion::STATUS_COMPLETED => 'success',
                        Suggestion::STATUS_DISMISSED => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Suggestion::TYPE_IDEA => '💡 Idea',
                        Suggestion::TYPE_BUG => '🐛 Error',
                        Suggestion::TYPE_SCORES_REQUEST => '🎼 Partituras',
                        Suggestion::TYPE_USABILITY => '⚡ Usabilidad',
                        Suggestion::TYPE_OTHER => '💬 Otro',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Suggestion::TYPE_BUG => 'danger',
                        Suggestion::TYPE_IDEA => 'primary',
                        Suggestion::TYPE_SCORES_REQUEST => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('source')
                    ->label('Canal')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Suggestion::SOURCE_APP => 'App Android',
                        Suggestion::SOURCE_WEB => 'Web',
                        Suggestion::SOURCE_ADMIN => 'Admin',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Suggestion::SOURCE_APP => 'primary',
                        Suggestion::SOURCE_WEB => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                IconColumn::make('is_premium')
                    ->label('PRO')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(25)
                    ->placeholder('-'),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(40)
                    ->tooltip(fn (Suggestion $record): string => $record->message),

                TextColumn::make('replied_at')
                    ->label('Respondida')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Recibida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Suggestion::STATUS_NEW => 'Nueva (Sin revisar)',
                        Suggestion::STATUS_IN_REVIEW => 'En revisión',
                        Suggestion::STATUS_PLANNED => 'Aceptada / Planificada',
                        Suggestion::STATUS_COMPLETED => 'Implementada',
                        Suggestion::STATUS_DISMISSED => 'Descartada',
                    ]),

                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        Suggestion::TYPE_IDEA => 'Idea / Nueva función',
                        Suggestion::TYPE_BUG => 'Reporte de error / Bug',
                        Suggestion::TYPE_SCORES_REQUEST => 'Petición de partituras',
                        Suggestion::TYPE_USABILITY => 'Rendimiento / Usabilidad',
                        Suggestion::TYPE_OTHER => 'Otro comentario',
                    ]),

                SelectFilter::make('source')
                    ->label('Canal')
                    ->options([
                        Suggestion::SOURCE_APP => 'App Móvil (Android)',
                        Suggestion::SOURCE_WEB => 'Formulario Web',
                        Suggestion::SOURCE_ADMIN => 'Panel Admin',
                    ]),

                TernaryFilter::make('is_premium')
                    ->label('Usuario PRO')
                    ->placeholder('Todos')
                    ->trueLabel('Solo usuarios PRO')
                    ->falseLabel('Solo usuarios Free'),
            ])
            ->headerActions([
                Action::make('syncFirestore')
                    ->label('Sincronizar desde Firestore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (): void {
                        $gateway = app(FirestoreSuggestionGateway::class);
                        $count = $gateway->syncFromFirestore();

                        if ($count > 0) {
                            Notification::make()
                                ->title('Sincronización completada')
                                ->body("Se han importado {$count} nuevas sugerencias desde Firestore.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin nuevas sugerencias')
                                ->body('No hay sugerencias nuevas pendientes de importar en Firestore.')
                                ->info()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Action::make('changeStatus')
                    ->label('Estado')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label('Nuevo Estado')
                            ->options([
                                Suggestion::STATUS_NEW => 'Nueva',
                                Suggestion::STATUS_IN_REVIEW => 'En revisión',
                                Suggestion::STATUS_PLANNED => 'Aceptada / Planificada',
                                Suggestion::STATUS_COMPLETED => 'Implementada',
                                Suggestion::STATUS_DISMISSED => 'Descartada',
                            ])
                            ->default(fn (Suggestion $record): string => $record->status)
                            ->required()
                            ->native(false),

                        Textarea::make('admin_notes')
                            ->label('Notas internas del administrador')
                            ->default(fn (Suggestion $record): ?string => $record->admin_notes)
                            ->rows(3),
                    ])
                    ->action(function (Suggestion $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
                        ]);

                        Notification::make()
                            ->title('Estado actualizado')
                            ->success()
                            ->send();
                    }),

                Action::make('replyEmail')
                    ->label('Responder')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->form([
                        TextInput::make('recipient')
                            ->label('Destinatario')
                            ->default(fn (Suggestion $record): string => $record->email)
                            ->disabled(),

                        TextInput::make('subject')
                            ->label('Asunto del correo')
                            ->default(fn (Suggestion $record): string => $record->subject ? "Re: {$record->subject}" : 'Sobre tu sugerencia en ScoreBox')
                            ->required(),

                        Textarea::make('message')
                            ->label('Mensaje de respuesta')
                            ->placeholder('Escribe tu respuesta personalizada para el usuario...')
                            ->rows(6)
                            ->required(),

                        Checkbox::make('gift_promo_code')
                            ->label('Regalar un Código Promocional PRO de Google Play')
                            ->helperText('Asigna automáticamente un código libre del stock y lo adjunta con instrucciones de canje en el correo.')
                            ->default(false),
                    ])
                    ->action(function (Suggestion $record, array $data): void {
                        $promoCode = null;

                        if (! empty($data['gift_promo_code'])) {
                            $promo = PromotionalCode::available()->first();
                            if ($promo) {
                                $promo->update([
                                    'assigned_email' => strtolower($record->email),
                                    'assigned_at' => now(),
                                ]);
                                $promoCode = $promo->code;
                            } else {
                                Notification::make()
                                    ->title('Stock agotado')
                                    ->body('No hay códigos libres para regalar, pero el correo se enviará igualmente.')
                                    ->warning()
                                    ->send();
                            }
                        }

                        Mail::to($record->email)->send(new UserSupportMailable(
                            supportSubject: (string) $data['subject'],
                            supportMessage: (string) $data['message'],
                            userName: $record->name ?: 'músico',
                            promoCode: $promoCode,
                            recipientEmail: $record->email,
                        ));

                        $record->update([
                            'replied_at' => now(),
                            'status' => $record->status === Suggestion::STATUS_NEW ? Suggestion::STATUS_IN_REVIEW : $record->status,
                        ]);

                        $notificationBody = $promoCode
                            ? "Respuesta enviada a {$record->email} con el código regalo {$promoCode}."
                            : "Respuesta enviada con éxito a {$record->email}.";

                        Notification::make()
                            ->title('Respuesta enviada')
                            ->body($notificationBody)
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
