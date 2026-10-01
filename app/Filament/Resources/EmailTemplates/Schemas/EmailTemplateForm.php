<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmailTemplates\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmailTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la Plantilla')
                    ->description('Configura los datos base de la plantilla reutilizable.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre identificativo')
                            ->placeholder('Ej: Bienvenida nuevo usuario, Novedades v2.0, Oferta Especial...')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('description')
                            ->label('Descripción / Notas internas')
                            ->placeholder('Ej: Plantilla para enviar a usuarios registrados en la última semana.')
                            ->maxLength(500),

                        TextInput::make('subject')
                            ->label('Asunto por defecto')
                            ->placeholder('Ej: ¡Bienvenido a ScoreBox, {{name}}!')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Puedes incluir {{name}} o {{promotioncode}} como comodines.'),

                        ToggleButtons::make('editor_mode')
                            ->label('Modo del editor')
                            ->options([
                                'code' => 'Código HTML (< / >)',
                                'visual' => 'Editor Visual',
                            ])
                            ->icons([
                                'code' => 'heroicon-o-code-bracket',
                                'visual' => 'heroicon-o-pencil-square',
                            ])
                            ->default('visual')
                            ->inline()
                            ->live(),

                        RichEditor::make('content')
                            ->key('template_content_visual')
                            ->label('Contenido de la plantilla')
                            ->placeholder('Escribe aquí el diseño o texto de la plantilla...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => ($get('editor_mode') ?? 'visual') === 'visual')
                            ->dehydrated(fn (Get $get): bool => ($get('editor_mode') ?? 'visual') === 'visual'),

                        Textarea::make('content')
                            ->key('template_content_code')
                            ->label('Contenido HTML directo')
                            ->placeholder('<p>Pega aquí tu plantilla HTML con estilos inline...</p>')
                            ->required()
                            ->rows(18)
                            ->extraInputAttributes(['class' => 'font-mono text-sm leading-relaxed'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('editor_mode') === 'code')
                            ->dehydrated(fn (Get $get): bool => $get('editor_mode') === 'code'),
                    ]),
            ]);
    }
}
