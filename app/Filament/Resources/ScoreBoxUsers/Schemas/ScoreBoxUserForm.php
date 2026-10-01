<?php

namespace App\Filament\Resources\ScoreBoxUsers\Schemas;

use App\Models\PromotionalCode;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScoreBoxUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información personal')
                    ->schema([
                        TextInput::make('uid')
                            ->label('UID')
                            ->disabled(),
                        TextInput::make('displayName')
                            ->label('Nombre')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->disabled(),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->disabled(),
                        TextInput::make('country')
                            ->label('País')
                            ->disabled(),
                        TextInput::make('city')
                            ->label('Ciudad')
                            ->disabled(),
                        TextInput::make('birthDate')
                            ->label('Fecha de nacimiento')
                            ->disabled(),
                    ]),
                Section::make('Estudio y suscripción')
                    ->schema([
                        TextInput::make('mainInstrument')
                            ->label('Instrumento principal')
                            ->disabled(),
                        TextInput::make('studyType')
                            ->label('Tipo de estudio')
                            ->disabled(),
                        TextInput::make('subscription')
                            ->label('Suscripción')
                            ->disabled(),
                        Checkbox::make('isPremium')
                            ->label('Premium')
                            ->disabled(),
                        Checkbox::make('notificationsEnabled')
                            ->label('Notificaciones')
                            ->disabled(),
                    ]),
                Section::make('Soporte y Código Promocional')
                    ->schema([
                        TextInput::make('assigned_promo_code')
                            ->label('Código Promocional asignado')
                            ->formatStateUsing(function (?FirestoreUser $record): string {
                                if (! $record || blank($record->email)) {
                                    return 'Sin email registrado';
                                }

                                $promo = PromotionalCode::where('assigned_email', strtolower($record->email))->first();

                                return $promo
                                    ? "{$promo->code} (Asignado el ".($promo->assigned_at ? $promo->assigned_at->format('d/m/Y H:i') : 'fecha desconocida').')'
                                    : 'Ningún código asignado';
                            })
                            ->disabled(),
                    ]),
                Section::make('Auditoría')
                    ->schema([
                        TextInput::make('createdAt')
                            ->label('Creado')
                            ->disabled(),
                        TextInput::make('updatedAt')
                            ->label('Actualizado')
                            ->disabled(),
                    ]),
            ]);
    }
}
