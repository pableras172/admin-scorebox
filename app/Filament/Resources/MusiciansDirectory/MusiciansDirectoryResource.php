<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusiciansDirectory;

use App\Filament\Resources\MusiciansDirectory\Pages\ListMusiciansDirectory;
use App\Filament\Resources\MusiciansDirectory\Pages\ViewMusicianProfile;
use App\Filament\Resources\MusiciansDirectory\Schemas\MusicianProfileForm;
use App\Filament\Resources\MusiciansDirectory\Tables\MusiciansDirectoryTable;
use App\Models\FirestoreMusicianProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MusiciansDirectoryResource extends Resource
{
    protected static ?string $model = FirestoreMusicianProfile::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?string $recordTitleAttribute = 'displayName';

    protected static ?string $slug = 'musicians-directory';

    public static function getNavigationGroup(): ?string
    {
        return 'Directorio de Músicos';
    }

    public static function getNavigationLabel(): string
    {
        return 'Directorio Global';
    }

    public static function getModelLabel(): string
    {
        return 'músico del directorio';
    }

    public static function getPluralModelLabel(): string
    {
        return 'músicos del directorio';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return MusicianProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MusiciansDirectoryTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMusiciansDirectory::route('/'),
            'view' => ViewMusicianProfile::route('/{record}'),
        ];
    }
}
