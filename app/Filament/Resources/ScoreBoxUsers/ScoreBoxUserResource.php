<?php

namespace App\Filament\Resources\ScoreBoxUsers;

use App\Filament\Resources\ScoreBoxUsers\Pages\ListScoreBoxUsers;
use App\Filament\Resources\ScoreBoxUsers\Pages\ViewScoreBoxUser;
use App\Filament\Resources\ScoreBoxUsers\Schemas\ScoreBoxUserForm;
use App\Filament\Resources\ScoreBoxUsers\Tables\ScoreBoxUsersTable;
use App\Models\FirestoreUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScoreBoxUserResource extends Resource
{
    protected static ?string $model = FirestoreUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'displayName';

    public static function getNavigationGroup(): ?string
    {
        return 'Usuarios scorebox';
    }

    public static function getNavigationLabel(): string
    {
        return 'Firestore Users';
    }

    public static function getModelLabel(): string
    {
        return 'usuario Firestore';
    }

    public static function getPluralModelLabel(): string
    {
        return 'usuarios Firestore';
    }

    public static function form(Schema $schema): Schema
    {
        return ScoreBoxUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScoreBoxUsersTable::configure($table);
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
            'index' => ListScoreBoxUsers::route('/'),
            'view' => ViewScoreBoxUser::route('/{record}'),
        ];
    }
}
