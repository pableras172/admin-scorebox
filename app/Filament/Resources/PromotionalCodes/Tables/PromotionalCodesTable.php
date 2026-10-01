<?php

declare(strict_types=1);

namespace App\Filament\Resources\PromotionalCodes\Tables;

use App\Models\PromotionalCode;
use App\Services\Marketing\PromotionalCodeImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class PromotionalCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Código Promocional')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (PromotionalCode $record): string => $record->isAssigned() ? 'Asignado' : 'Disponible')
                    ->color(fn (string $state): string => match ($state) {
                        'Disponible' => 'success',
                        'Asignado' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('assigned_email')
                    ->label('Usuario Asignado')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('assigned_uid')
                    ->label('UID Firebase')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('campaign.subject')
                    ->label('Campaña')
                    ->limit(25)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('assigned_at')
                    ->label('Fecha de Asignación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Fecha de Carga')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('availability')
                    ->label('Disponibilidad')
                    ->options([
                        'available' => 'Solo disponibles (sin asignar)',
                        'assigned' => 'Solo asignados',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'available' => $query->whereNull('assigned_email'),
                            'assigned' => $query->whereNotNull('assigned_email'),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([
                Action::make('importCsv')
                    ->label('Importar Códigos (CSV)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->modalHeading('Importar Códigos Promocionales desde CSV')
                    ->modalDescription('Sube el archivo CSV exportado desde Google Play Console. El sistema omitirá la cabecera "Promotion code", limpiará espacios e importará los códigos disponibles ignorando duplicados.')
                    ->modalSubmitActionLabel('Iniciar Importación')
                    ->form([
                        FileUpload::make('csv_file')
                            ->label('Archivo CSV')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->disk('local')
                            ->directory('temp-promo-imports')
                            ->required()
                            ->helperText('Formato esperado: columna "Promotion code" o listado simple de códigos, uno por línea.'),
                    ])
                    ->action(function (array $data): void {
                        $relativeDiskPath = $data['csv_file'];
                        $fullPath = Storage::disk('local')->path($relativeDiskPath);

                        $stats = PromotionalCodeImporter::import($fullPath);

                        if (Storage::disk('local')->exists($relativeDiskPath)) {
                            Storage::disk('local')->delete($relativeDiskPath);
                        }

                        Notification::make()
                            ->title('Importación de códigos completada')
                            ->body("Se han importado {$stats['inserted']} códigos nuevos. {$stats['skipped']} códigos duplicados o vacíos fueron omitidos.")
                            ->success()
                            ->send();
                    }),

                CreateAction::make()
                    ->label('Añadir Código Manual')
                    ->modalHeading('Registrar Código Promocional Individual')
                    ->form([
                        TextInput::make('code')
                            ->label('Código Promocional')
                            ->required()
                            ->maxLength(255)
                            ->unique(PromotionalCode::class, 'code')
                            ->placeholder('Ej: 4DUJQ6ASZ392Z0EARXBLL64'),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['code'] = strtoupper(trim((string) $data['code']));

                        return $data;
                    }),
            ])
            ->actions([
                Action::make('release')
                    ->label('Liberar código')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Liberar Código Promocional')
                    ->modalDescription('¿Estás seguro de que deseas desvincular este código del usuario? El código volverá a estar disponible para futuras campañas.')
                    ->modalSubmitActionLabel('Sí, liberar código')
                    ->visible(fn (PromotionalCode $record): bool => $record->isAssigned())
                    ->action(function (PromotionalCode $record): void {
                        $record->release();

                        Notification::make()
                            ->title('Código liberado')
                            ->body('El código promocional vuelve a estar disponible en el inventario libre.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Eliminar')
                    ->modalHeading('Eliminar Código Promocional')
                    ->modalDescription('Esta acción eliminará el código del inventario de forma permanente.')
                    ->modalSubmitActionLabel('Sí, eliminar'),
            ])
            ->defaultSort('id', 'desc');
    }
}
