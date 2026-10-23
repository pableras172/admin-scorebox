<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicGenres\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MusicGenreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Género Musical')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Género')
                            ->placeholder('Ej: Marcha Procesional, Pasodoble, Banda Sonora...')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->placeholder('Se genera automáticamente si se deja vacío')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ]),
            ]);
    }
}
