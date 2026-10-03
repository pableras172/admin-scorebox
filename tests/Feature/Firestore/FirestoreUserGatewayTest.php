<?php

declare(strict_types=1);

namespace Tests\Feature\Firestore;

use App\Services\Firestore\FirestoreClientFactory;
use App\Services\Firestore\FirestoreResult;
use App\Services\Firestore\FirestoreUserGateway;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\Cache;
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
        $document = new class(['uid' => 'abc123', 'email' => 'player@example.com', 'displayName' => 'Player One', 'active' => true, 'createdAt' => '2026-08-30T10:00:00Z', 'updatedAt' => '2026-08-30T10:15:00Z', 'profile' => ['language' => 'es']])
        {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $snapshot = new class($document)
        {
            public function __construct(private readonly object $document) {}

            public function data(): array
            {
                return $this->document->data();
            }
        };

        $docReference = new class($snapshot)
        {
            public function __construct(private readonly object $snapshot) {}

            public function snapshot(): object
            {
                return $this->snapshot;
            }
        };

        $collection = new class($docReference)
        {
            public function __construct(private readonly object $reference) {}

            public function document(string $uid): object
            {
                return $this->reference;
            }
        };

        $client = new class($collection)
        {
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
        $document = new class(['email' => 'missing-uid'])
        {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $snapshot = new class($document)
        {
            public function __construct(private readonly object $document) {}

            public function data(): array
            {
                return $this->document->data();
            }
        };

        $docReference = new class($snapshot)
        {
            public function __construct(private readonly object $snapshot) {}

            public function snapshot(): object
            {
                return $this->snapshot;
            }
        };

        $collection = new class($docReference)
        {
            public function __construct(private readonly object $reference) {}

            public function document(string $uid): object
            {
                return $this->reference;
            }
        };

        $client = new class($collection)
        {
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
        $snapshot = new class(['uid' => 'abc123', 'email' => 'player@example.com', 'displayName' => 'Player One', 'active' => true, 'createdAt' => '2026-08-30T10:00:00Z', 'updatedAt' => '2026-08-30T10:15:00Z'])
        {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $query = new class([$snapshot])
        {
            public function __construct(private readonly array $documents) {}

            public function where(string $field, string $operator, mixed $value): self
            {
                return $this;
            }

            public function orderBy(string $field, string $direction = 'ASC'): self
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

        $collection = new class($query)
        {
            public function __construct(private readonly object $query) {}

            public function where(string $field, string $operator, mixed $value): object
            {
                return $this->query->where($field, $operator, $value);
            }

            public function orderBy(string $field, string $direction = 'ASC'): object
            {
                return $this->query->orderBy($field, $direction);
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

        $client = new class($collection)
        {
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

    public function test_it_retrieves_all_users_without_limit(): void
    {
        $document = new class(['uid' => 'user_unlimited', 'email' => 'unlimited@example.com'])
        {
            public function __construct(private readonly array $data) {}

            public function data(): array
            {
                return $this->data;
            }
        };

        $query = new class([$document])
        {
            public function __construct(private readonly array $docs) {}

            public function where(string $field, string $operator, mixed $value): object
            {
                return $this;
            }

            public function orderBy(string $field, string $direction): object
            {
                return $this;
            }

            public function documents(): array
            {
                return $this->docs;
            }
        };

        $collection = new class($query)
        {
            public function __construct(private readonly object $query) {}

            public function orderBy(string $field, string $direction): object
            {
                return $this->query;
            }

            public function where(string $field, string $operator, mixed $value): object
            {
                return $this->query;
            }

            public function documents(): array
            {
                return $this->query->documents();
            }
        };

        $client = new class($collection)
        {
            public function __construct(private readonly object $collection) {}

            public function collection(string $name): object
            {
                return $this->collection;
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($client);

        $gateway = new FirestoreUserGateway($factory);
        $result = $gateway->all();

        $this->assertTrue($result->isSuccess());
        $this->assertCount(1, $result->data());
        $this->assertSame('user_unlimited', $result->data()[0]['uid']);
    }

    public function test_it_counts_all_users_using_aggregation_and_caches_result(): void
    {
        Cache::flush();

        $collection = new class
        {
            public int $countCalls = 0;

            public function count(): int
            {
                $this->countCalls++;

                return 42;
            }
        };

        $client = new class($collection)
        {
            public function __construct(public object $collection) {}

            public function collection(string $name): object
            {
                return $this->collection;
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);
        // Expect make() to be called only ONCE even if countAll() is called TWICE
        $factory->shouldReceive('make')->once()->andReturn($client);

        $gateway = new FirestoreUserGateway($factory);

        $firstResult = $gateway->countAll();
        $this->assertTrue($firstResult->isSuccess());
        $this->assertSame(42, $firstResult->data());

        // Second call should come from cache without invoking factory->make() again
        $secondResult = $gateway->countAll();
        $this->assertTrue($secondResult->isSuccess());
        $this->assertSame(42, $secondResult->data());
    }

    public function test_it_counts_users_by_premium_status_using_aggregation(): void
    {
        Cache::flush();

        $query = new class
        {
            public function count(): int
            {
                return 15;
            }
        };

        $collection = new class($query)
        {
            public function __construct(public object $query) {}

            public function where(string $field, string $operator, mixed $value): object
            {
                return $this->query;
            }
        };

        $client = new class($collection)
        {
            public function __construct(public object $collection) {}

            public function collection(string $name): object
            {
                return $this->collection;
            }
        };

        $factory = Mockery::mock(FirestoreClientFactory::class);
        $factory->shouldReceive('make')->once()->andReturn($client);

        $gateway = new FirestoreUserGateway($factory);

        $result = $gateway->countByPremiumStatus();
        $this->assertTrue($result->isSuccess());
        $this->assertSame(15, $result->data()['premium']);
        $this->assertSame(15, $result->data()['free']);

        // Second call should come from cache
        $cachedResult = $gateway->countByPremiumStatus();
        $this->assertTrue($cachedResult->isSuccess());
    }

    public function test_it_rejects_invalid_update_payloads(): void
    {
        $client = new class
        {
            public function collection(string $name): object
            {
                return new class
                {
                    public function document(string $uid): object
                    {
                        return new class
                        {
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
