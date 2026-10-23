<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScoreDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreDocument>
 */
class ScoreDocumentFactory extends Factory
{
    protected $model = ScoreDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->words(3, true),
            'sku' => 'score_'.$this->faker->unique()->regexify('[a-z0-9_]{10}'),
            'composer' => $this->faker->name(),
            'instrument' => $this->faker->randomElement(['Clarinete', 'Piano', 'Saxofón Alto', 'Trompeta', 'Flauta']),
            'genre' => $this->faker->randomElement(['Clásica', 'Banda / Marchas', 'Pasodoble', 'Jazz']),
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'description' => $this->faker->sentence(),
            'file_path' => 'score_documents/sample_score_'.$this->faker->uuid().'.pdf',
            'file_name' => 'sample_score.pdf',
            'file_size' => 1024 * 100, // 100KB
            'mime_type' => 'application/pdf',
            'is_active' => true,
            'is_premium_only' => false,
            'downloads_count' => 0,
        ];
    }

    public function premium(): static
    {
        return $this->state(fn (): array => [
            'is_premium_only' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
