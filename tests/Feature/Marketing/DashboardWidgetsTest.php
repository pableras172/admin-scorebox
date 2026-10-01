<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Filament\Widgets\LatestCampaignsWidget;
use App\Filament\Widgets\UsesScoreBox;
use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use App\Models\User;
use App\Services\Firestore\FirestoreResult;
use App\Services\Firestore\FirestoreUserGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

final class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_scorebox_widget_renders_statistics(): void
    {
        $mockGateway = Mockery::mock(FirestoreUserGateway::class);
        $mockGateway->shouldReceive('countAll')->andReturn(FirestoreResult::success(150));
        $mockGateway->shouldReceive('countByPremiumStatus')->andReturn(FirestoreResult::success([
            'premium' => 25,
            'free' => 125,
        ]));

        $this->app->instance(FirestoreUserGateway::class, $mockGateway);

        PromotionalCode::create([
            'code' => 'PROMO-1',
            'is_used' => false,
        ]);
        PromotionalCode::create([
            'code' => 'PROMO-2',
            'is_used' => true,
            'assigned_email' => 'user@example.com',
            'assigned_at' => now(),
        ]);

        MarketingCampaign::create([
            'subject' => 'Camp 1',
            'content' => 'Contenido',
            'status' => MarketingCampaign::STATUS_SENT,
            'is_test' => false,
            'sent_count' => 120,
        ]);

        $widget = new UsesScoreBox;
        $stats = $widget->getStats();

        $this->assertCount(3, $stats);
        $this->assertEquals('150', $stats[0]->getValue());
        $this->assertEquals('1', $stats[1]->getValue());
        $this->assertEquals('1', $stats[2]->getValue());
    }

    public function test_latest_campaigns_widget_renders_successfully(): void
    {
        $user = User::factory()->create();

        MarketingCampaign::create([
            'subject' => 'Última Campaña',
            'content' => '<p>Hola</p>',
            'status' => MarketingCampaign::STATUS_SENT,
            'is_test' => false,
        ]);

        Livewire::actingAs($user)
            ->test(LatestCampaignsWidget::class)
            ->assertSuccessful()
            ->assertSee('Última Campaña');
    }
}
