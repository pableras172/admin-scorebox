<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Models\EmailUnsubscribe;
use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MarketingAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_campaign_can_be_created_and_managed(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Prueba de Campaña',
            'content' => '<p>Hola {{name}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_FREE,
            'is_test' => false,
            'status' => MarketingCampaign::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'subject' => 'Prueba de Campaña',
            'target_segment' => 'free',
            'is_test' => 0,
            'status' => 'draft',
        ]);

        $this->assertCount(1, MarketingCampaign::drafts()->get());
        $this->assertCount(0, MarketingCampaign::sent()->get());
    }

    public function test_campaign_defaults_to_test_mode_and_converts_to_real(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Campaña con test por defecto',
            'content' => '<p>Contenido</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
        ]);

        $this->assertTrue($campaign->isTest());
        $this->assertTrue($campaign->is_test);
        $this->assertSame(MarketingCampaign::STATUS_DRAFT, $campaign->status);

        $this->assertCount(1, MarketingCampaign::tests()->get());
        $this->assertCount(0, MarketingCampaign::real()->get());

        // Simulate having sent a test
        $campaign->update([
            'status' => MarketingCampaign::STATUS_TEST_SENT,
            'sent_count' => 2,
            'recipients_count' => 2,
            'sent_at' => now(),
        ]);

        // Convert to real production campaign
        $campaign->convertToReal(MarketingCampaign::SEGMENT_PREMIUM);
        $campaign->refresh();

        $this->assertFalse($campaign->isTest());
        $this->assertFalse($campaign->is_test);
        $this->assertSame(MarketingCampaign::SEGMENT_PREMIUM, $campaign->target_segment);
        $this->assertSame(MarketingCampaign::STATUS_DRAFT, $campaign->status);
        $this->assertSame(0, $campaign->sent_count);
        $this->assertSame(0, $campaign->recipients_count);
        $this->assertNull($campaign->sent_at);

        $this->assertCount(0, MarketingCampaign::tests()->get());
        $this->assertCount(1, MarketingCampaign::real()->get());
    }

    public function test_email_suppression_list_supports_manual_addition_and_reversal(): void
    {
        $email = 'manual-suppressed@example.com';

        $record = EmailUnsubscribe::unsubscribe(
            email: $email,
            source: EmailUnsubscribe::SOURCE_MANUAL,
            reason: 'Solicitud telefónica de baja'
        );

        $this->assertDatabaseHas('email_unsubscribes', [
            'email' => $email,
            'source' => EmailUnsubscribe::SOURCE_MANUAL,
            'reason' => 'Solicitud telefónica de baja',
        ]);

        $this->assertTrue(EmailUnsubscribe::isUnsubscribed($email));

        // Reversal / deletion
        $deleted = EmailUnsubscribe::resubscribe($email);
        $this->assertTrue($deleted);
        $this->assertFalse(EmailUnsubscribe::isUnsubscribed($email));
    }
}
