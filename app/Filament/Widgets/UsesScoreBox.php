<?php

namespace App\Filament\Widgets;

use App\Services\Firestore\FirestoreUserGateway;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class UsesScoreBox extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public function getStats(): array
    {
        try {
            $result = app(FirestoreUserGateway::class)->countAll();
            $count = is_numeric($result->data()) ? (int) $result->data() : 0;

            return [
                Stat::make('Usuarios ScoreBox', number_format($count, 0, ',', '.'))
                    ->description('Total en Firestore')
                    ->icon('heroicon-o-users')
                    ->color('success'),
            ];
        } catch (\Throwable $exception) {
            Log::error('ScoreBox widget failed to load user count.', [
                'error' => $exception->getMessage(),
            ]);

            return [
                Stat::make('Usuarios ScoreBox', '0')
                    ->description('No disponible')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger'),
            ];
        }
    }
}
