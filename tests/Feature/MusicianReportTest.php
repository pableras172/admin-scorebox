<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MusicianReport;
use App\Services\Firestore\FirestoreClientFactory;
use App\Services\Firestore\FirestoreMusicianReportGateway;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MusicianReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_musician_report_model_and_scopes(): void
    {
        $pending = MusicianReport::create([
            'firestore_id' => 'rep-1',
            'reported_user_id' => 'user-123',
            'reason' => 'Spam y publicidad masiva',
            'status' => MusicianReport::STATUS_PENDING,
            'reported_at' => now(),
        ]);

        $resolved = MusicianReport::create([
            'firestore_id' => 'rep-2',
            'reported_user_id' => 'user-456',
            'reason' => 'Contenido inapropiado',
            'status' => MusicianReport::STATUS_RESOLVED,
            'action_taken' => MusicianReport::ACTION_HIDDEN,
            'reported_at' => now(),
        ]);

        $this->assertCount(1, MusicianReport::pending()->get());
        $this->assertCount(1, MusicianReport::resolved()->get());
        $this->assertEquals($pending->id, MusicianReport::pending()->first()->id);
    }

    public function test_sync_from_firestore_imports_new_reports(): void
    {
        $mockDoc = Mockery::mock(DocumentSnapshot::class);
        $mockDoc->shouldReceive('id')->andReturn('doc-rep-99');
        $mockDoc->shouldReceive('data')->andReturn([
            'reportedUserId' => 'musician-abc',
            'reporterUserId' => 'reporter-xyz',
            'reason' => 'Perfil fraudulento',
            'details' => 'Dice ser profesor pero envía enlaces raros',
            'timestamp' => 1730379600000,
        ]);

        $mockCollection = Mockery::mock(CollectionReference::class);
        $mockCollection->shouldReceive('documents')->once()->andReturn([$mockDoc]);

        $mockClient = Mockery::mock(FirestoreClient::class);
        $mockClient->shouldReceive('collection')->with('reports_musicians')->once()->andReturn($mockCollection);

        $mockFactory = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $gateway = new FirestoreMusicianReportGateway($mockFactory);
        $imported = $gateway->syncFromFirestore();

        $this->assertEquals(1, $imported);
        $this->assertDatabaseHas('musician_reports', [
            'firestore_id' => 'doc-rep-99',
            'reported_user_id' => 'musician-abc',
            'reporter_user_id' => 'reporter-xyz',
            'reason' => 'Perfil fraudulento',
            'status' => MusicianReport::STATUS_PENDING,
        ]);

        // Second run with same document must not duplicate
        $mockCollection2 = Mockery::mock(CollectionReference::class);
        $mockCollection2->shouldReceive('documents')->once()->andReturn([$mockDoc]);
        $mockClient2 = Mockery::mock(FirestoreClient::class);
        $mockClient2->shouldReceive('collection')->with('reports_musicians')->once()->andReturn($mockCollection2);
        $mockFactory2 = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory2->shouldReceive('make')->once()->andReturn($mockClient2);

        $gateway2 = new FirestoreMusicianReportGateway($mockFactory2);
        $importedSecond = $gateway2->syncFromFirestore();
        $this->assertEquals(0, $importedSecond);
    }

    public function test_hide_reported_musician_updates_directory(): void
    {
        $mockDocRef = Mockery::mock(DocumentReference::class);
        $mockDocRef->shouldReceive('set')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['isVisible']) && $data['isVisible'] === false && isset($data['updatedAt']);
            }), ['merge' => true])
            ->andReturn([]);

        $mockCollection = Mockery::mock(CollectionReference::class);
        $mockCollection->shouldReceive('document')->with('musician-777')->once()->andReturn($mockDocRef);

        $mockClient = Mockery::mock(FirestoreClient::class);
        $mockClient->shouldReceive('collection')->with('musicians_directory')->once()->andReturn($mockCollection);

        $mockFactory = Mockery::mock(FirestoreClientFactory::class);
        $mockFactory->shouldReceive('make')->once()->andReturn($mockClient);

        $gateway = new FirestoreMusicianReportGateway($mockFactory);
        $success = $gateway->hideReportedMusician('musician-777');

        $this->assertTrue($success);
    }
}
