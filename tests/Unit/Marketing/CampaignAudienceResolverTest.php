<?php

declare(strict_types=1);

namespace Tests\Unit\Marketing;

use App\Models\EmailUnsubscribe;
use App\Models\MarketingCampaign;
use App\Services\Firestore\FirestoreResult;
use App\Services\Firestore\FirestoreUserGateway;
use App\Services\Marketing\CampaignAudienceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class CampaignAudienceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_all_valid_recipients_excluding_unsubscribed_users(): void
    {
        $mockUsers = [
            [
                'uid' => 'u1',
                'email' => 'alice@example.com',
                'displayName' => 'Alice Musician',
                'isPremium' => false,
            ],
            [
                'uid' => 'u2',
                'email' => 'bob@example.com',
                'displayName' => 'Bob Pianist',
                'isPremium' => true,
            ],
            [
                'uid' => 'u3',
                'email' => 'unsubscribed@example.com',
                'displayName' => 'Unsubscribed User',
                'isPremium' => false,
            ],
            [
                'uid' => 'u4',
                'email' => 'invalid-email',
                'displayName' => 'Invalid Email User',
                'isPremium' => false,
            ],
            [
                'uid' => 'u5',
                'email' => 'noname@example.com',
                'displayName' => '',
                'isPremium' => true,
            ],
        ];

        EmailUnsubscribe::create([
            'email' => 'unsubscribed@example.com',
            'reason' => 'user_click',
            'source' => 'link',
        ]);

        $gateway = Mockery::mock(FirestoreUserGateway::class);
        $gateway->shouldReceive('list')
            ->with([], 100)
            ->once()
            ->andReturn(FirestoreResult::success($mockUsers, []));

        $resolver = new CampaignAudienceResolver($gateway);
        $recipients = $resolver->resolve(MarketingCampaign::SEGMENT_ALL);

        $this->assertCount(3, $recipients);

        $emails = $recipients->pluck('email')->all();
        $this->assertContains('alice@example.com', $emails);
        $this->assertContains('bob@example.com', $emails);
        $this->assertContains('noname@example.com', $emails);
        $this->assertNotContains('unsubscribed@example.com', $emails);
        $this->assertNotContains('invalid-email', $emails);

        $noNameRecipient = $recipients->firstWhere('email', 'noname@example.com');
        $this->assertSame('músico', $noNameRecipient->name);
    }

    public function test_it_filters_recipients_by_free_and_premium_segments(): void
    {
        $mockUsers = [
            [
                'uid' => 'u1',
                'email' => 'free@example.com',
                'displayName' => 'Free User',
                'isPremium' => false,
            ],
            [
                'uid' => 'u2',
                'email' => 'premium@example.com',
                'displayName' => 'Premium User',
                'isPremium' => true,
            ],
        ];

        $gateway = Mockery::mock(FirestoreUserGateway::class);
        $gateway->shouldReceive('list')
            ->with(['isPremium' => false], 100)
            ->once()
            ->andReturn(FirestoreResult::success([$mockUsers[0]], []));

        $gateway->shouldReceive('list')
            ->with(['isPremium' => true], 100)
            ->once()
            ->andReturn(FirestoreResult::success([$mockUsers[1]], []));

        $resolver = new CampaignAudienceResolver($gateway);

        $freeRecipients = $resolver->resolve(MarketingCampaign::SEGMENT_FREE);
        $this->assertCount(1, $freeRecipients);
        $this->assertSame('free@example.com', $freeRecipients->first()->email);

        $premiumRecipients = $resolver->resolve(MarketingCampaign::SEGMENT_PREMIUM);
        $this->assertCount(1, $premiumRecipients);
        $this->assertSame('premium@example.com', $premiumRecipients->first()->email);
    }

    public function test_it_resolves_test_recipients_when_is_test_is_true(): void
    {
        config(['app.test_emails' => ['tester1@example.com', 'tester2@example.com']]);

        $gateway = Mockery::mock(FirestoreUserGateway::class);
        $gateway->shouldNotReceive('list');

        $resolver = new CampaignAudienceResolver($gateway);
        $testRecipients = $resolver->resolve(MarketingCampaign::SEGMENT_ALL, isTest: true);

        $this->assertCount(2, $testRecipients);
        $this->assertSame(['tester1@example.com', 'tester2@example.com'], $testRecipients->pluck('email')->all());
        $this->assertSame('Tester', $testRecipients->first()->name);
    }
}
