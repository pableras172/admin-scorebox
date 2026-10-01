<?php

declare(strict_types=1);

namespace Tests\Feature\Firestore;

use App\Services\Firestore\FirestoreAppAnnouncementGateway;
use App\Services\Firestore\FirestoreClientFactory;
use Mockery;
use Tests\TestCase;

final class AppAnnouncementTest extends TestCase
{
    public function test_it_saves_and_retrieves_announcement_data(): void
    {
        $storage = [];

        $docRef = new class($storage)
        {
            public function __construct(public array &$storage) {}

            public function snapshot(): object
            {
                $data = $this->storage;

                return new class($data)
                {
                    public function __construct(private readonly array $data) {}

                    public function data(): array
                    {
                        return $this->data;
                    }
                };
            }

            public function set(array $data): void
            {
                $this->storage = $data;
            }
        };

        $collection = new class($docRef)
        {
            public function __construct(private readonly object $docRef) {}

            public function document(string $id): object
            {
                return $this->docRef;
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

        $mockFactory = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory->shouldReceive('make')->andReturn($client);

        $gateway = new FirestoreAppAnnouncementGateway($mockFactory);

        $saveResult = $gateway->save([
            'enabled' => true,
            'title' => '¡Nuevas partituras disponibles!',
            'message' => 'Descubre la nueva sección de jazz.',
            'type' => 'promo',
            'action_text' => 'Ver Partituras',
            'action_url' => 'https://example.com/scores',
        ]);

        $this->assertTrue($saveResult->isSuccess());

        $loaded = $gateway->get();
        $this->assertTrue($loaded['enabled']);
        $this->assertEquals('¡Nuevas partituras disponibles!', $loaded['title']);
        $this->assertEquals('Descubre la nueva sección de jazz.', $loaded['message']);
        $this->assertEquals('promo', $loaded['type']);
        $this->assertEquals('Ver Partituras', $loaded['action_text']);
        $this->assertEquals('https://example.com/scores', $loaded['action_url']);
        $this->assertNotEmpty($loaded['updated_at']);
    }
}
