<?php

namespace App\Filament\Widgets;

use App\Models\FirestoreUser;
use App\Services\Firestore\FirestoreUserGateway;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Pagination\LengthAwarePaginator;

class LastFiveUsers extends TableWidget
{
    protected static ?string $heading = 'Últimos 5 usuarios creados';

    public function table(Table $table): Table
    {
        $records = function (): LengthAwarePaginator {
            $result = app(FirestoreUserGateway::class)->list([], 5);

            if (! $result->isSuccess()) {
                return new LengthAwarePaginator(collect(), 0, 5, 1);
            }

            $items = collect($result->data() ?? [])
                ->take(5)
                ->map(function (array $user): FirestoreUser {
                    $model = new FirestoreUser;
                    $model->forceFill([
                        'uid' => $user['uid'] ?? null,
                        'email' => $user['email'] ?? null,
                        'displayName' => $user['displayName'] ?? null,
                        'isPremium' => (bool) ($user['isPremium'] ?? false),
                        'platform' => $user['platform'] ?? null,
                        'createdAt' => $user['createdAt'] ?? null,
                    ]);

                    return $model;
                })
                ->values();

            return new LengthAwarePaginator($items, $items->count(), 5, 1);
        };

        return $table
            ->records($records)
            ->columns([
                TextColumn::make('displayName')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('platform')
                    ->label('SO')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match (strtolower((string) $state)) {
                        'android' => 'Android',
                        'ios' => 'iOS',
                        default => $state ?: '-',
                    })
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'android' => 'success',
                        'ios' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('isPremium')
                    ->label('Premium')
                    ->boolean(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No hay usuarios recientes')
            ->emptyStateDescription('Todavía no hay usuarios creados en Firestore.');
    }
}
