<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusiciansDirectory\Pages;

use App\Filament\Resources\MusiciansDirectory\MusiciansDirectoryResource;
use App\Filament\Resources\MusiciansDirectory\Schemas\MusicianProfileForm;
use App\Models\FirestoreMusicianProfile;
use App\Services\Firestore\FirestoreMusiciansDirectoryGateway;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewMusicianProfile extends ViewRecord
{
    protected static string $resource = MusiciansDirectoryResource::class;

    public function getTitle(): string
    {
        return 'Detalle del Músico en Directorio';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleVisibility')
                ->label(fn (): string => $this->getRecord()->isVisible ? 'Ocultar del Directorio' : 'Hacer Visible')
                ->icon(fn (): string => $this->getRecord()->isVisible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color(fn (): string => $this->getRecord()->isVisible ? 'danger' : 'success')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var FirestoreMusicianProfile $record */
                    $record = $this->getRecord();
                    $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                    $newVisibility = ! $record->isVisible;
                    $res = $gateway->toggleVisibility($record->userId, $newVisibility);

                    if ($res->isSuccess()) {
                        $record->isVisible = $newVisibility;
                        Notification::make()
                            ->title('Visibilidad actualizada con éxito')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error')
                            ->body($res->message())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('toggleVerified')
                ->label(fn (): string => $this->getRecord()->isVerified ? 'Quitar Verificación' : 'Verificar Músico')
                ->icon('heroicon-o-check-badge')
                ->color(fn (): string => $this->getRecord()->isVerified ? 'gray' : 'primary')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var FirestoreMusicianProfile $record */
                    $record = $this->getRecord();
                    $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                    $newVerified = ! $record->isVerified;
                    $res = $gateway->toggleVerified($record->userId, $newVerified);

                    if ($res->isSuccess()) {
                        $record->isVerified = $newVerified;
                        Notification::make()
                            ->title('Verificación actualizada')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    protected function resolveRecord(int|string $record): FirestoreMusicianProfile
    {
        $userId = (string) $record;
        $gateway = app(FirestoreMusiciansDirectoryGateway::class);
        $result = $gateway->getById($userId);

        if ($result->isSuccess() && is_array($result->data())) {
            $data = $result->data();

            return new FirestoreMusicianProfile($data);
        }

        return new FirestoreMusicianProfile([
            'userId' => $userId,
            'displayName' => 'Músico no encontrado',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return MusicianProfileForm::configure(
            $schema
                ->record($this->getRecord())
                ->statePath('data')
                ->disabled(),
        );
    }
}
