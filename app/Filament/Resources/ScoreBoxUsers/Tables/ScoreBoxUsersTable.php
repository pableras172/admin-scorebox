<?php

namespace App\Filament\Resources\ScoreBoxUsers\Tables;

use App\Filament\Resources\ScoreBoxUsers\ScoreBoxUserResource;
use App\Mail\UserSupportMailable;
use App\Models\FirestoreUser;
use App\Models\PromotionalCode;
use App\Services\Firestore\FirestoreUserGateway;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class ScoreBoxUsersTable
{
    public static function normalizeStringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $flattened = collect($value)
                ->flatten()
                ->map(function (mixed $part): string {
                    if ($part === null) {
                        return '';
                    }

                    if (is_scalar($part) || $part instanceof \Stringable) {
                        return (string) $part;
                    }

                    return json_encode($part, JSON_THROW_ON_ERROR) ?: '';
                })
                ->filter(fn (string $part) => $part !== '')
                ->values()
                ->all();

            return $flattened === [] ? null : implode(', ', $flattened);
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR) ?: null;
    }

    public static function filterUsersForSearch(Collection $users, ?string $search): Collection
    {
        $term = trim((string) $search);

        if ($term === '') {
            return $users;
        }

        $normalizedTerm = mb_strtolower($term);

        return $users->filter(function (FirestoreUser $user) use ($normalizedTerm): bool {
            $displayName = mb_strtolower(self::normalizeStringValue($user->displayName ?? null) ?? '');
            $email = mb_strtolower(self::normalizeStringValue($user->email ?? null) ?? '');
            $uid = mb_strtolower(self::normalizeStringValue($user->uid ?? null) ?? '');

            return str_contains($displayName, $normalizedTerm)
                || str_contains($email, $normalizedTerm)
                || str_contains($uid, $normalizedTerm);
        })->values();
    }

    public static function filterUsersForFilters(Collection $users, ?array $filters): Collection
    {
        if (empty($filters)) {
            return $users;
        }

        return $users->filter(function (FirestoreUser $user) use ($filters): bool {
            foreach ($filters as $key => $value) {
                $normalizedValue = self::normalizeStringValue($value);

                if ($value === null || $value === '' || $value === [] || $normalizedValue === null || $normalizedValue === '') {
                    continue;
                }

                if ($key === 'country') {
                    if ((self::normalizeStringValue($user->country ?? null) ?? '') !== $normalizedValue) {
                        return false;
                    }

                    continue;
                }

                if ($key === 'studyType') {
                    if ((self::normalizeStringValue($user->studyType ?? null) ?? '') !== $normalizedValue) {
                        return false;
                    }

                    continue;
                }

                if ($key === 'isPremium') {
                    $expected = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    if ($expected === null) {
                        $expected = (bool) $value;
                    }

                    if ((bool) ($user->isPremium ?? false) !== $expected) {
                        return false;
                    }
                }
            }

            return true;
        })->values();
    }

    public static function configure(Table $table): Table
    {
        $records = function (int|string $page = 1, int|string $recordsPerPage = 50, ?array $filters = null, ?string $search = null): LengthAwarePaginator {
            $page = max(1, (int) $page);
            $isAll = $recordsPerPage === 'all' || (int) $recordsPerPage <= 0;
            $perPage = $isAll ? PHP_INT_MAX : max(1, (int) $recordsPerPage);

            $result = app(FirestoreUserGateway::class)->all();

            if (! $result->isSuccess()) {
                return new LengthAwarePaginator(collect(), 0, $perPage === PHP_INT_MAX ? 50 : $perPage, $page);
            }

            $users = collect($result->data() ?? [])
                ->map(function (array $user, int|string $index): FirestoreUser {
                    $primaryKey = (string) ($user['uid'] ?? $index);
                    $model = new FirestoreUser;
                    $model->forceFill([
                        'id' => $primaryKey,
                        'uid' => $primaryKey,
                        'email' => self::normalizeStringValue($user['email'] ?? null),
                        'displayName' => self::normalizeStringValue($user['displayName'] ?? null),
                        'photoUrl' => self::normalizeStringValue($user['photoUrl'] ?? null),
                        'birthDate' => self::normalizeStringValue($user['birthDate'] ?? null),
                        'city' => self::normalizeStringValue($user['city'] ?? null),
                        'country' => self::normalizeStringValue($user['country'] ?? null),
                        'createdAt' => self::normalizeStringValue($user['createdAt'] ?? null),
                        'updatedAt' => self::normalizeStringValue($user['updatedAt'] ?? null),
                        'isPremium' => (bool) ($user['isPremium'] ?? false),
                        'mainInstrument' => self::normalizeStringValue($user['mainInstrument'] ?? null),
                        'notificationsEnabled' => (bool) ($user['notificationsEnabled'] ?? false),
                        'phone' => self::normalizeStringValue($user['phone'] ?? null),
                        'studyType' => self::normalizeStringValue($user['studyType'] ?? null),
                        'subscription' => self::normalizeStringValue($user['subscription'] ?? $user['subscrition'] ?? null),
                    ]);

                    return $model;
                })
                ->values();

            $users = self::filterUsersForFilters($users, $filters);
            $users = self::filterUsersForSearch($users, $search);

            $total = $users->count();
            $items = $isAll
                ? $users
                : $users->slice(($page - 1) * $perPage, $perPage)->values();

            return new LengthAwarePaginator(
                $items,
                $total,
                $isAll ? max(1, $total) : $perPage,
                $page,
            );
        };

        $countryOptions = function (): array {
            $result = app(FirestoreUserGateway::class)->all();
            if (! $result->isSuccess()) {
                return [];
            }

            return collect($result->data() ?? [])
                ->map(fn (array $user) => self::normalizeStringValue($user['country'] ?? null))
                ->filter(fn (?string $country) => filled($country))
                ->unique()
                ->sort()
                ->mapWithKeys(fn (string $country) => [$country => $country])
                ->all();
        };

        return $table
            ->recordUrl(fn (FirestoreUser $record): string => ScoreBoxUserResource::getUrl('view', ['record' => $record->uid]))
            ->records($records)
            ->columns([
                TextColumn::make('uid')
                    ->label('UID')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('displayName')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('country')
                    ->label('País')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('mainInstrument')
                    ->label('Instrumento principal')
                    ->searchable(),
                TextColumn::make('studyType')
                    ->label('Tipo de estudio')
                    ->searchable(),
                TextColumn::make('subscription')
                    ->label('Suscripción')
                    ->searchable(),
                IconColumn::make('isPremium')
                    ->label('Premium')
                    ->boolean(),
                IconColumn::make('notificationsEnabled')
                    ->label('Notificaciones')
                    ->boolean(),
                TextColumn::make('promo_code')
                    ->label('Código Promo')
                    ->getStateUsing(function (FirestoreUser $record): ?string {
                        if (blank($record->email)) {
                            return null;
                        }

                        return PromotionalCode::where('assigned_email', strtolower($record->email))->value('code');
                    })
                    ->badge()
                    ->color('success')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('birthDate')
                    ->label('Nacimiento')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdAt')
                    ->label('Creado'),
                TextColumn::make('updatedAt')
                    ->label('Actualizado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('country')
                    ->label('País')
                    ->options($countryOptions)
                    ->placeholder('Todos los países'),
                SelectFilter::make('studyType')
                    ->label('Tipo de estudio')
                    ->options(function (): array {
                        $result = app(FirestoreUserGateway::class)->all();
                        if (! $result->isSuccess()) {
                            return [];
                        }

                        return collect($result->data() ?? [])
                            ->map(fn (array $user) => self::normalizeStringValue($user['studyType'] ?? null))
                            ->filter(fn (?string $studyType) => filled($studyType))
                            ->unique()
                            ->sort()
                            ->mapWithKeys(fn (string $studyType) => [$studyType => $studyType])
                            ->all();
                    })
                    ->placeholder('Todos los tipos'),
                TernaryFilter::make('isPremium')
                    ->label('Premium')
                    ->placeholder('Todos')
                    ->trueLabel('Sí')
                    ->falseLabel('No'),
            ])
            ->actions([
                Action::make('promoCode')
                    ->label('Código Promo')
                    ->icon('heroicon-o-ticket')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(function (FirestoreUser $record): string {
                        $promo = filled($record->email) ? PromotionalCode::where('assigned_email', strtolower($record->email))->first() : null;

                        return $promo ? 'Reenviar Código Promocional' : 'Asignar Código Promocional';
                    })
                    ->modalDescription(function (FirestoreUser $record): string {
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
                    ->modalSubmitActionLabel(function (FirestoreUser $record): string {
                        $promo = filled($record->email) ? PromotionalCode::where('assigned_email', strtolower($record->email))->first() : null;

                        return $promo ? 'Sí, Reenviar Código' : 'Sí, Asignar y Enviar Código';
                    })
                    ->action(function (FirestoreUser $record): void {
                        if (blank($record->email)) {
                            Notification::make()
                                ->title('Usuario sin email')
                                ->warning()
                                ->send();

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
                    ->action(function (FirestoreUser $record, array $data): void {
                        if (blank($record->email)) {
                            Notification::make()
                                ->title('Usuario sin email')
                                ->warning()
                                ->send();

                            return;
                        }

                        Mail::to($record->email)->send(new UserSupportMailable(
                            supportSubject: (string) $data['subject'],
                            supportMessage: (string) $data['message'],
                            userName: $record->displayName ?: null,
                        ));

                        Notification::make()
                            ->title('Email enviado')
                            ->body("Mensaje enviado con éxito a {$record->email}.")
                            ->success()
                            ->send();
                    }),

                ViewAction::make()
                    ->url(fn (FirestoreUser $record): string => ScoreBoxUserResource::getUrl('view', ['record' => $record->uid])),
            ])
            ->headerActions([
                Action::make('refreshFromFirestore')
                    ->label('Refrescar de Firestore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (): void {
                        app(FirestoreUserGateway::class)->clearCache();
                        app(FirestoreUserGateway::class)->all([], fresh: true);

                        Notification::make()
                            ->title('Usuarios actualizados')
                            ->body('Se han recargado todos los usuarios desde Cloud Firestore en tiempo real.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No hay usuarios en Firestore')
            ->emptyStateDescription('La colección de usuarios de la aplicación móvil aún no tiene documentos o la conexión falla.')
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }
}
