<?php

declare(strict_types=1);

namespace Tests\Feature\Marketing;

use App\Jobs\SendMarketingCampaignJob;
use App\Models\MarketingCampaign;
use App\Services\Firestore\FirestoreResult;
use App\Services\Firestore\FirestoreUserGateway;
use App\Services\Marketing\CampaignAudienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

final class ScheduledCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_future_scheduled_at_automatically_sets_status_to_scheduled(): void
    {
        $campaign = MarketingCampaign::create([
            'subject' => 'Campaña de Fin de Semana',
            'content' => '<p>Partituras recomendadas</p>',
            'target_segment' => MarketingCampaign::SEGMENT_ALL,
            'is_test' => false,
            'scheduled_at' => now()->addDays(2),
        ]);

        $this->assertEquals(MarketingCampaign::STATUS_SCHEDULED, $campaign->status);
        $this->assertTrue($campaign->isScheduled());
    }

    public function test_scheduled_artisan_command_queues_due_campaigns(): void
    {
        Queue::fake();

        // 1. Campaign ready to send (scheduled 5 minutes ago)
        $dueCampaign = MarketingCampaign::create([
            'subject' => 'Campaña Vencida',
            'content' => '<p>Contenido</p>',
            'status' => MarketingCampaign::STATUS_SCHEDULED,
            'is_test' => false,
            'scheduled_at' => now()->subMinutes(5),
        ]);

        // 2. Campaign for tomorrow
        $futureCampaign = MarketingCampaign::create([
            'subject' => 'Campaña Mañana',
            'content' => '<p>Contenido</p>',
            'status' => MarketingCampaign::STATUS_SCHEDULED,
            'is_test' => false,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->artisan('marketing:send-scheduled')
            ->expectsOutputToContain('1 campañas programadas')
            ->assertExitCode(0);

        Queue::assertPushed(SendMarketingCampaignJob::class, function (SendMarketingCampaignJob $job) use ($dueCampaign): bool {
            return $job->campaignId === $dueCampaign->id;
        });

        $this->assertEquals(MarketingCampaign::STATUS_QUEUED, $dueCampaign->fresh()->status);
        $this->assertEquals(MarketingCampaign::STATUS_SCHEDULED, $futureCampaign->fresh()->status);
    }

    public function test_audience_resolver_filters_by_target_instrument(): void
    {
        $mockGateway = Mockery::mock(FirestoreUserGateway::class);
        $mockGateway->shouldReceive('all')
            ->andReturn(FirestoreResult::success([
                [
                    'uid' => 'user-1',
                    'email' => 'guitar@example.com',
                    'displayName' => 'Guitarrista',
                    'isPremium' => false,
                    'mainInstrument' => 'Guitarra',
                ],
                [
                    'uid' => 'user-2',
                    'email' => 'piano@example.com',
                    'displayName' => 'Pianista',
                    'isPremium' => false,
                    'mainInstrument' => 'Piano',
                ],
                [
                    'uid' => 'user-3',
                    'email' => 'other-guitar@example.com',
                    'displayName' => 'Otro Guitarrista',
                    'isPremium' => true,
                    'mainInstrument' => 'Guitarra',
                ],
            ]));

        $this->app->instance(FirestoreUserGateway::class, $mockGateway);

        $resolver = app(CampaignAudienceResolver::class);

        // Filter by Guitarra
        $guitarRecipients = $resolver->resolve(MarketingCampaign::SEGMENT_ALL, false, 'Guitarra');
        $this->assertCount(2, $guitarRecipients);
        $this->assertEquals(['guitar@example.com', 'other-guitar@example.com'], $guitarRecipients->pluck('email')->all());

        // Filter by Piano
        $pianoRecipients = $resolver->resolve(MarketingCampaign::SEGMENT_ALL, false, 'Piano');
        $this->assertCount(1, $pianoRecipients);
        $this->assertEquals('piano@example.com', $pianoRecipients->first()->email);

        // No filter (null) returns all
        $allRecipients = $resolver->resolve(MarketingCampaign::SEGMENT_ALL, false, null);
        $this->assertCount(3, $allRecipients);
    }
}
