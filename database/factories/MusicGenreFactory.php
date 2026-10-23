<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MusicGenre;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MusicGenre>
 */
class MusicGenreFactory extends Factory
{
    protected $model = MusicGenre::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Marcha Procesional',
            'Pasodoble',
            'Banda Sonora',
            'Música Clásica',
            'Zarzuela',
            'Jazz / Swing',
            'Flamenco',
            'Música Popular / Tradicional',
            'Pop / Rock Sinfónico',
            'Villancico',
        ]).' '.$this->faker->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
