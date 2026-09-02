<?php

namespace Tests\Feature;

use App\Filament\Resources\ScoreBoxUsers\Tables\ScoreBoxUsersTable;
use App\Models\FirestoreUser;
use Tests\TestCase;

class ScoreBoxUsersTableSearchTest extends TestCase
{
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
}
