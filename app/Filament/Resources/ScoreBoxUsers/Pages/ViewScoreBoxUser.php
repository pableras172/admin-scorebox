<?php

namespace App\Filament\Resources\ScoreBoxUsers\Pages;

use App\Filament\Resources\ScoreBoxUsers\Schemas\ScoreBoxUserForm;
use App\Filament\Resources\ScoreBoxUsers\ScoreBoxUserResource;
use App\Models\FirestoreUser;
use App\Services\Firestore\FirestoreUserGateway;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

class ViewScoreBoxUser extends ViewRecord
{
    protected static string $resource = ScoreBoxUserResource::class;

    public function getTitle(): string
    {
        return 'Detalle del usuario Firestore';
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
