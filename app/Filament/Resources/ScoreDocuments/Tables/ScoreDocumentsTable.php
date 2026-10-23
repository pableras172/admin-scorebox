<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScoreDocuments\Tables;

use App\Models\ScoreDocument;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScoreDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (ScoreDocument $record): ?string => $record->composer),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->color('gray')
                    ->placeholder('-')
                    ->copyable()
                    ->copyMessage('SKU copiado')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('musicGenre.name')
                    ->label('Género')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('bandType.name')
                    ->label('Tipo de Banda')
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('instrument')
                    ->label('Instrumentación')
                    ->searchable()
                    ->placeholder('General')
                    ->toggleable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('EUR')
                    ->sortable()
                    ->placeholder('Gratis'),

                IconColumn::make('is_premium_only')
                    ->label('Solo PRO')
                    ->boolean()
                    ->alignCenter(),

                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->alignCenter(),

                TextColumn::make('downloads_count')
                    ->label('Descargas')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('formatted_file_size')
                    ->label('Tamaño')
                    ->alignRight(),

                TextColumn::make('published_at')
                    ->label('Inicio Pub.')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('publication_end_at')
                    ->label('Fin Pub.')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Estado de Publicación')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Desactivados'),

                TernaryFilter::make('is_premium_only')
                    ->label('Acceso')
                    ->placeholder('Todos')
                    ->trueLabel('Solo PRO')
                    ->falseLabel('Gratis / Todos'),

                SelectFilter::make('music_genre_id')
                    ->label('Género Musical')
                    ->relationship('musicGenre', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('band_type_id')
                    ->label('Tipo de Banda')
                    ->relationship('bandType', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('difficulty')
                    ->label('Dificultad')
                    ->options([
                        'easy' => 'Fácil',
                        'medium' => 'Intermedio',
                        'hard' => 'Avanzado',
                    ]),
            ])
            ->actions([
                Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function (ScoreDocument $record): ?StreamedResponse {
                        if (! Storage::disk('local')->exists($record->file_path)) {
                            Notification::make()
                                ->title('Archivo no disponible')
                                ->body('El archivo físico no se encuentra en el almacenamiento local.')
                                ->danger()
                                ->send();

                            return null;
                        }

                        return Storage::disk('local')->download($record->file_path, $record->file_name);
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
