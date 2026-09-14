<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailUnsubscribes;

use App\Filament\Resources\EmailUnsubscribes\Pages\ListEmailUnsubscribes;
use App\Filament\Resources\EmailUnsubscribes\Tables\EmailUnsubscribesTable;
use App\Models\EmailUnsubscribe;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmailUnsubscribeResource extends Resource
{
    protected static ?string $model = EmailUnsubscribe::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserMinus;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getNavigationGroup(): ?string
    {
        return 'Marketing';
    }

    public static function getNavigationLabel(): string
    {
        return 'Bajas de Marketing';
    }

    public static function getModelLabel(): string
    {
        return 'baja de marketing';
    }

    public static function getPluralModelLabel(): string
    {
        return 'bajas de marketing';
    }

    public static function table(Table $table): Table
    {
        return EmailUnsubscribesTable::configure($table);
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
            'index' => ListEmailUnsubscribes::route('/'),
        ];
    }
}

