<?php

namespace App\Filament\Resources\ScoreBoxUsers\Tables;

use App\Filament\Resources\ScoreBoxUsers\ScoreBoxUserResource;
use App\Models\FirestoreUser;
use App\Services\Firestore\FirestoreUserGateway;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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
        $records = function (int|string $page = 1, int|string $recordsPerPage = 10, ?array $filters = null, ?string $search = null): LengthAwarePaginator {
            $page = max(1, (int) $page);
            $recordsPerPage = is_numeric($recordsPerPage) ? (int) $recordsPerPage : 10;
            $recordsPerPage = max(1, $recordsPerPage);

            $result = app(FirestoreUserGateway::class)->list([], 100);

            if (! $result->isSuccess()) {
                return new LengthAwarePaginator(collect(), 0, $recordsPerPage, $page);
            }

            $users = collect($result->data() ?? [])
                ->map(function (array $user, int|string $index): FirestoreUser {
                    $primaryKey = (string) ($user['uid'] ?? $index);
                    $model = new FirestoreUser();
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
            $items = $users
                ->slice(($page - 1) * $recordsPerPage, $recordsPerPage)
                ->values();

            return new LengthAwarePaginator(
                $items,
                $total,
                $recordsPerPage,
                $page,
            );
        };

        $countryOptions = function () use ($records): array {
            return collect($records()->items())
                ->map(fn (FirestoreUser $user) => self::normalizeStringValue($user->country ?? null))
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
                TextColumn::make('birthDate')
                    ->label('Nacimiento'),
                TextColumn::make('createdAt')
                    ->label('Creado'),
                TextColumn::make('updatedAt')
                    ->label('Actualizado'),
            ])
            ->filters([
                SelectFilter::make('country')
                    ->label('País')
                    ->options($countryOptions)
                    ->placeholder('Todos los países'),
                SelectFilter::make('studyType')
                    ->label('Tipo de estudio')
                    ->options(function () use ($records): array {
                        return collect($records()->items())
                            ->map(fn (FirestoreUser $user) => self::normalizeStringValue($user->studyType ?? null))
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
            ->emptyStateHeading('No hay usuarios en Firestore')
            ->emptyStateDescription('La colección de usuarios de la aplicación móvil aún no tiene documentos o la conexión falla.')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
