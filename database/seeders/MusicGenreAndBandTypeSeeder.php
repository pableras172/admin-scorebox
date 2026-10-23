<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BandType;
use App\Models\MusicGenre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MusicGenreAndBandTypeSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            'Marcha Procesional' => 'Marchas fúnebres, solemnes y de gloria para procesiones.',
            'Pasodoble' => 'Pasodobles de concierto, taurinos y de fiesta.',
            'Banda Sonora' => 'Música de cine, televisión y bandas sonoras originales.',
            'Música Clásica' => 'Obras del repertorio clásico, barroco, romántico y contemporáneo.',
            'Zarzuela' => 'Preludios, coros e intermedios del género lírico español.',
            'Jazz / Swing' => 'Estándares de jazz, swing, blues y dixieland.',
            'Flamenco' => 'Música con raíz flamenca adaptada a agrupaciones musicales.',
            'Música Popular y Tradicional' => 'Himnos, folclore, jotas, marchas militares y festivas.',
            'Pop / Rock Sinfónico' => 'Versiones y arreglos sinfónicos de música moderna.',
            'Villancicos y Navidad' => 'Música navideña tradicional y clásica.',
        ];

        foreach ($genres as $name => $desc) {
            MusicGenre::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'is_active' => true,
                ]
            );
        }

        $bandTypes = [
            'Banda de Música' => 'Banda de plantilla tradicional con maderas, metales y percusión.',
            'Banda Sinfónica' => 'Banda completa incluyendo instrumentos de cuerda (violonchelos y contrabajos).',
            'Orquesta Sinfónica' => 'Orquesta completa con cuerda, viento y percusión.',
            'Charanga / Fanfarria' => 'Agrupación reducida y festiva de calle con metales, saxos y percusión.',
            'Agrupación Musical' => 'Formación cofrade con cornetas, trompetas, trombones, tubas y percusión.',
            'Banda de Cornetas y Tambores' => 'Formación cofrade tradicional de viento metal y percusión.',
            'Big Band' => 'Formación de jazz con secciones de saxofones, trompetas, trombones y ritmo.',
            'Música de Cámara / Ensamble' => 'Dúos, tríos, cuartetos y quintetos de viento o cuerda.',
            'Solista' => 'Obras para instrumento solista con o sin acompañamiento.',
            'Coro' => 'Agrupaciones vocales y polifónicas.',
        ];

        foreach ($bandTypes as $name => $desc) {
            BandType::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $desc,
                    'is_active' => true,
                ]
            );
        }
    }
}
