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
                IconColumn::make('isPremium')
                    ->label('Premium')
                    ->boolean(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No hay usuarios recientes')
            ->emptyStateDescription('Todavía no hay usuarios creados en Firestore.');
    }
}
