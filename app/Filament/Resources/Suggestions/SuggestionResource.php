<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suggestions;

use App\Filament\Resources\Suggestions\Pages\CreateSuggestion;
use App\Filament\Resources\Suggestions\Pages\EditSuggestion;
use App\Filament\Resources\Suggestions\Pages\ListSuggestions;
use App\Filament\Resources\Suggestions\Schemas\SuggestionForm;
use App\Filament\Resources\Suggestions\Tables\SuggestionsTable;
use App\Models\Suggestion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SuggestionResource extends Resource
{
    protected static ?string $model = Suggestion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    public static function getNavigationGroup(): ?string
    {
        return 'Soporte';
    }

    public static function getNavigationLabel(): string
    {
        return 'Buzón de Sugerencias';
    }

    public static function getModelLabel(): string
    {
        return 'sugerencia';
    }

    public static function getPluralModelLabel(): string
    {
        return 'sugerencias';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Suggestion::where('status', Suggestion::STATUS_NEW)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return SuggestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuggestionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuggestions::route('/'),
            'create' => CreateSuggestion::route('/create'),
            'edit' => EditSuggestion::route('/{record}/edit'),
        ];
    }
}
