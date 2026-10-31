<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusicianReports\Schemas;

use App\Models\MusicianReport;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MusicianReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalles de la Denuncia')
                    ->description('Información enviada por el usuario reportante a través de la app.')
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                MusicianReport::STATUS_PENDING => 'Pendiente (Sin revisar)',
                                MusicianReport::STATUS_REVIEWED => 'En revisión',
                                MusicianReport::STATUS_RESOLVED => 'Resuelta',
                                MusicianReport::STATUS_DISMISSED => 'Descartada',
                            ])
                            ->default(MusicianReport::STATUS_PENDING)
                            ->required()
                            ->native(false),

                        TextInput::make('reason')
                            ->label('Motivo principal')
                            ->required()
                            ->readOnly(),

                        TextInput::make('reported_user_id')
                            ->label('ID del Músico Denunciado (UID)')
                            ->helperText('Identificador único en Firestore de la ficha del músico.')
                            ->required()
                            ->readOnly(),

                        TextInput::make('reporter_user_id')
                            ->label('ID del Usuario Denunciante')
                            ->placeholder('Anónimo / No registrado')
                            ->readOnly(),

                        DateTimePicker::make('reported_at')
                            ->label('Fecha del reporte')
                            ->readOnly(),

                        TextInput::make('action_taken')
                            ->label('Acción de moderación aplicada')
                            ->placeholder('Ninguna todavía')
                            ->readOnly(),

                        Textarea::make('details')
                            ->label('Detalles adicionales facilitados por el denunciante')
                            ->rows(4)
                            ->columnSpanFull()
                            ->readOnly(),
                    ])
                    ->columns(2),

                Section::make('Resolución y Notas de Moderación')
                    ->description('Anotaciones internas del administrador sobre la decisión adoptada.')
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('Notas del Administrador')
                            ->placeholder('Ej: Perfil verificado, contenía fotos inapropiadas y fue ocultado del directorio público.')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
