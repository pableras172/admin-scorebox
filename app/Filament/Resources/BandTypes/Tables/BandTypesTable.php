<?php

declare(strict_types=1);

namespace App\Filament\Resources\BandTypes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BandTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tipo de Agrupación')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('score_documents_count')
                    ->label('Partituras')
                    ->counts('scoreDocuments')
                    ->badge()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name', 'asc');
    }
}
