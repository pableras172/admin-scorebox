<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BandType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BandType>
 */
class BandTypeFactory extends Factory
{
    protected $model = BandType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Banda de Música',
            'Banda Sinfónica',
            'Orquesta Sinfónica',
            'Charanga / Fanfarria',
            'Agrupación Musical',
            'Banda de Cornetas y Tambores',
            'Big Band',
            'Música de Cámara / Cuarteto',
            'Solista con Acompañamiento',
            'Coro',
        ]).' '.$this->faker->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
