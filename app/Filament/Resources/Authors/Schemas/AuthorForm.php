<?php

declare(strict_types=1);

namespace App\Filament\Resources\Authors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuthorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Autor / Compositor')
                    ->description('Datos personales, biografía e imagen del compositor.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre Completo / Artístico')
                            ->placeholder('Ej: Ludwig van Beethoven, Manuel de Falla, Hans Zimmer...')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->placeholder('Se genera automáticamente si se deja vacío')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('country')
                            ->label('País / Nacionalidad')
                            ->placeholder('Ej: España, Alemania, Austria, Italia...')
                            ->maxLength(100),

                        FileUpload::make('photo_path')
                            ->label('Fotografía o Retrato')
                            ->image()
                            ->disk('public')
                            ->directory('authors')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('600')
                            ->imageResizeTargetHeight('600')
                            ->maxSize(10240)
                            ->columnSpanFull(),

                        TextInput::make('birth_year')
                            ->label('Año de Nacimiento')
                            ->numeric()
                            ->placeholder('Ej: 1876')
                            ->minValue(1000)
                            ->maxValue(2100),

                        TextInput::make('death_year')
                            ->label('Año de Fallecimiento')
                            ->numeric()
                            ->placeholder('Ej: 1946 (dejar vacío si vive)')
                            ->minValue(1000)
                            ->maxValue(2100)
                            ->helperText('Útil para determinar si sus obras están en dominio público.'),

                        Textarea::make('bio')
                            ->label('Biografía / Trayectoria')
                            ->placeholder('Reseña biográfica, obras destacadas, formación, estilo...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Enlaces y Presencia Digital')
                    ->description('Páginas web, redes sociales y plataformas de streaming.')
                    ->schema([
                        TextInput::make('website_url')
                            ->label('Página Web Oficial')
                            ->placeholder('https://...')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('spotify_url')
                            ->label('Spotify')
                            ->placeholder('https://open.spotify.com/artist/...')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('youtube_url')
                            ->label('YouTube')
                            ->placeholder('https://youtube.com/@...')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('instagram_url')
                            ->label('Instagram')
                            ->placeholder('https://instagram.com/...')
                            ->url()
                            ->maxLength(255),

                        KeyValue::make('social_links')
                            ->label('Otros Enlaces / Redes')
                            ->keyLabel('Plataforma')
                            ->valueLabel('URL')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Control de Visibilidad')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->helperText('Visible en la app móvil y en los selectores de partituras.')
                            ->default(true),

                        Toggle::make('is_featured')
                            ->label('Destacado')
                            ->helperText('Mostrar de forma destacada en la sección de autores de la app.')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}
