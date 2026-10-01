<?php

namespace App\Filament\Widgets;

use App\Services\Firestore\FirestoreUserGateway;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class UsersPremiumVsFree extends ChartWidget
{
    protected ?string $heading = 'Usuarios premium vs no premium';

    protected function getData(): array
    {
        try {
            $result = app(FirestoreUserGateway::class)->countByPremiumStatus();
            $premium = is_array($result->data()) ? (int) ($result->data()['premium'] ?? 0) : 0;
            $free = is_array($result->data()) ? (int) ($result->data()['free'] ?? 0) : 0;
            $total = $premium + $free;

            $premiumPercent = $total > 0 ? round(($premium / $total) * 100, 1) : 0;
            $freePercent = $total > 0 ? round(($free / $total) * 100, 1) : 0;

            return [
                'labels' => [
                    'Premium ('.$premiumPercent.'%)',
                    'No premium ('.$freePercent.'%)',
                ],
                'datasets' => [[
                    'label' => 'Usuarios',
                    'data' => [$premium, $free],
                    'backgroundColor' => ['#22c55e', '#94a3b8'],
                    'hoverOffset' => 4,
                ]],
            ];
        } catch (\Throwable $exception) {
            Log::error('Premium vs free widget failed to load Firestore data.', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'labels' => ['Premium', 'No premium'],
                'datasets' => [[
                    'label' => 'Usuarios',
                    'data' => [0, 0],
                    'backgroundColor' => ['#22c55e', '#94a3b8'],
                ]],
            ];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
