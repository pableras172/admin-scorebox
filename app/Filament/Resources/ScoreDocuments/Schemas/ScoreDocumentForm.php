<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScoreDocuments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScoreDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Archivo de la Partitura / Documento')
                    ->description('Sube el archivo en formato PDF, MusicXML, ZIP o audio para su distribución en la app.')
                    ->schema([
                        FileUpload::make('file_path')
                            ->label('Archivo del Documento')
                            ->disk('local')
                            ->directory('score_documents')
                            ->visibility('private')
                            ->preserveFilenames()
                            ->storeFileNamesIn('file_name')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/xml',
                                'text/xml',
                                'application/vnd.recordare.musicxml+xml',
                                'audio/midi',
                                'audio/x-midi',
                                'application/zip',
                                'application/x-zip-compressed',
                                'application/octet-stream',
                            ])
                            ->maxSize(51200) // 50MB
                            ->downloadable()
                            ->openable()
                            ->required(),

                        Hidden::make('file_name'),
                    ]),

                Section::make('Metadatos Musicales')
                    ->description('Detalles identificativos que permitirán a los músicos buscar y filtrar esta obra en la app.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título de la Obra')
                            ->placeholder('Ej: Canon en Re Mayor, La Madrugá, El Concierto de Aranjuez...')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('sku')
                            ->label('SKU / ID Producto Google Play')
                            ->placeholder('Ej: score_canon_in_d o score_001')
                            ->helperText('Identificador único para compras in-app en Google Play Store (opcional).')
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        TextInput::make('composer')
                            ->label('Compositor / Arreglista')
                            ->placeholder('Ej: Johann Pachelbel, Abel Moreno, Joaquín Rodrigo...')
                            ->maxLength(255),

                        Select::make('music_genre_id')
                            ->label('Género Musical')
                            ->relationship('musicGenre', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nombre del Género')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(2),
                            ]),

                        Select::make('band_type_id')
                            ->label('Tipo de Banda / Agrupación')
                            ->relationship('bandType', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Tipo de Agrupación')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(2),
                            ]),

                        TextInput::make('instrument')
                            ->label('Instrumentación / Papel')
                            ->placeholder('Ej: Clarinete 1º, Guion General, Cuarteto de Saxofones...')
                            ->maxLength(255),

                        Select::make('difficulty')
                            ->label('Dificultad')
                            ->options([
                                'easy' => '🟢 Fácil / Principiante',
                                'medium' => '🟡 Intermedio',
                                'hard' => '🔴 Avanzado / Profesional',
                            ])
                            ->native(false)
                            ->nullable(),

                        Textarea::make('description')
                            ->label('Notas adicionales / Descripción')
                            ->placeholder('Detalles de la edición, partes incluidas (guion, particellas), tonalidad...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Control de Publicación y Condiciones')
                    ->description('Configura la visibilidad en el catálogo, fechas de programación y condiciones económicas.')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Publicado y disponible en la App')
                            ->helperText('Si se desactiva, la obra no aparecerá en las búsquedas ni en los listados de la app móvil.')
                            ->default(true),

                        Toggle::make('is_premium_only')
                            ->label('Exclusivo para usuarios PRO')
                            ->helperText('Si se marca, solo los usuarios con suscripción PRO activa tendrán permiso para descargar el archivo.')
                            ->default(false),

                        TextInput::make('price')
                            ->label('Precio (€)')
                            ->numeric()
                            ->prefix('€')
                            ->placeholder('0.00')
                            ->helperText('Opcional. Reservado para venta o suscripciones especiales.'),

                        DateTimePicker::make('published_at')
                            ->label('Fecha Inicio Publicación')
                            ->native(false)
                            ->seconds(false)
                            ->helperText('Si se programa para el futuro, no estará visible hasta esa fecha.'),

                        DateTimePicker::make('publication_end_at')
                            ->label('Fecha Fin Publicación')
                            ->native(false)
                            ->seconds(false)
                            ->helperText('Opcional. Pasada esta fecha, la obra se ocultará automáticamente.'),
                    ])
                    ->columns(2),
            ]);
    }
}
