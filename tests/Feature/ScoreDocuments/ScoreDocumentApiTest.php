<?php

declare(strict_types=1);

namespace Tests\Feature\ScoreDocuments;

use App\Models\Author;
use App\Models\BandType;
use App\Models\MusicGenre;
use App\Models\ScoreDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ScoreDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.scorebox.api_key' => 'test-secret-key-123']);
    }

    public function test_it_rejects_unauthorized_requests_without_valid_api_key(): void
    {
        $response = $this->getJson('/api/v1/scores');
        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
        ]);

        $invalidResponse = $this->getJson('/api/v1/scores', [
            'X-ScoreBox-Api-Key' => 'wrong-key',
        ]);
        $invalidResponse->assertStatus(401);
    }

    public function test_it_authorizes_requests_with_valid_api_key_header(): void
    {
        $response = $this->getJson('/api/v1/scores', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    public function test_it_lists_active_scores_and_excludes_inactive(): void
    {
        ScoreDocument::factory()->create([
            'title' => 'Canon en Re',
            'is_active' => true,
        ]);
        ScoreDocument::factory()->create([
            'title' => 'Air on the G String',
            'is_active' => true,
        ]);
        ScoreDocument::factory()->inactive()->create([
            'title' => 'Obra Oculta',
        ]);

        $response = $this->getJson('/api/v1/scores', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Canon en Re', $titles);
        $this->assertContains('Air on the G String', $titles);
        $this->assertNotContains('Obra Oculta', $titles);
    }

    public function test_it_filters_scores_by_search_term_and_instrument(): void
    {
        ScoreDocument::factory()->create([
            'title' => 'Concierto de Clarinete',
            'composer' => 'Mozart',
            'instrument' => 'Clarinete',
            'genre' => 'Clásica',
        ]);
        ScoreDocument::factory()->create([
            'title' => 'Claro de Luna',
            'composer' => 'Debussy',
            'instrument' => 'Piano',
            'genre' => 'Impresionismo',
        ]);

        // Search by instrument
        $instrumentResponse = $this->getJson('/api/v1/scores?instrument=Clarinete', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $instrumentResponse->assertStatus(200);
        $instrumentResponse->assertJsonCount(1, 'data');
        $this->assertSame('Concierto de Clarinete', $instrumentResponse->json('data.0.title'));

        // Search term in composer
        $searchResponse = $this->getJson('/api/v1/scores?search=Debussy', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonCount(1, 'data');
        $this->assertSame('Claro de Luna', $searchResponse->json('data.0.title'));
    }

    public function test_it_filters_scores_by_genre_and_band_type(): void
    {
        $pasodoble = MusicGenre::create(['name' => 'Pasodoble', 'slug' => 'pasodoble']);
        $banda = BandType::create(['name' => 'Banda de Música', 'slug' => 'banda-de-musica']);
        $orquesta = BandType::create(['name' => 'Orquesta Sinfónica', 'slug' => 'orquesta-sinfonica']);

        $score1 = ScoreDocument::factory()->create([
            'title' => 'Amparito Roca',
            'music_genre_id' => $pasodoble->id,
            'band_type_id' => $banda->id,
        ]);
        $score2 = ScoreDocument::factory()->create([
            'title' => 'Sinfonía 5',
            'music_genre_id' => null,
            'band_type_id' => $orquesta->id,
        ]);

        // Filter by genre
        $genreResponse = $this->getJson('/api/v1/scores?genre=pasodoble', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $genreResponse->assertStatus(200);
        $genreResponse->assertJsonCount(1, 'data');
        $this->assertSame($score1->id, $genreResponse->json('data.0.id'));

        // Filter by band_type
        $bandResponse = $this->getJson('/api/v1/scores?band_type=banda-de-musica', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $bandResponse->assertStatus(200);
        $bandResponse->assertJsonCount(1, 'data');
        $this->assertSame($score1->id, $bandResponse->json('data.0.id'));
    }

    public function test_it_respects_publication_dates(): void
    {
        // Future score (not yet published)
        ScoreDocument::factory()->create([
            'title' => 'Obra Futura',
            'published_at' => now()->addDays(5),
        ]);
        // Past expired score
        ScoreDocument::factory()->create([
            'title' => 'Obra Caducada',
            'publication_end_at' => now()->subDay(),
        ]);
        // Currently published score
        $current = ScoreDocument::factory()->create([
            'title' => 'Obra Publicada Ahora',
            'published_at' => now()->subDays(2),
            'publication_end_at' => now()->addDays(10),
            'price' => '15.50',
        ]);

        $response = $this->getJson('/api/v1/scores', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertSame($current->id, $response->json('data.0.id'));
        $this->assertSame('15.50', $response->json('data.0.price'));
    }

    public function test_it_returns_genres_and_band_types_lists(): void
    {
        MusicGenre::create(['name' => 'Marcha Procesional', 'slug' => 'marcha-procesional']);
        BandType::create(['name' => 'Charanga', 'slug' => 'charanga']);

        $genreResponse = $this->getJson('/api/v1/genres', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $genreResponse->assertStatus(200);
        $genreResponse->assertJson(['success' => true]);
        $this->assertNotEmpty($genreResponse->json('data'));

        $bandResponse = $this->getJson('/api/v1/band-types', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $bandResponse->assertStatus(200);
        $bandResponse->assertJson(['success' => true]);
        $this->assertNotEmpty($bandResponse->json('data'));
    }

    public function test_it_returns_single_score_details(): void
    {
        $score = ScoreDocument::factory()->create([
            'title' => 'La Madrugá',
            'composer' => 'Abel Moreno',
            'instrument' => 'Banda de Música',
            'genre' => 'Marcha Procesional',
            'is_premium_only' => true,
        ]);

        $response = $this->getJson("/api/v1/scores/{$score->id}", [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $score->id,
                'title' => 'La Madrugá',
                'composer' => 'Abel Moreno',
                'is_premium_only' => true,
            ],
        ]);
    }

    public function test_it_returns_404_for_non_existent_score(): void
    {
        $response = $this->getJson('/api/v1/scores/999999', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_it_downloads_score_file_and_increments_counter(): void
    {
        Storage::fake('local');
        $filePath = 'score_documents/test_partitura.pdf';
        Storage::disk('local')->put($filePath, 'PDF-DUMMY-CONTENT');

        $score = ScoreDocument::factory()->create([
            'title' => 'Partitura Descargable',
            'file_path' => $filePath,
            'file_name' => 'partitura.pdf',
            'mime_type' => 'application/pdf',
            'downloads_count' => 0,
            'is_premium_only' => false,
        ]);

        $response = $this->get("/api/v1/scores/{$score->id}/download", [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $score->fresh()->downloads_count);
    }

    public function test_it_blocks_non_premium_user_when_score_is_premium_only(): void
    {
        Storage::fake('local');
        $filePath = 'score_documents/test_premium.pdf';
        Storage::disk('local')->put($filePath, 'PREMIUM-CONTENT');

        $score = ScoreDocument::factory()->premium()->create([
            'title' => 'Obra Exclusiva PRO',
            'file_path' => $filePath,
        ]);

        // Attempt download claiming not premium
        $forbiddenResponse = $this->get("/api/v1/scores/{$score->id}/download", [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
            'X-User-Is-Premium' => 'false',
        ]);
        $forbiddenResponse->assertStatus(403);
        $forbiddenResponse->assertJson([
            'success' => false,
        ]);

        // Successful download with premium header
        $successResponse = $this->get("/api/v1/scores/{$score->id}/download", [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
            'X-User-Is-Premium' => 'true',
        ]);
        $successResponse->assertStatus(200);
        $this->assertSame(1, $score->fresh()->downloads_count);
    }

    public function test_it_filters_and_returns_scores_by_google_play_sku(): void
    {
        $scoreWithSku = ScoreDocument::factory()->create([
            'title' => 'Partitura con SKU',
            'sku' => 'score_play_store_001',
        ]);
        ScoreDocument::factory()->create([
            'title' => 'Otra Partitura',
            'sku' => 'score_other_002',
        ]);

        $response = $this->getJson('/api/v1/scores?sku=score_play_store_001', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertSame('score_play_store_001', $response->json('data.0.sku'));
        $this->assertSame($scoreWithSku->id, $response->json('data.0.id'));
    }

    public function test_it_includes_author_relation_and_composer_fallback_in_score_endpoints(): void
    {
        $author = Author::factory()->create([
            'name' => 'Manuel de Falla',
            'country' => 'España',
            'birth_year' => 1876,
            'death_year' => 1946,
            'website_url' => 'https://manueldefalla.com',
        ]);

        $scoreWithAuthor = ScoreDocument::factory()->create([
            'title' => 'El Amor Brujo',
            'author_id' => $author->id,
            'composer' => null,
        ]);

        $scoreWithoutAuthor = ScoreDocument::factory()->create([
            'title' => 'Obra Popular Tradicional',
            'author_id' => null,
            'composer' => 'Tradicional / Anónimo',
        ]);

        $response = $this->getJson('/api/v1/scores', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);

        $response->assertStatus(200);

        $data = collect($response->json('data'));
        $fallaItem = $data->firstWhere('id', $scoreWithAuthor->id);
        $this->assertNotNull($fallaItem);
        $this->assertSame('Manuel de Falla', $fallaItem['composer']);
        $this->assertSame($author->id, $fallaItem['author_id']);
        $this->assertSame('España', $fallaItem['author']['country']);
        $this->assertSame(1876, $fallaItem['author']['birth_year']);

        $anonItem = $data->firstWhere('id', $scoreWithoutAuthor->id);
        $this->assertNotNull($anonItem);
        $this->assertSame('Tradicional / Anónimo', $anonItem['composer']);
        $this->assertNull($anonItem['author']);
    }

    public function test_it_filters_scores_by_author_id_and_search(): void
    {
        $authorA = Author::factory()->create(['name' => 'Isaac Albéniz', 'slug' => 'isaac-albeniz']);
        $authorB = Author::factory()->create(['name' => 'Enrique Granados', 'slug' => 'enrique-granados']);

        $scoreA = ScoreDocument::factory()->create([
            'title' => 'Asturias (Leyenda)',
            'author_id' => $authorA->id,
        ]);
        $scoreB = ScoreDocument::factory()->create([
            'title' => 'Danzas Españolas',
            'author_id' => $authorB->id,
        ]);

        // Filter by author_id
        $respA = $this->getJson("/api/v1/scores?author_id={$authorA->id}", [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $respA->assertStatus(200);
        $respA->assertJsonCount(1, 'data');
        $this->assertSame($scoreA->id, $respA->json('data.0.id'));

        // Search by author name
        $respSearch = $this->getJson('/api/v1/scores?search=Granados', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $respSearch->assertStatus(200);
        $respSearch->assertJsonCount(1, 'data');
        $this->assertSame($scoreB->id, $respSearch->json('data.0.id'));
    }

    public function test_it_lists_active_authors_and_retrieves_author_detail_with_scores(): void
    {
        $author = Author::factory()->create([
            'name' => 'Joaquín Rodrigo',
            'country' => 'España',
            'is_active' => true,
        ]);

        $inactiveAuthor = Author::factory()->inactive()->create([
            'name' => 'Autor No Publicado',
        ]);

        $score = ScoreDocument::factory()->create([
            'title' => 'Concierto de Aranjuez',
            'author_id' => $author->id,
            'is_active' => true,
        ]);

        // List authors
        $listResp = $this->getJson('/api/v1/authors', [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $listResp->assertStatus(200);
        $authorNames = collect($listResp->json('data'))->pluck('name')->all();
        $this->assertContains('Joaquín Rodrigo', $authorNames);
        $this->assertNotContains('Autor No Publicado', $authorNames);

        // Author detail with scores
        $detailResp = $this->getJson("/api/v1/authors/{$author->id}", [
            'X-ScoreBox-Api-Key' => 'test-secret-key-123',
        ]);
        $detailResp->assertStatus(200);
        $detailResp->assertJson([
            'success' => true,
            'data' => [
                'id' => $author->id,
                'name' => 'Joaquín Rodrigo',
                'country' => 'España',
                'scores' => [
                    [
                        'id' => $score->id,
                        'title' => 'Concierto de Aranjuez',
                    ],
                ],
            ],
        ]);
    }
}
