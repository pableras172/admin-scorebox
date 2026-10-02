<?php

declare(strict_types=1);

namespace Tests\Feature\Announcements;

use App\Filament\Resources\AppAnnouncements\AppAnnouncementResource;
use App\Models\AppAnnouncement;
use App\Models\User;
use App\Services\Firestore\FirestoreAppAnnouncementGateway;
use App\Services\Firestore\FirestoreResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class AppAnnouncementManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create();
    }

    public function test_it_creates_app_announcement(): void
    {
        $announcement = AppAnnouncement::factory()->create([
            'title' => 'Nuevas Funcionalidades 2.5',
            'message' => 'Ahora puedes sincronizar partituras en la nube.',
            'type' => AppAnnouncement::TYPE_SUCCESS,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('app_announcements', [
            'id' => $announcement->id,
            'title' => 'Nuevas Funcionalidades 2.5',
            'type' => 'success',
            'is_active' => false,
        ]);
    }

    public function test_activating_announcement_deactivates_others_and_syncs_to_firestore(): void
    {
        $mockGateway = Mockery::mock(FirestoreAppAnnouncementGateway::class);
        $mockGateway->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return $payload['enabled'] === true
                    && $payload['title'] === 'Banner Especial'
                    && $payload['type'] === AppAnnouncement::TYPE_PROMO;
            }))
            ->andReturn(FirestoreResult::success());

        $this->app->instance(FirestoreAppAnnouncementGateway::class, $mockGateway);

        $previous = AppAnnouncement::factory()->create([
            'title' => 'Banner Antiguo',
            'is_active' => true,
        ]);

        $new = AppAnnouncement::factory()->create([
            'title' => 'Banner Especial',
            'type' => AppAnnouncement::TYPE_PROMO,
            'is_active' => false,
        ]);

        $result = $new->activateAndSync();

        $this->assertTrue($result);
        $this->assertFalse($previous->fresh()->is_active);
        $this->assertTrue($new->fresh()->is_active);
        $this->assertNotNull($new->fresh()->synced_to_firestore_at);
    }

    public function test_deactivating_announcement_syncs_to_firestore(): void
    {
        $mockGateway = Mockery::mock(FirestoreAppAnnouncementGateway::class);
        $mockGateway->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (array $payload): bool {
                return $payload['enabled'] === false
                    && $payload['title'] === 'Banner a apagar';
            }))
            ->andReturn(FirestoreResult::success());

        $this->app->instance(FirestoreAppAnnouncementGateway::class, $mockGateway);

        $announcement = AppAnnouncement::factory()->create([
            'title' => 'Banner a apagar',
            'is_active' => true,
        ]);

        $result = $announcement->deactivateAndSync();

        $this->assertTrue($result);
        $this->assertFalse($announcement->fresh()->is_active);
    }

    public function test_it_imports_announcement_from_firestore(): void
    {
        $mockGateway = Mockery::mock(FirestoreAppAnnouncementGateway::class);
        $mockGateway->shouldReceive('get')
            ->once()
            ->andReturn([
                'enabled' => true,
                'title' => 'Aviso en Firestore',
                'message' => 'Contenido recuperado',
                'type' => 'ad',
                'image_url' => 'https://example.com/banner.jpg',
                'action_text' => 'Comprar',
                'action_url' => 'https://example.com/shop',
                'hide_for_pro' => true,
                'updated_at' => now()->toIso8601String(),
            ]);

        $this->app->instance(FirestoreAppAnnouncementGateway::class, $mockGateway);

        $imported = AppAnnouncement::importFromFirestore();

        $this->assertNotNull($imported);
        $this->assertEquals('Aviso en Firestore', $imported->title);
        $this->assertEquals('Contenido recuperado', $imported->message);
        $this->assertEquals('ad', $imported->type);
        $this->assertTrue($imported->hide_for_pro);
        $this->assertTrue($imported->is_active);
    }

    public function test_it_renders_filament_announcements_list_page(): void
    {
        AppAnnouncement::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)->get(AppAnnouncementResource::getUrl('index'));

        $response->assertSuccessful();
    }
}
