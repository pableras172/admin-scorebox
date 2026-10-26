<?php

declare(strict_types=1);

namespace App\Filament\Resources\Authors\Tables;

use App\Models\Author;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn (): string => 'https://ui-avatars.com/api/?name=Author&background=e2e8f0&color=64748b'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('country')
                    ->label('País')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('lifespan')
                    ->label('Período')
                    ->state(function (Author $record): string {
                        if (! $record->birth_year && ! $record->death_year) {
                            return '-';
                        }

                        return ($record->birth_year ?? '?').' - '.($record->death_year ?? 'presente');
                    })
                    ->color('gray'),

                TextColumn::make('score_documents_count')
                    ->label('Partituras')
                    ->counts('scoreDocuments')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean()
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Desactivados'),

                TernaryFilter::make('is_featured')
                    ->label('Destacado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Destacados')
                    ->falseLabel('No Destacados'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name', 'asc');
    }
}
