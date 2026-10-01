<?php

namespace App\Filament\Widgets;

use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use App\Services\Firestore\FirestoreUserGateway;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Log;

class UsesScoreBox extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public function getStats(): array
    {
        $stats = [];

        // 1. Usuarios ScoreBox (Firestore)
        try {
            $gateway = app(FirestoreUserGateway::class);
            $countResult = $gateway->countAll();
            $totalUsers = is_numeric($countResult->data()) ? (int) $countResult->data() : 0;

            $premiumResult = $gateway->countByPremiumStatus();
            $premiumData = is_array($premiumResult->data()) ? $premiumResult->data() : [];
            $premiumCount = (int) ($premiumData['premium'] ?? 0);
            $freeCount = (int) ($premiumData['free'] ?? 0);

            $stats[] = Stat::make('Usuarios ScoreBox', number_format($totalUsers, 0, ',', '.'))
                ->description("{$premiumCount} Premium · {$freeCount} Free")
                ->descriptionIcon('heroicon-m-user-group')
                ->icon('heroicon-o-users')
                ->color('success');
        } catch (\Throwable $exception) {
            Log::error('ScoreBox widget failed to load user count.', [
                'error' => $exception->getMessage(),
            ]);

            $stats[] = Stat::make('Usuarios ScoreBox', '0')
                ->description('No disponible temporalmente')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger');
        }

        // 2. Stock Códigos Promocionales Play Store
        try {
            $availableCodes = PromotionalCode::available()->count();
            $assignedCodes = PromotionalCode::assigned()->count();
            $totalCodes = PromotionalCode::count();

            $color = match (true) {
                $availableCodes > 15 => 'success',
                $availableCodes > 5 => 'warning',
                default => 'danger',
            };

            $stockDescription = $totalCodes > 0
                ? "{$assignedCodes} entregados de {$totalCodes} totales"
                : 'Sin inventario de códigos';

            $stats[] = Stat::make('Stock Códigos Promo', number_format($availableCodes, 0, ',', '.'))
                ->description($stockDescription)
                ->descriptionIcon('heroicon-m-ticket')
                ->icon('heroicon-o-ticket')
                ->color($color);
        } catch (\Throwable $exception) {
            $stats[] = Stat::make('Stock Códigos Promo', '-')
                ->description('Error al consultar stock')
                ->icon('heroicon-o-ticket')
                ->color('gray');
        }

        // 3. Campañas y Correos Enviados
        try {
            $sentCampaigns = MarketingCampaign::where('status', MarketingCampaign::STATUS_SENT)->count();
            $scheduledCampaigns = MarketingCampaign::where('status', MarketingCampaign::STATUS_SCHEDULED)->count();
            $totalSentEmails = (int) MarketingCampaign::sum('sent_count');

            $campaignDescription = $scheduledCampaigns > 0
                ? "{$scheduledCampaigns} programadas · {$totalSentEmails} correos enviados"
                : "{$totalSentEmails} correos enviados en total";

            $stats[] = Stat::make('Campañas Enviadas', number_format($sentCampaigns, 0, ',', '.'))
                ->description($campaignDescription)
                ->descriptionIcon('heroicon-m-paper-airplane')
                ->icon('heroicon-o-envelope')
                ->color('primary');
        } catch (\Throwable $exception) {
            $stats[] = Stat::make('Campañas Enviadas', '-')
                ->description('Sin datos')
                ->icon('heroicon-o-envelope')
                ->color('gray');
        }

        return $stats;
    }
}
