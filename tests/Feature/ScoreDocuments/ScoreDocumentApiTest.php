<?php

declare(strict_types=1);

namespace Tests\Feature\ScoreDocuments;

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
}
