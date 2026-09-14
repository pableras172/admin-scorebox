<?php

declare(strict_types=1);

namespace App\Filament\Resources\PromotionalCodes;

use App\Filament\Resources\PromotionalCodes\Pages\ListPromotionalCodes;
use App\Filament\Resources\PromotionalCodes\Tables\PromotionalCodesTable;
use App\Models\PromotionalCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PromotionalCodeResource extends Resource
{
    protected static ?string $model = PromotionalCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationGroup(): ?string
    {
        return 'Marketing';
    }

    public static function getNavigationLabel(): string
    {
        return 'Códigos Promocionales';
    }

    public static function getModelLabel(): string
    {
        return 'código promocional';
    }

    public static function getPluralModelLabel(): string
    {
        return 'códigos promocionales';
    }

    public static function table(Table $table): Table
    {
        return PromotionalCodesTable::configure($table);
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
            'index' => ListPromotionalCodes::route('/'),
        ];
    }
}

