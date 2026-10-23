<?php

declare(strict_types=1);

namespace App\Filament\Resources\BandTypes;

use App\Filament\Resources\BandTypes\Pages\CreateBandType;
use App\Filament\Resources\BandTypes\Pages\EditBandType;
use App\Filament\Resources\BandTypes\Pages\ListBandTypes;
use App\Filament\Resources\BandTypes\Schemas\BandTypeForm;
use App\Filament\Resources\BandTypes\Tables\BandTypesTable;
use App\Models\BandType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class BandTypeResource extends Resource
{
    protected static ?string $model = BandType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function getNavigationGroup(): ?string
    {
        return 'Catálogo';
    }

    public static function getNavigationLabel(): string
    {
        return 'Tipos de Banda / Agrupación';
    }

    public static function getModelLabel(): string
    {
        return 'tipo de banda';
    }

    public static function getPluralModelLabel(): string
    {
        return 'tipos de banda';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return BandTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BandTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBandTypes::route('/'),
            'create' => CreateBandType::route('/create'),
            'edit' => EditBandType::route('/{record}/edit'),
        ];
    }
}
