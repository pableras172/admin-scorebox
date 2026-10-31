<?php

namespace Tests\Unit;

use App\Services\Firestore\FirestoreUserValidator;
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
        ]);

        $this->assertTrue($validated['isPremium']);
        $this->assertTrue($validated['notificationsEnabled']);
        $this->assertSame('lifetime', $validated['subscription']);
        $this->assertSame('android', $validated['platform']);
    }

    public function test_it_normalizes_platform_field_for_ios_and_handles_null(): void
    {
        $iosUser = FirestoreUserValidator::validate([
            'uid' => 'user-ios',
            'platform' => 'iOS',
        ]);
        $this->assertSame('ios', $iosUser['platform']);

        $nullUser = FirestoreUserValidator::validate([
            'uid' => 'user-null',
        ]);
        $this->assertNull($nullUser['platform']);
    }
}
