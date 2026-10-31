<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Firestore\FirestoreUserGateway;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class UsersAndroidVsIos extends ChartWidget
{
    protected ?string $heading = 'Usuarios Android vs iOS';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        try {
            $result = app(FirestoreUserGateway::class)->countByPlatform();
            $data = is_array($result->data()) ? $result->data() : [];
            $android = (int) ($data['android'] ?? 0);
            $ios = (int) ($data['ios'] ?? 0);
            $total = $android + $ios;

            $androidPercent = $total > 0 ? round(($android / $total) * 100, 1) : 0;
            $iosPercent = $total > 0 ? round(($ios / $total) * 100, 1) : 0;

            return [
                'labels' => [
                    'Android ('.$androidPercent.'%)',
                    'iOS ('.$iosPercent.'%)',
                ],
                'datasets' => [[
                    'label' => 'Usuarios',
                    'data' => [$android, $ios],
                    'backgroundColor' => ['#3ddc84', '#007aff'],
                    'hoverOffset' => 4,
                ]],
            ];
        } catch (\Throwable $exception) {
            Log::error('Android vs iOS widget failed to load Firestore data.', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'labels' => ['Android', 'iOS'],
                'datasets' => [[
                    'label' => 'Usuarios',
                    'data' => [0, 0],
                    'backgroundColor' => ['#3ddc84', '#007aff'],
                ]],
            ];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
