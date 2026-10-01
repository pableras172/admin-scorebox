<?php

declare(strict_types=1);

namespace App\Filament\Resources\Suggestions\Schemas;

use App\Models\Suggestion;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SuggestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Sugerencia')
                    ->description('Datos enviados por el usuario o capturados desde la aplicación.')
                    ->schema([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                Suggestion::STATUS_NEW => 'Nueva (Sin revisar)',
                                Suggestion::STATUS_IN_REVIEW => 'En revisión / Evaluación',
                                Suggestion::STATUS_PLANNED => 'Planificada / Aceptada',
                                Suggestion::STATUS_COMPLETED => 'Implementada',
                                Suggestion::STATUS_DISMISSED => 'Descartada / Resuelta',
                            ])
                            ->default(Suggestion::STATUS_NEW)
                            ->required()
                            ->native(false),

                        Select::make('type')
                            ->label('Tipo de aportación')
                            ->options([
                                Suggestion::TYPE_IDEA => '💡 Idea / Nueva función',
                                Suggestion::TYPE_BUG => '🐛 Reporte de error / Bug',
                                Suggestion::TYPE_SCORES_REQUEST => '🎼 Petición de partituras',
                                Suggestion::TYPE_USABILITY => '⚡ Rendimiento / Usabilidad',
                                Suggestion::TYPE_OTHER => '💬 Otro comentario',
                            ])
                            ->default(Suggestion::TYPE_IDEA)
                            ->required()
                            ->native(false),

                        Select::make('source')
                            ->label('Canal de origen')
                            ->options([
                                Suggestion::SOURCE_APP => 'App Móvil (Android)',
                                Suggestion::SOURCE_WEB => 'Formulario Web',
                                Suggestion::SOURCE_ADMIN => 'Creado en Panel Admin',
                            ])
                            ->default(Suggestion::SOURCE_APP)
                            ->required()
                            ->native(false),

                        Checkbox::make('is_premium')
                            ->label('Usuario Premium de ScoreBox')
                            ->default(false),

                        TextInput::make('email')
                            ->label('Email del usuario')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('name')
                            ->label('Nombre del usuario')
                            ->maxLength(100),

                        TextInput::make('subject')
                            ->label('Asunto / Título')
                            ->columnSpanFull()
                            ->maxLength(150),

                        Textarea::make('message')
                            ->label('Mensaje / Sugerencia')
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Seguimiento y Notas Internas')
                    ->schema([
                        Textarea::make('admin_notes')
                            ->label('Notas internas del administrador')
                            ->placeholder('Anotaciones privadas sobre la viabilidad, versión prevista o seguimiento de esta sugerencia...')
                            ->rows(4)
                            ->columnSpanFull(),

                        DateTimePicker::make('replied_at')
                            ->label('Fecha de última respuesta al usuario')
                            ->disabled(),
                    ]),

                Section::make('Datos Técnicos del Dispositivo')
                    ->collapsed()
                    ->schema([
                        TextInput::make('app_version')
                            ->label('Versión de la app')
                            ->disabled(),

                        TextInput::make('device_info')
                            ->label('Dispositivo / SO')
                            ->disabled(),

                        TextInput::make('uid')
                            ->label('UID Firestore')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }
}
