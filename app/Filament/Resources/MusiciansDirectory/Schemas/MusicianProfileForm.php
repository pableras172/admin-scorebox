<?php

declare(strict_types=1);

namespace App\Filament\Resources\MusiciansDirectory\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MusicianProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidad y Perfil Público')
                    ->description('Datos visibles en el Directorio Global de ScoreBox.')
                    ->schema([
                        TextInput::make('userId')
                            ->label('User ID (UID)')
                            ->readOnly(),

                        TextInput::make('displayName')
                            ->label('Nombre del Músico')
                            ->readOnly(),

                        TextInput::make('photoUrl')
                            ->label('URL de la Foto de Perfil')
                            ->columnSpanFull()
                            ->readOnly(),

                        Textarea::make('bio')
                            ->label('Biografía / Trayectoria')
                            ->rows(4)
                            ->columnSpanFull()
                            ->readOnly(),
                    ])
                    ->columns(2),

                Section::make('Especialidad Musical y Ubicación')
                    ->schema([
                        TextInput::make('primaryInstrumentName')
                            ->label('Instrumento Principal')
                            ->readOnly(),

                        TagsInput::make('secondaryInstruments')
                            ->label('Instrumentos Secundarios')
                            ->disabled(),

                        TextInput::make('city')
                            ->label('Ciudad')
                            ->readOnly(),

                        TextInput::make('countryName')
                            ->label('País')
                            ->readOnly(),

                        TagsInput::make('roles')
                            ->label('Roles y Disponibilidad')
                            ->helperText('gigs_available (Actuaciones), teacher (Profesor), arranger (Arreglista), seeking_band (Buscando grupo), student (Estudiante)')
                            ->disabled(),

                        TagsInput::make('musicalStyles')
                            ->label('Estilos Musicales')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Contacto y Redes Sociales')
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->readOnly(),

                        TextInput::make('whatsappPhone')
                            ->label('WhatsApp / Teléfono')
                            ->readOnly(),

                        TextInput::make('instagramHandle')
                            ->label('Instagram')
                            ->readOnly(),

                        TextInput::make('websiteUrl')
                            ->label('Sitio Web')
                            ->readOnly(),
                    ])
                    ->columns(2),

                Section::make('Estados y Moderación')
                    ->schema([
                        Checkbox::make('isVisible')
                            ->label('Visible en la App (Directorio Global)')
                            ->disabled(),

                        Checkbox::make('isVerified')
                            ->label('Músico Verificado (Badge oficial)')
                            ->disabled(),

                        Checkbox::make('isFeatured')
                            ->label('Destacado en carruseles principales')
                            ->disabled(),

                        Checkbox::make('isPro')
                            ->label('Usuario con Suscripción PRO')
                            ->disabled(),

                        TextInput::make('createdAt')
                            ->label('Fecha de alta')
                            ->readOnly(),

                        TextInput::make('updatedAt')
                            ->label('Última actualización')
                            ->readOnly(),
                    ])
                    ->columns(2),
            ]);
    }
}
