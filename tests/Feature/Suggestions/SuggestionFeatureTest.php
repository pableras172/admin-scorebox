<?php

declare(strict_types=1);

namespace Tests\Feature\Suggestions;

use App\Models\Suggestion;
use App\Services\Firestore\FirestoreClientFactory;
use App\Services\Firestore\FirestoreSuggestionGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class SuggestionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_web_form_can_be_viewed_and_submitted(): void
    {
        $response = $this->get(route('suggestions.create'));
        $response->assertSuccessful();
        $response->assertSee('Buzón de Sugerencias');

        $postResponse = $this->post(route('suggestions.store'), [
            'email' => 'musician@example.com',
            'name' => 'Ana Músico',
            'type' => 'idea',
            'subject' => 'Afinador integrado en pantalla completa',
            'message' => 'Sería genial poder ver el afinador en la esquina superior mientras leo la partitura.',
        ]);

        $postResponse->assertRedirect(route('suggestions.create'));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('suggestions', [
            'email' => 'musician@example.com',
            'name' => 'Ana Músico',
            'type' => 'idea',
            'source' => Suggestion::SOURCE_WEB,
            'status' => Suggestion::STATUS_NEW,
        ]);
    }

    public function test_public_web_form_prefills_email_and_name_from_query_parameters(): void
    {
        $response = $this->get(route('suggestions.create', [
            'email' => 'prefilled@scorebox.app',
            'name' => 'Carlos Flauta',
        ]));

        $response->assertSuccessful();
        $response->assertSee('value="prefilled@scorebox.app"', false);
        $response->assertSee('value="Carlos Flauta"', false);
    }

    public function test_api_endpoint_stores_suggestion_from_mobile_app(): void
    {
        $payload = [
            'email' => 'pianist@scorebox.app',
            'name' => 'David',
            'uid' => 'firebase-uid-789',
            'is_premium' => true,
            'type' => 'bug',
            'subject' => 'Fallo al rotar pantalla',
            'message' => 'Al pasar a modo apaisado, la página 3 se recorta por abajo.',
            'app_version' => '2.4.0',
            'device_info' => 'Xiaomi Pad 6 - Android 14',
        ];

        $response = $this->postJson('/api/v1/suggestions', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('suggestions', [
            'email' => 'pianist@scorebox.app',
            'uid' => 'firebase-uid-789',
            'is_premium' => true,
            'source' => Suggestion::SOURCE_APP,
            'app_version' => '2.4.0',
            'device_info' => 'Xiaomi Pad 6 - Android 14',
            'status' => Suggestion::STATUS_NEW,
        ]);
    }

    public function test_firestore_suggestion_gateway_syncs_documents_without_duplicates(): void
    {
        $documents = [
            new class
            {
                public function id(): string
                {
                    return 'firestore-doc-1';
                }

                public function data(): array
                {
                    return [
                        'email' => 'guitarist@example.com',
                        'displayName' => 'Lucas',
                        'type' => 'scores_request',
                        'message' => 'Por favor añadir más estudios de Carulli.',
                        'isPremium' => false,
                        'appVersion' => '2.3.9',
                    ];
                }
            },
        ];

        $collection = new class($documents)
        {
            public function __construct(private readonly array $documents) {}

            public function documents(): array
            {
                return $this->documents;
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

        $gateway = new FirestoreSuggestionGateway($mockFactory);

        $count = $gateway->syncFromFirestore();
        $this->assertEquals(1, $count);

        $this->assertDatabaseHas('suggestions', [
            'firestore_id' => 'firestore-doc-1',
            'email' => 'guitarist@example.com',
            'name' => 'Lucas',
            'type' => 'scores_request',
        ]);

        // Second sync should skip duplicate
        $count2 = $gateway->syncFromFirestore();
        $this->assertEquals(0, $count2);
    }
}
