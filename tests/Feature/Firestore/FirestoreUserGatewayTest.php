<?php

declare(strict_types=1);

namespace Tests\Feature\Firestore;

use App\Services\Firestore\FirestoreClientFactory;
use App\Services\Firestore\FirestoreResult;
use App\Services\Firestore\FirestoreUserGateway;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FirestoreUserGateway::class)]
#[CoversClass(FirestoreResult::class)]
final class FirestoreUserGatewayTest extends LaravelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_returns_a_validated_user_from_firestore(): void
    {
        $document = new class([
            'uid' => 'abc123',
            'email' => 'player@example.com',
            'displayName' => 'Player One',
            'active' => true,
            'createdAt' => '2026-08-30T10:00:00Z',
            'updatedAt' => '2026-08-30T10:15:00Z',
            'profile' => ['language' => 'es'],
        ]) {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $snapshot = new class($document) {
            public function __construct(private readonly object $document) {}

            public function data(): array
            {
                return $this->document->data();
            }
        };

        $docReference = new class($snapshot) {
            public function __construct(private readonly object $snapshot) {}

            public function snapshot(): object
            {
                return $this->snapshot;
            }
        };

        $collection = new class($docReference) {
            public function __construct(private readonly object $reference) {}

            public function document(string $uid): object
            {
                return $this->reference;
            }
        };

        $client = new class($collection) {
            public function __construct(private readonly object $collection) {}

            public function collection(string $name): object
            {
                return $this->collection;
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($client);

        $gateway = new FirestoreUserGateway($factory);

        $result = $gateway->getById('abc123');

        $this->assertTrue($result->isSuccess());
        $this->assertSame('abc123', $result->data()['uid']);
        $this->assertSame('player@example.com', $result->data()['email']);
    }

    public function test_it_returns_a_controlled_error_for_invalid_document(): void
    {
        $document = new class(['email' => 'missing-uid']) {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $snapshot = new class($document) {
            public function __construct(private readonly object $document) {}

            public function data(): array
            {
                return $this->document->data();
            }
        };

        $docReference = new class($snapshot) {
            public function __construct(private readonly object $snapshot) {}

            public function snapshot(): object
            {
                return $this->snapshot;
            }
        };

        $collection = new class($docReference) {
            public function __construct(private readonly object $reference) {}

            public function document(string $uid): object
            {
                return $this->reference;
            }
        };

        $client = new class($collection) {
            public function __construct(private readonly object $collection) {}

            public function collection(string $name): object
            {
                return $this->collection;
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($client);

        $gateway = new FirestoreUserGateway($factory);

        $result = $gateway->getById('abc123');

        $this->assertFalse($result->isSuccess());
        $this->assertSame('INVALID_DOCUMENT', $result->error());
    }

    public function test_it_lists_users_with_a_safe_limit(): void
    {
        $snapshot = new class([
            'uid' => 'abc123',
            'email' => 'player@example.com',
            'displayName' => 'Player One',
            'active' => true,
            'createdAt' => '2026-08-30T10:00:00Z',
            'updatedAt' => '2026-08-30T10:15:00Z',
        ]) {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $query = new class([$snapshot]) {
            public function __construct(private readonly array $documents) {}

            public function where(string $field, string $operator, mixed $value): self
            {
                return $this;
            }

            public function limit(int $limit): self
            {
                return $this;
            }

            public function documents(): array
            {
                return $this->documents;
            }
        };

        $collection = new class($query) {
            public function __construct(private readonly object $query) {}

            public function where(string $field, string $operator, mixed $value): object
            {
                return $this->query->where($field, $operator, $value);
            }

            public function limit(int $limit): object
            {
                return $this->query->limit($limit);
            }

            public function documents(): array
            {
                return $this->query->documents();
            }
        };

        $client = new class($collection) {
            public function __construct(private readonly object $collection) {}

            public function collection(string $name): object
            {
                return $this->collection;
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($client);

        $gateway = new FirestoreUserGateway($factory);
        $result = $gateway->list([], 40);

        $this->assertTrue($result->isSuccess());
        $this->assertCount(1, $result->data());
        $this->assertSame('abc123', $result->data()[0]['uid']);
    }

    public function test_it_rejects_invalid_update_payloads(): void
    {
        $client = new class() {
            public function collection(string $name): object
            {
                return new class() {
                    public function document(string $uid): object
                    {
                        return new class() {
                            public function set(array $data): void {}
                        };
                    }
                };
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);

        $gateway = new FirestoreUserGateway($factory);
        $result = $gateway->update('abc123', ['email' => 'not-a-valid-email']);

        $this->assertFalse($result->isSuccess());
        $this->assertSame('INVALID_DOCUMENT', $result->error());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
