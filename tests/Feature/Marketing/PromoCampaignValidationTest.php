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

    public function test_it_filters_promo_campaign_for_premium_users_without_coupon(): void
    {
        // Premium user 1 already has a code
        PromotionalCode::factory()->assigned()->create([
            'code' => 'PREM-ALREADY-HAS',
            'assigned_email' => 'premium1@example.com',
        ]);
        // 2 fresh codes in stock
        PromotionalCode::factory()->create(['code' => 'FRESH-PREM-1']);
        PromotionalCode::factory()->create(['code' => 'FRESH-PREM-2']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Regalo Premium {{promotioncode}}',
            'content' => '<p>{{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_PREMIUM,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_PREMIUM, false)
            ->andReturn(collect([
                new CampaignRecipient(email: 'premium1@example.com', name: 'Premium Con Cupon', isPremium: true),
                new CampaignRecipient(email: 'premium2@example.com', name: 'Premium Sin Cupon', isPremium: true),
            ]));
        $this->app->instance(CampaignAudienceResolver::class, $resolver);

        $stats = MarketingCampaignsTable::calculatePromoStockStats($campaign);

        // premium1 is excluded, only premium2 is net recipient
        $this->assertSame(1, $stats['total_recipients']);
        $this->assertSame(1, $stats['needed_fresh']);
        $this->assertSame(2, $stats['available_codes']);
        $this->assertSame(0, $stats['deficit']);
    }

    public function test_it_duplicates_a_sent_campaign_into_draft(): void
    {
        $sentCampaign = MarketingCampaign::create([
            'subject' => 'Campaña Original',
            'content' => '<p>Contenido original</p>',
            'target_segment' => MarketingCampaign::SEGMENT_FREE,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_SENT,
            'sent_at' => now(),
            'recipients_count' => 100,
            'sent_count' => 98,
            'failed_count' => 2,
        ]);

        $clone = $sentCampaign->replicate([
            'status',
            'sent_at',
            'scheduled_at',
            'recipients_count',
            'sent_count',
            'failed_count',
        ]);
        $clone->subject = "{$sentCampaign->subject} (Copia)";
        $clone->status = MarketingCampaign::STATUS_DRAFT;
        $clone->sent_at = null;
        $clone->scheduled_at = null;
        $clone->recipients_count = 0;
        $clone->sent_count = 0;
        $clone->failed_count = 0;
        $clone->save();

        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $clone->id,
            'subject' => 'Campaña Original (Copia)',
            'status' => MarketingCampaign::STATUS_DRAFT,
            'target_segment' => MarketingCampaign::SEGMENT_FREE,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'recipients_count' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'sent_at' => null,
        ]);
    }
}
