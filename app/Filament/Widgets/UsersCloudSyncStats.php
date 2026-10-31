<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Firestore\FirestoreUserGateway;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class UsersCloudSyncStats extends ChartWidget
{
    protected ?string $heading = 'Copia en la Nube (Google Drive vs iCloud)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        try {
            $result = app(FirestoreUserGateway::class)->countByCloudProvider();
            $data = is_array($result->data()) ? $result->data() : [];

            $drive = (int) ($data['google_drive'] ?? 0);
            $icloud = (int) ($data['icloud_drive'] ?? 0);
            $none = (int) ($data['none'] ?? 0);
            $total = $drive + $icloud + $none;

            $drivePercent = $total > 0 ? round(($drive / $total) * 100, 1) : 0;
            $icloudPercent = $total > 0 ? round(($icloud / $total) * 100, 1) : 0;
            $nonePercent = $total > 0 ? round(($none / $total) * 100, 1) : 0;

            return [
                'labels' => [
                    'Google Drive ('.$drivePercent.'%)',
                    'iCloud Drive ('.$icloudPercent.'%)',
                    'Sin Copia ('.$nonePercent.'%)',
                ],
                'datasets' => [[
                    'label' => 'Usuarios',
                    'data' => [$drive, $icloud, $none],
                    'backgroundColor' => ['#0288d1', '#6366f1', '#94a3b8'],
                    'hoverOffset' => 4,
                ]],
            ];
        } catch (\Throwable $exception) {
            Log::error('Cloud sync widget failed to load Firestore data.', [
                'error' => $exception->getMessage(),
            ]);

            return [
                'labels' => ['Google Drive', 'iCloud Drive', 'Sin Copia'],
                'datasets' => [[
                    'label' => 'Usuarios',
                    'data' => [0, 0, 0],
                    'backgroundColor' => ['#0288d1', '#6366f1', '#94a3b8'],
                ]],
            ];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
