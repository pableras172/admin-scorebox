<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailTemplates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plantilla')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->color('gray'),

                TextColumn::make('editor_mode')
                    ->label('Modo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'code' => 'HTML',
                        default => 'Visual',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'code' => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
