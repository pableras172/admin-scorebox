<?php

declare(strict_types=1);

namespace Tests\Unit\Firestore;

use App\Services\Firestore\FirestoreUserValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FirestoreUserValidator::class)]
final class ScoreBoxUserValidatorTest extends TestCase
{
    public function test_valid_document_is_normalized(): void
    {
        $payload = [
            'uid' => 'abc123',
            'email' => 'player@example.com',
            'displayName' => 'Player One',
            'photoUrl' => 'https://example.com/avatar.png',
            'active' => true,
            'createdAt' => '2026-08-30T10:00:00Z',
            'updatedAt' => '2026-08-30T10:15:00Z',
            'profile' => ['language' => 'es'],
        ];

        $normalized = FirestoreUserValidator::validate($payload);

        $this->assertSame('abc123', $normalized['uid']);
        $this->assertSame('player@example.com', $normalized['email']);
        $this->assertSame('Player One', $normalized['displayName']);
        $this->assertTrue($normalized['active']);
        $this->assertSame(['language' => 'es'], $normalized['profile']);
        $this->assertIsString($normalized['createdAt']);
        $this->assertIsString($normalized['updatedAt']);
    }

    public function test_firestore_timestamp_values_are_accepted(): void
    {
        $payload = [
            'uid' => 'abc123',
            'email' => 'player@example.com',
            'displayName' => 'Player One',
            'active' => true,
            'createdAt' => new \Google\Cloud\Core\Timestamp(new \DateTimeImmutable('2026-08-30T10:00:00Z')),
            'updatedAt' => new \Google\Cloud\Core\Timestamp(new \DateTimeImmutable('2026-08-30T10:15:00Z')),
            'profile' => ['language' => 'es'],
        ];

        $normalized = FirestoreUserValidator::validate($payload);

        $this->assertSame('abc123', $normalized['uid']);
        $this->assertSame('2026-08-30T10:00:00Z', $normalized['createdAt']);
        $this->assertSame('2026-08-30T10:15:00Z', $normalized['updatedAt']);
    }

    public function test_numeric_millisecond_timestamps_are_normalized(): void
    {
        $payload = [
            'uid' => 'abc123',
            'email' => 'player@example.com',
            'displayName' => 'Player One',
            'active' => true,
            'createdAt' => 1785267918573,
            'updatedAt' => 1785413647425,
            'profile' => ['language' => 'es'],
        ];

        $normalized = FirestoreUserValidator::validate($payload);

        $this->assertSame('abc123', $normalized['uid']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/', $normalized['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/', $normalized['updatedAt']);
    }

    public function test_missing_required_uid_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('uid');

        FirestoreUserValidator::validate([
            'email' => 'player@example.com',
            'active' => true,
        ]);
    }
}
