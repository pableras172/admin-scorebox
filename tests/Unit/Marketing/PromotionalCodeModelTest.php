<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PromotionalCodeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_correctly_identifies_available_and_assigned_scopes(): void
    {
        $availableCode = PromotionalCode::factory()->create([
            'assigned_email' => null,
            'assigned_uid' => null,
        ]);

        $assignedCode = PromotionalCode::factory()->create([
            'assigned_email' => 'musician@example.com',
            'assigned_uid' => 'firebase-uid-123',
            'assigned_at' => now(),
        ]);

        $availableCodes = PromotionalCode::available()->get();
        $assignedCodes = PromotionalCode::assigned()->get();

        $this->assertTrue($availableCodes->contains($availableCode));
        $this->assertFalse($availableCodes->contains($assignedCode));

        $this->assertTrue($assignedCodes->contains($assignedCode));
        $this->assertFalse($assignedCodes->contains($availableCode));

        $this->assertFalse($availableCode->isAssigned());
        $this->assertTrue($assignedCode->isAssigned());
    }

    public function test_it_releases_assigned_code_back_to_inventory(): void
    {
        $campaign = MarketingCampaign::factory()->create();

        $code = PromotionalCode::factory()->create([
            'assigned_email' => 'musician@example.com',
            'assigned_uid' => 'firebase-uid-123',
            'marketing_campaign_id' => $campaign->id,
            'assigned_at' => now(),
        ]);

        $this->assertTrue($code->isAssigned());

        $code->release();
        $code->refresh();

        $this->assertFalse($code->isAssigned());
        $this->assertNull($code->assigned_email);
        $this->assertNull($code->assigned_uid);
        $this->assertNull($code->marketing_campaign_id);
        $this->assertNull($code->assigned_at);
    }

    public function test_it_relates_to_marketing_campaign(): void
    {
        $campaign = MarketingCampaign::factory()->create([
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
        ]);

        $code = PromotionalCode::factory()->create([
            'assigned_email' => 'musician@example.com',
            'marketing_campaign_id' => $campaign->id,
        ]);

        $this->assertInstanceOf(MarketingCampaign::class, $code->campaign);
        $this->assertSame($campaign->id, $code->campaign->id);
        $this->assertTrue($campaign->isPromotionalCode());
        $this->assertTrue($campaign->promotionalCodes->contains($code));
    }
}

