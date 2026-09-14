<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Jobs\SendMarketingCampaignJob;
use App\Mail\MarketingCampaignMailable;
use App\Models\MarketingCampaign;
use App\Services\Marketing\CampaignAudienceResolver;
use App\Services\Marketing\CampaignRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

final class SendMarketingCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_personalized_campaign_emails_and_updates_status(): void
    {
        Mail::fake();

        $campaign = MarketingCampaign::create([
            'subject' => '¡Hola {{name}}, nueva partitura disponible!',
            'content' => '<p>Hola {{name}}, te encantará nuestra nueva colección.</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_QUEUED,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'pianist@example.com', name: 'Chopin', isPremium: true),
                new CampaignRecipient(email: 'violinist@example.com', name: 'músico', isPremium: false),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(2, $campaign->recipients_count);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertNotNull($campaign->sent_at);

        Mail::assertSent(MarketingCampaignMailable::class, 2);

        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail): bool {
            return $mail->hasTo('pianist@example.com')
                && $mail->campaignSubject === '¡Hola Chopin, nueva partitura disponible!'
                && str_contains($mail->campaignContent, 'Hola Chopin,')
                && str_contains($mail->unsubscribeUrl, 'marketing/unsubscribe');
        });

        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail): bool {
            return $mail->hasTo('violinist@example.com')
                && $mail->campaignSubject === '¡Hola músico, nueva partitura disponible!'
                && str_contains($mail->campaignContent, 'Hola m&uacute;sico,') || str_contains($mail->campaignContent, 'Hola músico,');
        });
    }

    public function test_it_dispatches_test_campaign_and_can_be_converted_to_real_campaign(): void
    {
        Mail::fake();

        $campaign = MarketingCampaign::create([
            'subject' => 'Prueba de email: {{name}}',
            'content' => '<p>Verificación de diseño</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'is_test' => true,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, true)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'pableras172@hotmail.com', name: 'Tester', isPremium: false),
                new CampaignRecipient(email: 'pableras172@gmail.com', name: 'Tester', isPremium: false),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        // Should transition to STATUS_TEST_SENT when is_test is true
        $this->assertSame(MarketingCampaign::STATUS_TEST_SENT, $campaign->status);
        $this->assertTrue($campaign->isTest());
        $this->assertSame(2, $campaign->sent_count);

        Mail::assertSent(MarketingCampaignMailable::class, 2);

        // Convert to real campaign
        $campaign->convertToReal(MarketingCampaign::SEGMENT_PREMIUM);
        $campaign->refresh();

        $this->assertFalse($campaign->isTest());
        $this->assertSame(MarketingCampaign::SEGMENT_PREMIUM, $campaign->target_segment);
        $this->assertSame(MarketingCampaign::STATUS_DRAFT, $campaign->status);
        $this->assertSame(0, $campaign->sent_count);
        $this->assertNull($campaign->sent_at);

        // Dispatch real campaign
        $resolverReal = Mockery::mock(CampaignAudienceResolver::class);
        $resolverReal->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_PREMIUM, false)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'real.user@example.com', name: 'Real User', isPremium: true),
            ]));

        $jobReal = new SendMarketingCampaignJob($campaign->id);
        $jobReal->handle($resolverReal);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(1, $campaign->sent_count);
    }

    public function test_it_dispatches_promo_code_campaign_and_assigns_codes_atomically(): void
    {
        Mail::fake();

        \App\Models\PromotionalCode::factory()->create(['code' => 'PROMO-ALPHA']);
        \App\Models\PromotionalCode::factory()->create(['code' => 'PROMO-BETA']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Tu código PRO: {{promotioncode}}',
            'content' => '<p>Hola {{name}}, usa tu código {{promotioncode}} para desbloquear la app.</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'user1@example.com', name: 'User One', isPremium: false, uid: 'uid-1'),
                new CampaignRecipient(email: 'user2@example.com', name: 'User Two', isPremium: false, uid: 'uid-2'),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(2, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);

        $assignedCodes = \App\Models\PromotionalCode::query()->assigned()->get();
        $this->assertCount(2, $assignedCodes);

        $user1Code = \App\Models\PromotionalCode::where('assigned_email', 'user1@example.com')->first();
        $this->assertNotNull($user1Code);
        $this->assertSame('uid-1', $user1Code->assigned_uid);
        $this->assertSame($campaign->id, $user1Code->marketing_campaign_id);
        $this->assertNotNull($user1Code->assigned_at);

        $user2Code = \App\Models\PromotionalCode::where('assigned_email', 'user2@example.com')->first();
        $this->assertNotNull($user2Code);
        $this->assertSame('uid-2', $user2Code->assigned_uid);

        Mail::assertSent(MarketingCampaignMailable::class, 2);

        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail) use ($user1Code): bool {
            return $mail->hasTo('user1@example.com')
                && $mail->campaignSubject === "Tu código PRO: {$user1Code->code}"
                && str_contains($mail->campaignContent, "usa tu código {$user1Code->code}");
        });

        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail) use ($user2Code): bool {
            return $mail->hasTo('user2@example.com')
                && $mail->campaignSubject === "Tu código PRO: {$user2Code->code}"
                && str_contains($mail->campaignContent, "usa tu código {$user2Code->code}");
        });
    }

    public function test_it_resends_same_code_to_non_premium_recipient_when_exclusion_is_disabled(): void
    {
        Mail::fake();

        $existing = \App\Models\PromotionalCode::factory()->assigned()->create([
            'code' => 'EXISTING-PROMO',
            'assigned_email' => 'repeat@example.com',
            'assigned_uid' => 'uid-repeat',
        ]);

        $premiumAssigned = \App\Models\PromotionalCode::factory()->assigned()->create([
            'code' => 'PREMIUM-PROMO',
            'assigned_email' => 'premium@example.com',
            'assigned_uid' => 'uid-prem',
        ]);

        $fresh = \App\Models\PromotionalCode::factory()->create(['code' => 'FRESH-PROMO']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Regalo: {{promotioncode}}',
            'content' => '<p>Tu código: {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => false,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->once()
            ->andReturn(collect([
                // Previously received code, isPremium = false -> should resend same code
                new CampaignRecipient(email: 'repeat@example.com', name: 'Repeat', isPremium: false, uid: 'uid-repeat'),
                // Previously received code, isPremium = true -> should be omitted
                new CampaignRecipient(email: 'premium@example.com', name: 'Premium User', isPremium: true, uid: 'uid-prem'),
                // Never received code -> gets fresh code
                new CampaignRecipient(email: 'fresh@example.com', name: 'Fresh User', isPremium: false, uid: 'uid-fresh'),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(2, $campaign->sent_count);

        Mail::assertSent(MarketingCampaignMailable::class, 2);

        // repeat@example.com gets EXISTING-PROMO
        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail): bool {
            return $mail->hasTo('repeat@example.com')
                && str_contains($mail->campaignSubject, 'EXISTING-PROMO')
                && str_contains($mail->campaignContent, 'EXISTING-PROMO');
        });

        // fresh@example.com gets FRESH-PROMO
        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail): bool {
            return $mail->hasTo('fresh@example.com')
                && str_contains($mail->campaignSubject, 'FRESH-PROMO')
                && str_contains($mail->campaignContent, 'FRESH-PROMO');
        });

        // premium@example.com receives nothing
        Mail::assertNotSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail): bool {
            return $mail->hasTo('premium@example.com');
        });
    }

    public function test_it_excludes_previous_recipients_when_exclusion_is_enabled(): void
    {
        Mail::fake();

        \App\Models\PromotionalCode::factory()->assigned()->create([
            'code' => 'OLD-CODE',
            'assigned_email' => 'old@example.com',
        ]);

        \App\Models\PromotionalCode::factory()->create(['code' => 'FRESH-CODE']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Código: {{promotioncode}}',
            'content' => '<p>Código: {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'old@example.com', name: 'Old', isPremium: false),
                new CampaignRecipient(email: 'fresh@example.com', name: 'Fresh', isPremium: false),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(1, $campaign->sent_count);

        Mail::assertSent(MarketingCampaignMailable::class, 1);
        Mail::assertNotSent(MarketingCampaignMailable::class, fn ($m) => $m->hasTo('old@example.com'));
        Mail::assertSent(MarketingCampaignMailable::class, fn ($m) => $m->hasTo('fresh@example.com'));
    }

    public function test_it_injects_simulated_code_in_test_mode_without_touching_db(): void
    {
        Mail::fake();

        $campaign = MarketingCampaign::create([
            'subject' => 'Test: {{promotioncode}}',
            'content' => '<p>Prueba código {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => true,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, true)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'tester@example.com', name: 'Tester', isPremium: false),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_TEST_SENT, $campaign->status);
        $this->assertSame(1, $campaign->sent_count);

        $this->assertSame(0, \App\Models\PromotionalCode::count());

        Mail::assertSent(MarketingCampaignMailable::class, function (MarketingCampaignMailable $mail): bool {
            return $mail->hasTo('tester@example.com')
                && str_contains($mail->campaignSubject, 'PROMO-TEST-')
                && str_contains($mail->campaignContent, 'PROMO-TEST-');
        });
    }

    public function test_it_fails_and_aborts_if_insufficient_promotional_codes_available(): void
    {
        Mail::fake();

        // Stock has 1 code, but we need 2 fresh codes
        \App\Models\PromotionalCode::factory()->create(['code' => 'ONLY-ONE']);

        $campaign = MarketingCampaign::create([
            'subject' => 'Código: {{promotioncode}}',
            'content' => '<p>Código: {{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'exclude_previous_promo_recipients' => true,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldReceive('resolve')
            ->with(MarketingCampaign::SEGMENT_ALL, false)
            ->once()
            ->andReturn(collect([
                new CampaignRecipient(email: 'user1@example.com', name: 'User 1', isPremium: false),
                new CampaignRecipient(email: 'user2@example.com', name: 'User 2', isPremium: false),
            ]));

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        $campaign->refresh();

        $this->assertSame(MarketingCampaign::STATUS_FAILED, $campaign->status);
        Mail::assertNothingSent();
        $this->assertSame(1, \App\Models\PromotionalCode::available()->count());
    }

    public function test_it_aborts_if_campaign_is_already_sent(): void
    {
        Mail::fake();

        $campaign = MarketingCampaign::create([
            'subject' => 'Campaña ya enviada',
            'content' => '<p>Contenido</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_SENT,
        ]);

        $resolver = Mockery::mock(CampaignAudienceResolver::class);
        $resolver->shouldNotReceive('resolve');

        $job = new SendMarketingCampaignJob($campaign->id);
        $job->handle($resolver);

        Mail::assertNothingSent();
    }
}
