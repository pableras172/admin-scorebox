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
        ]);

        $this->assertTrue($validated['isPremium']);
        $this->assertTrue($validated['notificationsEnabled']);
        $this->assertSame('lifetime', $validated['subscription']);
    }
}
