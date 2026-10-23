<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicGenres;

use App\Filament\Resources\MusicGenres\Pages\CreateMusicGenre;
use App\Filament\Resources\MusicGenres\Pages\EditMusicGenre;
use App\Filament\Resources\MusicGenres\Pages\ListMusicGenres;
use App\Filament\Resources\MusicGenres\Schemas\MusicGenreForm;
use App\Filament\Resources\MusicGenres\Tables\MusicGenresTable;
use App\Models\MusicGenre;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MusicGenreResource extends Resource
{
    protected static ?string $model = MusicGenre::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogo';
    }

    public static function getNavigationLabel(): string
    {
        return 'Géneros Musicales';
    }

    public static function getModelLabel(): string
    {
        return 'género musical';
    }

    public static function getPluralModelLabel(): string
    {
        return 'géneros musicales';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return MusicGenreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MusicGenresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMusicGenres::route('/'),
            'create' => CreateMusicGenre::route('/create'),
            'edit' => EditMusicGenre::route('/{record}/edit'),
        ];
    }
}
