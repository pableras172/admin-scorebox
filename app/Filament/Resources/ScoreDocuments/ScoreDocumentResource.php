<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScoreDocuments;

use App\Filament\Resources\ScoreDocuments\Pages\CreateScoreDocument;
use App\Filament\Resources\ScoreDocuments\Pages\EditScoreDocument;
use App\Filament\Resources\ScoreDocuments\Pages\ListScoreDocuments;
use App\Filament\Resources\ScoreDocuments\Schemas\ScoreDocumentForm;
use App\Filament\Resources\ScoreDocuments\Tables\ScoreDocumentsTable;
use App\Models\ScoreDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ScoreDocumentResource extends Resource
{
    protected static ?string $model = ScoreDocument::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-musical-note';

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogo';
    }

    public static function getNavigationLabel(): string
    {
        return 'Partituras y Documentos';
    }

    public static function getModelLabel(): string
    {
        return 'partitura';
    }

    public static function getPluralModelLabel(): string
    {
        return 'partituras y documentos';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ScoreDocument::active()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return ScoreDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScoreDocumentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScoreDocuments::route('/'),
            'create' => CreateScoreDocument::route('/create'),
            'edit' => EditScoreDocument::route('/{record}/edit'),
        ];
    }
}
