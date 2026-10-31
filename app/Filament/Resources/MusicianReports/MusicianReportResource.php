<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicianReports;

use App\Filament\Resources\MusicianReports\Pages\EditMusicianReport;
use App\Filament\Resources\MusicianReports\Pages\ListMusicianReports;
use App\Filament\Resources\MusicianReports\Pages\ViewMusicianReport;
use App\Filament\Resources\MusicianReports\Schemas\MusicianReportForm;
use App\Filament\Resources\MusicianReports\Tables\MusicianReportsTable;
use App\Models\MusicianReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MusicianReportResource extends Resource
{
    protected static ?string $model = MusicianReport::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    public static function getNavigationGroup(): ?string
    {
        return 'Directorio de Músicos';
    }

    public static function getNavigationLabel(): string
    {
        return 'Denuncias de Perfiles';
    }

    public static function getModelLabel(): string
    {
        return 'denuncia';
    }

    public static function getPluralModelLabel(): string
    {
        return 'denuncias de músicos';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = MusicianReport::pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return MusicianReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MusicianReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMusicianReports::route('/'),
            'view' => ViewMusicianReport::route('/{record}'),
            'edit' => EditMusicianReport::route('/{record}/edit'),
        ];
    }
}
