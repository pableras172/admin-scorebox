<?php

namespace App\Filament\Resources\ScoreBoxUsers\Schemas;

use App\Models\FirestoreUser;
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
                        TextInput::make('platform')
                            ->label('Plataforma / Sistema Operativo')
                            ->formatStateUsing(function (?string $state): string {
                                $normalized = strtolower(trim((string) $state));

                                return match ($normalized) {
                                    'android' => 'Android',
                                    'ios' => 'iOS',
                                    default => $state ?: 'No especificada',
                                };
                            })
                            ->disabled(),
                        TextInput::make('mainInstrument')
                            ->label('Instrumento principal')
                            ->disabled(),
                        TextInput::make('studyType')
                            ->label('Tipo de estudio')
                            ->disabled(),
                        TextInput::make('subscription')
                            ->label('Suscripción')
                            ->disabled(),
                        TextInput::make('orderId')
                            ->label('ID de Pedido / Transacción (Google Play / App Store)')
                            ->placeholder('Sin compras registradas')
                            ->disabled(),
                        TextInput::make('trial_status')
                            ->label('Estado Periodo de Prueba (7 días)')
                            ->formatStateUsing(function (?string $state, ?FirestoreUser $record): string {
                                if (! $record) {
                                    return '-';
                                }

                                return match ($record->trialStatus ?? null) {
                                    'pro' => 'PRO Vitalicio (Suscripción activa)',
                                    'trial_active' => "En prueba activa (Quedan {$record->trialDaysRemaining} días)",
                                    'trial_expired' => 'Periodo de prueba expirado',
                                    'free' => 'Cuenta gratuita (Sin trial iniciado)',
                                    default => '-',
                                };
                            })
                            ->disabled(),
                        TextInput::make('trial_start_date')
                            ->label('Inicio de Periodo de Prueba')
                            ->formatStateUsing(function (?string $state, ?FirestoreUser $record): string {
                                $ts = $record?->trialStartDate;
                                if (! $ts) {
                                    return 'No iniciado';
                                }

                                return date('d/m/Y H:i', (int) ($ts / 1000));
                            })
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
                Section::make('Copia de Seguridad y Sincronización en la Nube (Multi-Cloud)')
                    ->description('Estado de respaldo automático en Google Drive o iCloud Drive.')
                    ->schema([
                        TextInput::make('cloudProvider')
                            ->label('Proveedor en la Nube')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'google_drive' => 'Google Drive',
                                'icloud_drive' => 'iCloud Drive',
                                default => 'Sin configurar / Desactivado',
                            })
                            ->disabled(),
                        TextInput::make('cloudAccountEmail')
                            ->label('Cuenta Vinculada')
                            ->placeholder('No vinculada')
                            ->disabled(),
                        TextInput::make('lastBackupAt')
                            ->label('Último Respaldo')
                            ->placeholder('Sin copias registradas')
                            ->disabled(),
                    ])
                    ->columns(3),
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
