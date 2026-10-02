<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppAnnouncements\Schemas;

use App\Models\AppAnnouncement;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AppAnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Estado y Publicación en la App')
                    ->description('Controla si este banner está visible actualmente en la app ScoreBox para los usuarios.')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Activar y publicar de inmediato en la app ScoreBox')
                            ->helperText('Si se activa, este banner reemplazará al que esté actualmente en vivo y se sincronizará con Firestore.')
                            ->default(false),

                        Select::make('type')
                            ->label('Tipo de banner / Estilo visual')
                            ->options([
                                AppAnnouncement::TYPE_INFO => 'ℹ️ Informativo (Azul / Neutro)',
                                AppAnnouncement::TYPE_WARNING => '⚠️ Aviso Importante (Amarillo)',
                                AppAnnouncement::TYPE_SUCCESS => '✨ Novedad / Actualización (Verde)',
                                AppAnnouncement::TYPE_PROMO => '💜 Promoción ScoreBox (Morado)',
                                AppAnnouncement::TYPE_AD => '📢 Anuncio Publicitario / Patrocinado (Oculto a usuarios PRO)',
                            ])
                            ->default(AppAnnouncement::TYPE_INFO)
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function (string $state, Set $set): void {
                                if ($state === AppAnnouncement::TYPE_AD) {
                                    $set('hide_for_pro', true);
                                }
                            }),

                        Toggle::make('hide_for_pro')
                            ->label('Ocultar a usuarios PRO (Sin anuncios)')
                            ->helperText('Los usuarios con suscripción Premium o código PRO nunca verán este banner. Se activa automáticamente para Anuncios Publicitarios.')
                            ->default(false),
                    ])
                    ->columns(3),

                Section::make('Contenido del Aviso o Banner')
                    ->description('Texto, imagen y enlaces que verán los usuarios en la aplicación Android.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título del aviso o patrocinador')
                            ->placeholder('Ej: ¡Nueva versión disponible! / Patrocinado por...')
                            ->required()
                            ->maxLength(100),

                        Textarea::make('message')
                            ->label('Mensaje / Descripción')
                            ->placeholder('Ej: Hemos añadido mejoras en la biblioteca de partituras y nuevas funciones.')
                            ->rows(3)
                            ->maxLength(255),

                        FileUpload::make('image_file')
                            ->label('Subir imagen del banner publicitario (Opcional)')
                            ->disk('public')
                            ->directory('announcements')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->helperText('Sube una imagen o creatividad publicitaria (.jpg, .png, .webp). Se generará automáticamente la URL pública para la app.')
                            ->columnSpanFull(),

                        TextInput::make('image_url')
                            ->label('O URL directa de imagen externa (Opcional)')
                            ->placeholder('https://tuservidor.com/banner.png')
                            ->url()
                            ->helperText('Si el patrocinador aloja la imagen en su propio servidor web o CDN, puedes pegar la URL aquí.')
                            ->columnSpanFull()
                            ->maxLength(500),

                        TextInput::make('action_text')
                            ->label('Texto del botón de acción (Opcional)')
                            ->placeholder('Ej: Ver tienda / Más información / Actualizar')
                            ->maxLength(100),

                        TextInput::make('action_url')
                            ->label('Enlace de acción o web del patrocinador (Opcional)')
                            ->placeholder('Ej: https://sponsor-web.com o enlace a Play Store')
                            ->url()
                            ->maxLength(500),
                    ])
                    ->columns(2),
            ]);
    }
}
