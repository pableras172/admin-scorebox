<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Firestore\FirestoreUserValidator;
use App\Services\Firestore\ScoreBoxUser;
use Tests\TestCase;

class FirestoreUserValidatorTest extends TestCase
{
    public function test_it_preserves_boolean_flags_and_subscription_values(): void
    {
        $validated = FirestoreUserValidator::validate([
            'uid' => 'user-123',
            'email' => 'user@example.com',
            'displayName' => 'User Name',
            'isPremium' => true,
            'notificationsEnabled' => true,
            'subscription' => 'lifetime',
            'platform' => 'Android',
            'orderId' => 'GPA.1234-5678-9012',
            'cloudProvider' => 'GOOGLE_DRIVE',
            'cloudAccountEmail' => 'user.drive@gmail.com',
        ]);

        $this->assertTrue($validated['isPremium']);
        $this->assertTrue($validated['notificationsEnabled']);
        $this->assertSame('lifetime', $validated['subscription']);
        $this->assertSame('android', $validated['platform']);
        $this->assertSame('GPA.1234-5678-9012', $validated['orderId']);
        $this->assertSame('google_drive', $validated['cloudProvider']);
        $this->assertSame('user.drive@gmail.com', $validated['cloudAccountEmail']);
    }

    public function test_it_normalizes_platform_field_for_ios_and_handles_null(): void
    {
        $iosUser = FirestoreUserValidator::validate([
            'uid' => 'user-ios',
            'platform' => 'iOS',
            'cloudProvider' => 'ICLOUD_DRIVE',
        ]);
        $this->assertSame('ios', $iosUser['platform']);
        $this->assertSame('icloud_drive', $iosUser['cloudProvider']);

        $nullUser = FirestoreUserValidator::validate([
            'uid' => 'user-null',
        ]);
        $this->assertNull($nullUser['platform']);
        $this->assertNull($nullUser['cloudProvider']);
    }

    public function test_scorebox_user_cloud_labels_and_trial_status(): void
    {
        $user = ScoreBoxUser::fromArray([
            'uid' => 'usr-1',
            'cloudProvider' => 'google_drive',
            'subscription' => 'lifetime',
        ]);

        $this->assertSame('Google Drive', $user->getCloudProviderLabel());
        $this->assertSame('pro', $user->getTrialStatus());

        $userIcloud = ScoreBoxUser::fromArray([
            'uid' => 'usr-2',
            'cloudProvider' => 'icloud_drive',
        ]);
        $this->assertSame('iCloud Drive', $userIcloud->getCloudProviderLabel());
    }
}
