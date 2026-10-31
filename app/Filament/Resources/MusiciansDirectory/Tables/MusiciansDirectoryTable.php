<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusiciansDirectory\Tables;

use App\Filament\Resources\MusiciansDirectory\MusiciansDirectoryResource;
use App\Models\FirestoreMusicianProfile;
use App\Services\Firestore\FirestoreMusiciansDirectoryGateway;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MusiciansDirectoryTable
{
    public static function configure(Table $table): Table
    {
        $records = function (int|string $page = 1, int|string $recordsPerPage = 50, ?array $filters = null, ?string $search = null): LengthAwarePaginator {
            $page = max(1, (int) $page);
            $isAll = $recordsPerPage === 'all' || (int) $recordsPerPage <= 0;
            $perPage = $isAll ? PHP_INT_MAX : max(1, (int) $recordsPerPage);

            $gateway = app(FirestoreMusiciansDirectoryGateway::class);
            $result = $gateway->all();

            if (! $result->isSuccess()) {
                return new LengthAwarePaginator(collect(), 0, $perPage === PHP_INT_MAX ? 50 : $perPage, $page);
            }

            /** @var Collection<int, FirestoreMusicianProfile> $profiles */
            $profiles = collect($result->data() ?? [])
                ->map(function (array $data): FirestoreMusicianProfile {
                    $model = new FirestoreMusicianProfile;
                    $model->forceFill([
                        'id' => $data['userId'],
                        'userId' => $data['userId'],
                        'displayName' => $data['displayName'] ?? 'Sin nombre',
                        'photoUrl' => $data['photoUrl'] ?? null,
                        'primaryInstrumentId' => $data['primaryInstrumentId'] ?? null,
                        'primaryInstrumentName' => $data['primaryInstrumentName'] ?? null,
                        'secondaryInstruments' => $data['secondaryInstruments'] ?? [],
                        'city' => $data['city'] ?? null,
                        'countryCode' => $data['countryCode'] ?? null,
                        'countryName' => $data['countryName'] ?? null,
                        'roles' => $data['roles'] ?? [],
                        'musicalStyles' => $data['musicalStyles'] ?? [],
                        'bio' => $data['bio'] ?? null,
                        'instagramHandle' => $data['instagramHandle'] ?? null,
                        'whatsappPhone' => $data['whatsappPhone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'websiteUrl' => $data['websiteUrl'] ?? null,
                        'isPro' => (bool) ($data['isPro'] ?? false),
                        'isFeatured' => (bool) ($data['isFeatured'] ?? false),
                        'isVerified' => (bool) ($data['isVerified'] ?? false),
                        'isVisible' => (bool) ($data['isVisible'] ?? false),
                        'createdAt' => $data['createdAt'] ?? null,
                        'updatedAt' => $data['updatedAt'] ?? null,
                    ]);

                    return $model;
                })
                ->values();

            // Apply Filters
            if (is_array($filters)) {
                $profiles = $profiles->filter(function (FirestoreMusicianProfile $profile) use ($filters): bool {
                    foreach ($filters as $key => $filter) {
                        $value = is_array($filter) ? ($filter['value'] ?? null) : $filter;
                        if ($value === null || $value === '') {
                            continue;
                        }

                        if ($key === 'isVisible') {
                            $expected = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
                            if ((bool) $profile->isVisible !== $expected) {
                                return false;
                            }
                        }

                        if ($key === 'isVerified') {
                            $expected = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
                            if ((bool) $profile->isVerified !== $expected) {
                                return false;
                            }
                        }

                        if ($key === 'isFeatured') {
                            $expected = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
                            if ((bool) $profile->isFeatured !== $expected) {
                                return false;
                            }
                        }

                        if ($key === 'isPro') {
                            $expected = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
                            if ((bool) $profile->isPro !== $expected) {
                                return false;
                            }
                        }

                        if ($key === 'countryName') {
                            if (mb_strtolower((string) $profile->countryName) !== mb_strtolower((string) $value)) {
                                return false;
                            }
                        }
                    }

                    return true;
                })->values();
            }

            // Apply Search
            $term = trim((string) $search);
            if ($term !== '') {
                $term = mb_strtolower($term);
                $profiles = $profiles->filter(function (FirestoreMusicianProfile $p) use ($term): bool {
                    return str_contains(mb_strtolower((string) $p->displayName), $term)
                        || str_contains(mb_strtolower((string) $p->userId), $term)
                        || str_contains(mb_strtolower((string) $p->primaryInstrumentName), $term)
                        || str_contains(mb_strtolower((string) $p->city), $term)
                        || str_contains(mb_strtolower((string) $p->countryName), $term)
                        || str_contains(mb_strtolower((string) $p->email), $term);
                })->values();
            }

            $total = $profiles->count();
            $items = $isAll
                ? $profiles
                : $profiles->slice(($page - 1) * $perPage, $perPage)->values();

            return new LengthAwarePaginator(
                $items,
                $total,
                $isAll ? max(1, $total) : $perPage,
                $page,
            );
        };

        return $table
            ->recordUrl(fn (FirestoreMusicianProfile $record): string => MusiciansDirectoryResource::getUrl('view', ['record' => $record->userId]))
            ->records($records)
            ->columns([
                ImageColumn::make('photoUrl')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=M&background=0D8ABC&color=fff'),

                TextColumn::make('displayName')
                    ->label('Músico')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('primaryInstrumentName')
                    ->label('Instrumento')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                TextColumn::make('location')
                    ->label('Ubicación')
                    ->state(function (FirestoreMusicianProfile $record): string {
                        $parts = array_filter([$record->city, $record->countryName]);

                        return empty($parts) ? '-' : implode(', ', $parts);
                    }),

                TextColumn::make('roles')
                    ->label('Roles / Disponibilidad')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn ($state): string => match ($state) {
                        'gigs_available' => 'Bolos / Conciertos',
                        'teacher' => 'Profesor',
                        'arranger' => 'Arreglista',
                        'seeking_band' => 'Busca banda',
                        'student' => 'Estudiante',
                        default => is_string($state) ? $state : '',
                    })
                    ->color(fn ($state): string => match ($state) {
                        'gigs_available' => 'success',
                        'teacher' => 'warning',
                        'arranger' => 'info',
                        default => 'gray',
                    }),

                IconColumn::make('isPro')
                    ->label('PRO')
                    ->boolean(),

                IconColumn::make('isVerified')
                    ->label('Verificado')
                    ->boolean(),

                IconColumn::make('isFeatured')
                    ->label('Destacado')
                    ->boolean(),

                IconColumn::make('isVisible')
                    ->label('Visible')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('isVisible')
                    ->label('Visibilidad en App')
                    ->trueLabel('Solo perfiles visibles')
                    ->falseLabel('Solo perfiles ocultos'),

                TernaryFilter::make('isVerified')
                    ->label('Verificación')
                    ->trueLabel('Solo verificados')
                    ->falseLabel('Sin verificar'),

                TernaryFilter::make('isFeatured')
                    ->label('Destacado')
                    ->trueLabel('Solo destacados')
                    ->falseLabel('No destacados'),

                TernaryFilter::make('isPro')
                    ->label('PRO')
                    ->trueLabel('Solo PRO')
                    ->falseLabel('Solo Free'),

                SelectFilter::make('countryName')
                    ->label('País')
                    ->options(function (): array {
                        $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                        $res = $gateway->all();
                        if (! $res->isSuccess()) {
                            return [];
                        }

                        return collect($res->data() ?? [])
                            ->pluck('countryName')
                            ->filter()
                            ->unique()
                            ->sort()
                            ->mapWithKeys(fn (string $c) => [$c => $c])
                            ->all();
                    }),
            ])
            ->headerActions([
                Action::make('refreshCache')
                    ->label('Refrescar Firestore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->action(function (): void {
                        $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                        $gateway->clearCache();

                        Notification::make()
                            ->title('Caché del directorio limpiada')
                            ->body('Se han recargado los perfiles más recientes desde Firestore.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('toggleVisibility')
                    ->label(fn (FirestoreMusicianProfile $record): string => $record->isVisible ? 'Ocultar' : 'Hacer Visible')
                    ->icon(fn (FirestoreMusicianProfile $record): string => $record->isVisible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (FirestoreMusicianProfile $record): string => $record->isVisible ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (FirestoreMusicianProfile $record): string => $record->isVisible ? '¿Ocultar perfil del directorio?' : '¿Mostrar perfil en el directorio?')
                    ->modalDescription(fn (FirestoreMusicianProfile $record): string => $record->isVisible
                        ? "El perfil de {$record->displayName} dejará de ser visible públicamente en la app móvil."
                        : "El perfil de {$record->displayName} volverá a ser visible públicamente en la app móvil.")
                    ->action(function (FirestoreMusicianProfile $record): void {
                        $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                        $res = $gateway->toggleVisibility($record->userId, ! $record->isVisible);

                        if ($res->isSuccess()) {
                            Notification::make()
                                ->title('Visibilidad actualizada')
                                ->body("El estado de visibilidad de {$record->displayName} ha sido modificado.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error al actualizar')
                                ->body($res->message())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('toggleVerified')
                    ->label(fn (FirestoreMusicianProfile $record): string => $record->isVerified ? 'Quitar Verificación' : 'Verificar Perfil')
                    ->icon('heroicon-o-check-badge')
                    ->color(fn (FirestoreMusicianProfile $record): string => $record->isVerified ? 'gray' : 'primary')
                    ->requiresConfirmation()
                    ->modalHeading(fn (FirestoreMusicianProfile $record): string => $record->isVerified ? '¿Quitar verificación oficial?' : '¿Verificar músico oficialmente?')
                    ->action(function (FirestoreMusicianProfile $record): void {
                        $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                        $res = $gateway->toggleVerified($record->userId, ! $record->isVerified);

                        if ($res->isSuccess()) {
                            Notification::make()
                                ->title('Verificación actualizada')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error al verificar')
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('toggleFeatured')
                    ->label(fn (FirestoreMusicianProfile $record): string => $record->isFeatured ? 'Quitar Destacado' : 'Destacar Músico')
                    ->icon('heroicon-o-star')
                    ->color(fn (FirestoreMusicianProfile $record): string => $record->isFeatured ? 'gray' : 'warning')
                    ->action(function (FirestoreMusicianProfile $record): void {
                        $gateway = app(FirestoreMusiciansDirectoryGateway::class);
                        $res = $gateway->toggleFeatured($record->userId, ! $record->isFeatured);

                        if ($res->isSuccess()) {
                            Notification::make()
                                ->title('Estado destacado actualizado')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->send();
                        }
                    }),

                ViewAction::make(),
            ]);
    }
}
