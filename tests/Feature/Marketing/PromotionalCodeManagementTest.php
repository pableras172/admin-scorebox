<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Filament\Resources\PromotionalCodes\PromotionalCodeResource;
use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PromotionalCodeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_promotional_codes_by_availability(): void
    {
        $available = PromotionalCode::factory()->create(['code' => 'AVAILABLE-CODE-1']);
        $assigned = PromotionalCode::factory()->assigned()->create([
            'code' => 'ASSIGNED-CODE-1',
            'assigned_email' => 'assigned.user@example.com',
        ]);

        $availableQuery = PromotionalCode::query()->available()->get();
        $this->assertCount(1, $availableQuery);
        $this->assertTrue($availableQuery->contains('id', $available->id));
        $this->assertFalse($availableQuery->contains('id', $assigned->id));

        $assignedQuery = PromotionalCode::query()->assigned()->get();
        $this->assertCount(1, $assignedQuery);
        $this->assertTrue($assignedQuery->contains('id', $assigned->id));
        $this->assertFalse($assignedQuery->contains('id', $available->id));
    }

    public function test_it_searches_promotional_codes_by_code_and_assigned_email(): void
    {
        $code1 = PromotionalCode::factory()->assigned()->create([
            'code' => 'TARGET-ALPHA-999',
            'assigned_email' => 'unique.musician@scorebox.app',
            'assigned_uid' => 'uid-search-1',
        ]);

        $code2 = PromotionalCode::factory()->create([
            'code' => 'OTHER-BETA-111',
        ]);

        // Search by exact code
        $foundByCode = PromotionalCode::where('code', 'TARGET-ALPHA-999')->first();
        $this->assertNotNull($foundByCode);
        $this->assertSame($code1->id, $foundByCode->id);

        // Search by assigned email
        $foundByEmail = PromotionalCode::where('assigned_email', 'unique.musician@scorebox.app')->first();
        $this->assertNotNull($foundByEmail);
        $this->assertSame($code1->id, $foundByEmail->id);

        // Search by assigned uid
        $foundByUid = PromotionalCode::where('assigned_uid', 'uid-search-1')->first();
        $this->assertNotNull($foundByUid);
        $this->assertSame($code1->id, $foundByUid->id);
    }

    public function test_it_releases_assigned_code_and_returns_it_to_available_pool(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Campaña test',
            'content' => '<p>{{promotioncode}}</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'campaign_type' => MarketingCampaign::TYPE_PROMOTIONAL_CODE,
            'is_test' => false,
        ]);

        $code = PromotionalCode::factory()->assigned()->create([
            'code' => 'TO-BE-RELEASED',
            'assigned_email' => 'client@example.com',
            'assigned_uid' => 'uid-client-123',
            'marketing_campaign_id' => $campaign->id,
            'assigned_at' => now()->subDay(),
        ]);

        $this->assertTrue($code->isAssigned());
        $this->assertSame(0, PromotionalCode::available()->count());

        $code->release();

        $code->refresh();
        $this->assertFalse($code->isAssigned());
        $this->assertNull($code->assigned_email);
        $this->assertNull($code->assigned_uid);
        $this->assertNull($code->marketing_campaign_id);
        $this->assertNull($code->assigned_at);
        $this->assertSame(1, PromotionalCode::available()->count());
    }

    public function test_filament_promotional_code_resource_configuration(): void
    {
        $this->assertSame(PromotionalCode::class, PromotionalCodeResource::getModel());
        $this->assertSame('Marketing', PromotionalCodeResource::getNavigationGroup());
        $this->assertSame('código promocional', PromotionalCodeResource::getModelLabel());
        $this->assertSame('códigos promocionales', PromotionalCodeResource::getPluralModelLabel());
    }
}
