<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->name().' '.$this->faker->lastName();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'country' => $this->faker->randomElement(['España', 'Alemania', 'Italia', 'Francia', 'Reino Unido', 'Austria']),
            'website_url' => $this->faker->optional(0.7)->url(),
            'instagram_url' => 'https://instagram.com/'.$this->faker->userName(),
            'youtube_url' => 'https://youtube.com/@'.$this->faker->userName(),
            'spotify_url' => 'https://open.spotify.com/artist/'.$this->faker->uuid(),
            'social_links' => [
                'facebook' => 'https://facebook.com/'.$this->faker->userName(),
            ],
            'bio' => $this->faker->paragraphs(2, true),
            'photo_path' => null,
            'birth_year' => $this->faker->numberBetween(1850, 1995),
            'death_year' => null,
            'is_featured' => false,
            'is_active' => true,
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_featured' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
