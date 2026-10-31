<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Firestore\FirestoreClientFactory;
use App\Services\Firestore\FirestoreMusiciansDirectoryGateway;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MusiciansDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_musicians_directory_gateway_all_maps_profiles(): void
    {
        $mockDoc = Mockery::mock(DocumentSnapshot::class);
        $mockDoc->shouldReceive('id')->andReturn('user-musician-1');
        $mockDoc->shouldReceive('data')->andReturn([
            'userId' => 'user-musician-1',
            'displayName' => 'Carlos Flautista',
            'photoUrl' => 'https://example.com/photo.jpg',
            'primaryInstrumentId' => 'flute',
            'primaryInstrumentName' => 'Flauta Travesera',
            'countryName' => 'España',
            'city' => 'Valencia',
            'roles' => ['gigs_available', 'teacher'],
            'isVisible' => true,
            'isVerified' => true,
            'isFeatured' => false,
            'isPro' => true,
        ]);

        $mockCollection = Mockery::mock(CollectionReference::class);
        $mockCollection->shouldReceive('documents')->once()->andReturn([$mockDoc]);

        $mockClient = Mockery::mock(FirestoreClient::class);
        $mockClient->shouldReceive('collection')->with('musicians_directory')->once()->andReturn($mockCollection);

        $mockFactory = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $gateway = new FirestoreMusiciansDirectoryGateway($mockFactory);
        $result = $gateway->all(fresh: true);

        $this->assertTrue($result->isSuccess());
        $data = $result->data();
        $this->assertCount(1, $data);
        $this->assertEquals('Carlos Flautista', $data[0]['displayName']);
        $this->assertEquals('Flauta Travesera', $data[0]['primaryInstrumentName']);
        $this->assertTrue($data[0]['isVisible']);
        $this->assertTrue($data[0]['isVerified']);
        $this->assertTrue($data[0]['isPro']);
    }

    public function test_musicians_directory_gateway_toggle_visibility(): void
    {
        $mockDocRef = Mockery::mock(DocumentReference::class);
        $mockDocRef->shouldReceive('set')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['isVisible']) && $data['isVisible'] === false;
            }), ['merge' => true])
            ->andReturn([]);

        $mockCollection = Mockery::mock(CollectionReference::class);
        $mockCollection->shouldReceive('document')->with('user-musician-1')->once()->andReturn($mockDocRef);

        $mockClient = Mockery::mock(FirestoreClient::class);
        $mockClient->shouldReceive('collection')->with('musicians_directory')->once()->andReturn($mockCollection);

        $mockFactory = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $gateway = new FirestoreMusiciansDirectoryGateway($mockFactory);
        $result = $gateway->toggleVisibility('user-musician-1', false);

        $this->assertTrue($result->isSuccess());
    }

    public function test_musicians_directory_gateway_toggle_verified(): void
    {
        $mockDocRef = Mockery::mock(DocumentReference::class);
        $mockDocRef->shouldReceive('set')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['isVerified']) && $data['isVerified'] === true;
            }), ['merge' => true])
            ->andReturn([]);

        $mockCollection = Mockery::mock(CollectionReference::class);
        $mockCollection->shouldReceive('document')->with('user-musician-2')->once()->andReturn($mockDocRef);

        $mockClient = Mockery::mock(FirestoreClient::class);
        $mockClient->shouldReceive('collection')->with('musicians_directory')->once()->andReturn($mockCollection);

        $mockFactory = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $gateway = new FirestoreMusiciansDirectoryGateway($mockFactory);
        $result = $gateway->toggleVerified('user-musician-2', true);

        $this->assertTrue($result->isSuccess());
    }
}
