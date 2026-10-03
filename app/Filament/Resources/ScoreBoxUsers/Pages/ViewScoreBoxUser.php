<?php

namespace App\Filament\Resources\ScoreBoxUsers\Pages;

use App\Filament\Resources\ScoreBoxUsers\Schemas\ScoreBoxUserForm;
use App\Filament\Resources\ScoreBoxUsers\ScoreBoxUserResource;
use App\Mail\UserSupportMailable;
use App\Models\FirestoreUser;
use App\Models\PromotionalCode;
use App\Services\Firestore\FirestoreUserGateway;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ViewScoreBoxUser extends ViewRecord
{
    protected static string $resource = ScoreBoxUserResource::class;

    public function getTitle(): string
    {
        return 'Detalle del usuario Firestore';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('promoCode')
                ->label('Código Promo')
                ->icon('heroicon-o-ticket')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(function (): string {
                    /** @var FirestoreUser $record */
                    $record = $this->getRecord();
                    $promo = filled($record->email) ? PromotionalCode::where('assigned_email', strtolower($record->email))->first() : null;

                    return $promo ? 'Reenviar Código Promocional' : 'Asignar Código Promocional';
                })
                ->modalDescription(function (): string {
                    /** @var FirestoreUser $record */
                    $record = $this->getRecord();
                    if (blank($record->email)) {
                        return 'El usuario no tiene dirección de correo electrónico registrada.';
                    }

                    $promo = PromotionalCode::where('assigned_email', strtolower($record->email))->first();
                    if ($promo) {
                        $assignedDate = $promo->assigned_at ? $promo->assigned_at->format('d/m/Y H:i') : 'desconocida';

                        return "El usuario {$record->email} ya tiene asignado el código: {$promo->code} (asignado el {$assignedDate}). ¿Deseas reenviárselo por correo?";
                    }

                    $availableCount = PromotionalCode::available()->count();
                    if ($availableCount === 0) {
                        return "El usuario {$record->email} no tiene ningún código asignado, pero NO hay códigos disponibles en el inventario.";
                    }

                    return "El usuario {$record->email} no tiene ningún código asignado. Hay {$availableCount} códigos disponibles en stock. ¿Deseas asignarle uno y enviárselo por correo ahora?";
                })
                ->modalSubmitActionLabel(function (): string {
                    /** @var FirestoreUser $record */
                    $record = $this->getRecord();
                    $promo = filled($record->email) ? PromotionalCode::where('assigned_email', strtolower($record->email))->first() : null;

                    return $promo ? 'Sí, Reenviar Código' : 'Sí, Asignar y Enviar Código';
                })
                ->action(function (): void {
                    /** @var FirestoreUser $record */
                    $record = $this->getRecord();
                    if (blank($record->email)) {
                        Notification::make()->title('Usuario sin email')->warning()->send();

                        return;
                    }

                    $promo = PromotionalCode::where('assigned_email', strtolower($record->email))->first();

                    if (! $promo) {
                        $promo = PromotionalCode::available()->first();

                        if (! $promo) {
                            Notification::make()
                                ->title('Inventario agotado')
                                ->body('No hay códigos promocionales disponibles para asignar.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $promo->update([
                            'assigned_email' => strtolower($record->email),
                            'assigned_at' => now(),
                        ]);
                    }

                    Mail::to($record->email)->send(new UserSupportMailable(
                        supportSubject: 'Tu código promocional de ScoreBox',
                        supportMessage: 'Aquí tienes tu código promocional para disfrutar de la suscripción PRO en ScoreBox.',
                        userName: $record->displayName ?: 'músico',
                        promoCode: $promo->code,
                        recipientEmail: $record->email,
                    ));

                    Notification::make()
                        ->title('Código enviado correctamente')
                        ->body("Se ha enviado el código {$promo->code} a {$record->email}.")
                        ->success()
                        ->send();
                }),

            Action::make('sendDirectEmail')
                ->label('Enviar Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->form([
                    TextInput::make('subject')
                        ->label('Asunto')
                        ->default('Información sobre tu cuenta en ScoreBox')
                        ->required(),
                    Textarea::make('message')
                        ->label('Mensaje')
                        ->placeholder('Escribe tu mensaje para el usuario...')
                        ->rows(6)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var FirestoreUser $record */
                    $record = $this->getRecord();
                    if (blank($record->email)) {
                        Notification::make()->title('Usuario sin email')->warning()->send();

                        return;
                    }

                    Mail::to($record->email)->send(new UserSupportMailable(
                        supportSubject: (string) $data['subject'],
                        supportMessage: (string) $data['message'],
                        userName: $record->displayName ?: null,
                        recipientEmail: $record->email,
                    ));

                    Notification::make()
                        ->title('Email enviado')
                        ->body("Mensaje enviado con éxito a {$record->email}.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function resolveRecord(int|string $record): FirestoreUser
    {
        $uid = (string) $record;

        Log::info('ScoreBoxUser detail resolve started.', [
            'requested_uid' => $uid,
        ]);

        $result = app(FirestoreUserGateway::class)->getById($uid);
        $user = is_array($result->data()) ? $result->data() : null;

        Log::info('ScoreBoxUser detail direct lookup result.', [
            'requested_uid' => $uid,
            'success' => $result->isSuccess(),
            'error' => $result->error(),
            'data' => $user,
        ]);

        if (is_array($user)) {
            $model = new FirestoreUser([
                'uid' => $user['uid'] ?? $uid,
                'email' => $user['email'] ?? null,
                'displayName' => $user['displayName'] ?? null,
                'phone' => $user['phone'] ?? null,
                'country' => $user['country'] ?? null,
                'city' => $user['city'] ?? null,
                'birthDate' => $user['birthDate'] ?? null,
                'mainInstrument' => $user['mainInstrument'] ?? null,
                'studyType' => $user['studyType'] ?? null,
                'subscription' => $user['subscription'] ?? $user['subscrition'] ?? null,
                'isPremium' => (bool) ($user['isPremium'] ?? false),
                'notificationsEnabled' => (bool) ($user['notificationsEnabled'] ?? false),
                'createdAt' => $user['createdAt'] ?? null,
                'updatedAt' => $user['updatedAt'] ?? null,
            ]);

            Log::info('ScoreBoxUser detail match found by id.', [
                'requested_uid' => $uid,
                'matched_uid' => $model->uid,
                'model_data' => $model->toArray(),
            ]);

            return $model;
        }

        Log::warning('ScoreBoxUser detail match not found by id.', [
            'requested_uid' => $uid,
            'error' => $result->error(),
        ]);

        return new FirestoreUser([
            'uid' => (string) $record,
            'displayName' => 'Usuario no encontrado',
            'email' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $record = $this->getRecord();

        Log::info('ScoreBoxUser detail form schema build.', [
            'record_class' => $record::class,
            'record_data' => $record->toArray(),
        ]);

        return ScoreBoxUserForm::configure(
            $schema
                ->record($record)
                ->statePath('data')
                ->disabled(),
        );
    }
}
