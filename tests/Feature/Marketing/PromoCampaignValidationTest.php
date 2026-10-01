<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Filament\Resources\MarketingCampaigns\Tables\MarketingCampaignsTable;
use App\Jobs\SendMarketingCampaignJob;
use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use App\Services\Marketing\CampaignAudienceResolver;
use App\Services\Marketing\CampaignRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class PromoCampaignValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_stock_deficit_correctly(): void
    {
        PromotionalCode::factory()->create(['code' => 'CODE-1']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Oferta {{promotioncode}}',
            'content' => '<p>Código {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->andReturn(collect([
                new CampaignRecipient(email: 'user1@example.com', name: 'User 1', isPremium: false),
                new CampaignRecipient(email: 'user2@example.com', name: 'User 2', isPremium: false),
                new CampaignRecipient(email: 'user3@example.com', name: 'User 3', isPremium: false),
            ]));
        $this->app->instance(CampaignAudienceResolver::class, $resolver);

        $stats = MarketingCampaignsTable::calculatePromoStockStats($campaign);

        $this->assertSame(3, $stats['total_recipients']);
        $this->assertSame(3, $stats['needed_fresh']);
        $this->assertSame(1, $stats['available_codes']);
        $this->assertSame(2, $stats['deficit']);
    }

    public function test_it_does_not_require_stock_for_test_mode(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Test {{promotioncode}}',
            'content' => '<p>Código {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => true,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, true)
            ->andReturn(collect([
                new CampaignRecipient(email: 'tester@example.com', name: 'Tester', isPremium: false),
            ]));
        $this->app->instance(CampaignAudienceResolver::class, $resolver);

        $stats = MarketingCampaignsTable::calculatePromoStockStats($campaign);

        $this->assertSame(1, $stats['total_recipients']);
        $this->assertSame(0, $stats['needed_fresh']);
        $this->assertSame(0, $stats['available_codes']);
        $this->assertSame(0, $stats['deficit']);
    }

    public function test_it_accounts_for_reused_codes_when_exclusion_is_disabled(): void
    {
        // 1 assigned code to user1 (who is non-premium)
        PromotionalCode::factory()->assigned()->create([
            'code' => 'EXISTING',
            'assigned_email' => 'user1@example.com',
        ]);
        // 1 available code in stock
        PromotionalCode::factory()->create(['code' => 'FRESH-1']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Promo {{promotioncode}}',
            'content' => '<p>{{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => false,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->andReturn(collect([
                // user1 gets existing code, doesn't consume new code
                new CampaignRecipient(email: 'user1@example.com', name: 'User 1', isPremium: false),
                // user2 needs fresh code
                new CampaignRecipient(email: 'user2@example.com', name: 'User 2', isPremium: false),
            ]));
        $this->app->instance(CampaignAudienceResolver::class, $resolver);

        $stats = MarketingCampaignsTable::calculatePromoStockStats($campaign);

        $this->assertSame(2, $stats['total_recipients']);
        $this->assertSame(1, $stats['needed_fresh']);
        $this->assertSame(1, $stats['available_codes']);
        $this->assertSame(0, $stats['deficit']);
    }

    public function test_send_action_aborts_dispatch_when_deficit_exists(): void
    {
        Queue::fake();

        // 0 codes available
        $campaign = MarketingCampaign::create([
            'subject' => 'Promo {{promotioncode}}',
            'content' => '<p>{{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->andReturn(collect([
                new CampaignRecipient(email: 'user1@example.com', name: 'User 1', isPremium: false),
            ]));
        $this->app->instance(CampaignAudienceResolver::class, $resolver);

        // Call the action callback directly or simulate table action execution
        $stats = MarketingCampaignsTable::calculatePromoStockStats($campaign);
        $this->assertGreaterThan(0, $stats['deficit']);

        // Verify that SendMarketingCampaignJob aborted when run with insufficient stock
        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();
        $this->assertSame(MarketingCampaign::STATUS_FAILED, $campaign->status);
    }
}
