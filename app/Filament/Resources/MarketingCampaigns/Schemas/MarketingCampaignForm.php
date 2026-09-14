<?php

declare(strict_types=1);

namespace App\Filament\Resources\MarketingCampaigns\Schemas;

use App\Models\MarketingCampaign;
use App\Models\PromotionalCode;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Tiptap\Editor;

class MarketingCampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        $testEmails = (array) config('app.test_emails', [
            'pableras172@hotmail.com',
            'pableras172@gmail.com',
        ]);
        $testEmailsString = implode('; ', $testEmails);

        return $schema
            ->components([
                Section::make('Detalles de la Campaña')
                    ->description('Configura el asunto, el tipo de envío y el mensaje.')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Asunto del correo')
                            ->placeholder('Ej: ¡Consigue tu versión PRO sin anuncios, {{name}}!')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Puedes incluir {{name}} para que sea sustituido por el nombre del usuario o "músico".'),

                        Select::make('campaign_type')
                            ->label('Tipo de campaña')
                            ->options([
                                MarketingCampaign::TYPE_STANDARD => 'Estándar (Informativa / General)',
                                MarketingCampaign::TYPE_PROMOTIONAL_CODE => 'Código Promocional (Play Store)',
                            ])
                            ->default(MarketingCampaign::TYPE_STANDARD)
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(function (Get $get): string {
                                if ($get('campaign_type') === MarketingCampaign::TYPE_PROMOTIONAL_CODE) {
                                    $availableCount = PromotionalCode::available()->count();

                                    return "Stock actual: {$availableCount} códigos disponibles en inventario. Recuerda incluir {{promotioncode}} en el mensaje.";
                                }

                                return 'Campaña de correo estándar sin reparto de códigos.';
                            }),

                        Checkbox::make('exclude_previous_promo_recipients')
                            ->label('Excluir usuarios que ya hayan recibido un código promocional')
                            ->default(true)
                            ->visible(fn (Get $get): bool => $get('campaign_type') === MarketingCampaign::TYPE_PROMOTIONAL_CODE)
                            ->helperText('Si se desmarca, los usuarios que ya recibieron código y sigan sin ser Premium (isPremium = false) volverán a recibir su mismo código como recordatorio. Si ya son Premium, se omitirán.'),

                        Checkbox::make('is_test')
                            ->label("Correo de pruebas ({$testEmailsString})")
                            ->default(true)
                            ->live()
                            ->helperText('Si está marcado, el correo se enviará solo a las direcciones de prueba indicadas.'),

                        Select::make('target_segment')
                            ->label('Audiencia objetivo')
                            ->options([
                                MarketingCampaign::SEGMENT_ALL => 'Todos los usuarios (Free + Premium)',
                                MarketingCampaign::SEGMENT_FREE => 'Solo usuarios Free',
                                MarketingCampaign::SEGMENT_PREMIUM => 'Solo usuarios Premium',
                            ])
                            ->default(MarketingCampaign::SEGMENT_ALL)
                            ->required()
                            ->native(false)
                            ->disabled(fn (Get $get): bool => (bool) $get('is_test'))
                            ->dehydrated()
                            ->helperText(fn (Get $get): string => (bool) $get('is_test')
                                ? 'Desactiva la casilla "Correo de pruebas" superior para seleccionar una audiencia real.'
                                : 'Los usuarios en la lista de bajas serán excluidos automáticamente.'
                            ),

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
                            ->default('code')
                            ->afterStateHydrated(function (ToggleButtons $component, mixed $state): void {
                                if ($state === null) {
                                    $component->state('code');
                                }
                            })
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->helperText('En modo "Código HTML" puedes pegar plantillas completas con botones, tablas y estilos inline. No cambies a "Editor Visual" si pegas una plantilla con estilos, ya que el editor visual simplifica el HTML.'),

                        RichEditor::make('content')
                            ->label('Contenido del correo')
                            ->placeholder('Escribe aquí el contenido del mensaje...')
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('editor_mode') === 'visual')
                            ->dehydrated(fn (Get $get): bool => $get('editor_mode') === 'visual')
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ($get('campaign_type') === MarketingCampaign::TYPE_PROMOTIONAL_CODE) {
                                        $subject = (string) ($get('subject') ?? '');
                                        $content = is_array($value)
                                            ? (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '')
                                            : (string) ($value ?? '');

                                        if (! str_contains($subject, '{{promotioncode}}') && ! str_contains($content, '{{promotioncode}}')) {
                                            $fail('Las campañas de tipo Código Promocional deben incluir el comodín {{promotioncode}} en el asunto o en el contenido del correo.');
                                        }
                                    }
                                },
                            ])
                            ->helperText(function (Get $get): string {
                                if ($get('campaign_type') === MarketingCampaign::TYPE_PROMOTIONAL_CODE) {
                                    return 'Puedes utilizar {{name}} para el nombre y {{promotioncode}} para inyectar el código promocional o enlace de canjeo directo.';
                                }

                                return 'Puedes utilizar {{name}} en cualquier lugar del texto para personalizar el saludo.';
                            }),

                        Textarea::make('content')
                            ->label('Contenido del correo (HTML directo)')
                            ->placeholder('<p>Pega o escribe aquí el código HTML del correo...</p>')
                            ->required()
                            ->rows(20)
                            ->extraInputAttributes(['class' => 'font-mono text-sm leading-relaxed'])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => ($get('editor_mode') ?? 'code') === 'code')
                            ->dehydrated(fn (Get $get): bool => ($get('editor_mode') ?? 'code') === 'code')
                            ->formatStateUsing(function (mixed $state, ?MarketingCampaign $record): string {
                                $val = is_string($state) && $state !== '' ? $state : ($record?->content ?? '');
                                if (str_starts_with(trim($val), '{"type":"doc"')) {
                                    $decoded = json_decode($val, true);
                                    if (is_array($decoded)) {
                                        return (new Editor())->setContent($decoded)->getHTML();
                                    }
                                }

                                return is_string($val) ? $val : '';
                            })
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ($get('campaign_type') === MarketingCampaign::TYPE_PROMOTIONAL_CODE) {
                                        $subject = (string) ($get('subject') ?? '');
                                        $content = is_array($value)
                                            ? (json_encode($value, JSON_UNESCAPED_UNICODE) ?: '')
                                            : (string) ($value ?? '');

                                        if (! str_contains($subject, '{{promotioncode}}') && ! str_contains($content, '{{promotioncode}}')) {
                                            $fail('Las campañas de tipo Código Promocional deben incluir el comodín {{promotioncode}} en el asunto o en el contenido del correo.');
                                        }
                                    }
                                },
                            ])
                            ->helperText(function (Get $get): string {
                                if ($get('campaign_type') === MarketingCampaign::TYPE_PROMOTIONAL_CODE) {
                                    return 'Modo código HTML: Ideal para pegar plantillas completas con tablas y estilos inline. Recuerda incluir {{promotioncode}} y {{name}}.';
                                }

                                return 'Modo código HTML: Ideal para pegar plantillas personalizadas. Puedes utilizar {{name}} para personalizar el saludo.';
                            }),
                    ]),

                Section::make('Métricas y Estado de Envío')
                    ->description('Información operativa del envío.')
                    ->schema([
                        TextInput::make('status')
                            ->label('Estado')
                            ->disabled(),

                        TextInput::make('recipients_count')
                            ->label('Destinatarios calculados')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('sent_count')
                            ->label('Enviados con éxito')
                            ->numeric()
                            ->disabled(),

                        TextInput::make('failed_count')
                            ->label('Fallidos')
                            ->numeric()
                            ->disabled(),

                        DateTimePicker::make('sent_at')
                            ->label('Fecha de envío')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->hidden(fn (?MarketingCampaign $record): bool => $record === null || $record->status === MarketingCampaign::STATUS_DRAFT),
            ]);
    }
}
