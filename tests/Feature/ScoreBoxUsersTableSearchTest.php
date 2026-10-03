<?php

namespace Tests\Feature;

use App\Filament\Resources\ScoreBoxUsers\Pages\ViewScoreBoxUser;
use App\Filament\Resources\ScoreBoxUsers\Tables\ScoreBoxUsersTable;
use App\Models\FirestoreUser;
use App\Models\PromotionalCode;
use App\Models\User;
use App\Services\Firestore\FirestoreResult;
use App\Services\Firestore\FirestoreUserGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ScoreBoxUsersTableSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_search_matches_display_name_or_email(): void
    {
        $users = collect([
            new FirestoreUser(['uid' => 'u1', 'displayName' => 'Ana García', 'email' => 'ana@example.com']),
            new FirestoreUser(['uid' => 'u2', 'displayName' => 'Pedro Ruiz', 'email' => 'pedro@example.com']),
            new FirestoreUser(['uid' => 'u3', 'displayName' => 'Lucía', 'email' => 'lucia@other.com']),
        ]);

        $matchedByName = ScoreBoxUsersTable::filterUsersForSearch($users, 'garcía');
        $this->assertSame(['u1'], $matchedByName->pluck('uid')->all());

        $matchedByEmail = ScoreBoxUsersTable::filterUsersForSearch($users, 'pedro@example.com');
        $this->assertSame(['u2'], $matchedByEmail->pluck('uid')->all());

        $matchedCaseInsensitive = ScoreBoxUsersTable::filterUsersForSearch($users, 'LUCIA');
        $this->assertSame(['u3'], $matchedCaseInsensitive->pluck('uid')->all());
    }

    public function test_predefined_filters_reduce_the_visible_users(): void
    {
        $users = collect([
            new FirestoreUser(['uid' => 'u1', 'email' => 'ana@example.com', 'displayName' => 'Ana', 'country' => 'España', 'studyType' => 'Grado', 'isPremium' => true]),
            new FirestoreUser(['uid' => 'u2', 'email' => 'pedro@example.com', 'displayName' => 'Pedro', 'country' => 'México', 'studyType' => 'Máster', 'isPremium' => false]),
            new FirestoreUser(['uid' => 'u3', 'email' => 'lucia@example.com', 'displayName' => 'Lucía', 'country' => 'España', 'studyType' => 'Doctorado', 'isPremium' => true]),
        ]);

        $filteredByCountry = ScoreBoxUsersTable::filterUsersForFilters($users, ['country' => 'España']);
        $this->assertSame(['u1', 'u3'], $filteredByCountry->pluck('uid')->all());

        $filteredByPremium = ScoreBoxUsersTable::filterUsersForFilters($users, ['isPremium' => true]);
        $this->assertSame(['u1', 'u3'], $filteredByPremium->pluck('uid')->all());

        $filteredByStudyType = ScoreBoxUsersTable::filterUsersForFilters($users, ['studyType' => 'Máster']);
        $this->assertSame(['u2'], $filteredByStudyType->pluck('uid')->all());
    }

    public function test_view_scorebox_user_renders_without_type_error(): void
    {
        $admin = User::factory()->create();
        $mockGateway = Mockery::mock(FirestoreUserGateway::class);
        $mockGateway->shouldReceive('getById')
            ->with('user-123')
            ->andReturn(FirestoreResult::success([
                'uid' => 'user-123',
                'email' => 'musician@example.com',
                'displayName' => 'Carlos Músico',
                'isPremium' => true,
            ]));
        $this->app->instance(FirestoreUserGateway::class, $mockGateway);

        Livewire::actingAs($admin)
            ->test(ViewScoreBoxUser::class, ['record' => 'user-123'])
            ->assertSuccessful()
            ->assertSet('data.displayName', 'Carlos Músico')
            ->assertSee('Detalle del usuario Firestore');
    }

    public function test_view_scorebox_user_renders_with_assigned_promo_code(): void
    {
        $admin = User::factory()->create();
        PromotionalCode::create([
            'code' => 'PROMO-SCORE-123',
            'assigned_email' => 'musician@example.com',
            'assigned_at' => now(),
        ]);

        $mockGateway = Mockery::mock(FirestoreUserGateway::class);
        $mockGateway->shouldReceive('getById')
            ->with('user-123')
            ->andReturn(FirestoreResult::success([
                'uid' => 'user-123',
                'email' => 'musician@example.com',
                'displayName' => 'Carlos Músico',
                'isPremium' => true,
            ]));
        $this->app->instance(FirestoreUserGateway::class, $mockGateway);

        Livewire::actingAs($admin)
            ->test(ViewScoreBoxUser::class, ['record' => 'user-123'])
            ->assertSuccessful()
            ->assertSet('data.assigned_promo_code', function ($val) {
                return str_contains((string) $val, 'PROMO-SCORE-123');
            });
    }
}
