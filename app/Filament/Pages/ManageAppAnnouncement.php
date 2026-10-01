<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Firestore\FirestoreAppAnnouncementGateway;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * @property-read Schema $form
 */
class ManageAppAnnouncement extends Page
{
    protected static ?string $title = 'Aviso Remoto en App Móvil';

    protected string $view = 'filament.pages.manage-app-announcement';

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-megaphone';
    }

    public static function getNavigationLabel(): string
    {
        return 'Aviso en App (Banner)';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración App';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $this->form->fill($gateway->get());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Configuración del Banner Remoto (ScoreBox Android)')
                    ->description('Este aviso se sincroniza en tiempo real con la app móvil a través de Cloud Firestore (app_config/announcement).')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Activar aviso en la app')
                            ->helperText('Cuando está activo, los usuarios verán este banner destacado al abrir la app móvil ScoreBox.')
                            ->default(false),

                        TextInput::make('title')
                            ->label('Título del aviso')
                            ->placeholder('Ej: ¡Nueva versión disponible!')
                            ->maxLength(100),

                        Textarea::make('message')
                            ->label('Mensaje / Descripción')
                            ->placeholder('Ej: Hemos añadido mejoras en la biblioteca de partituras y nuevas funciones.')
                            ->rows(3)
                            ->maxLength(255),

                        Select::make('type')
                            ->label('Estilo visual del banner')
                            ->options([
                                'info' => 'Informativo (Azul / Neutro)',
                                'warning' => 'Aviso Importante (Amarillo)',
                                'success' => 'Novedad / Actualización (Verde)',
                                'promo' => 'Promoción / Oferta (Morado ScoreBox)',
                            ])
                            ->default('info')
                            ->required()
                            ->native(false),

                        TextInput::make('action_text')
                            ->label('Texto del botón de acción (Opcional)')
                            ->placeholder('Ej: Actualizar / Ver más'),

                        TextInput::make('action_url')
                            ->label('Enlace de acción (Opcional)')
                            ->placeholder('Ej: https://play.google.com/store/apps/details?id=com.mimusicalscores.scorebox')
                            ->url(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar y Sincronizar en Firestore')
                                ->icon('heroicon-o-cloud-arrow-up')
                                ->submit('save')
                                ->color('primary'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $formData = $this->form->getState();

        $gateway = app(FirestoreAppAnnouncementGateway::class);
        $result = $gateway->save($formData);

        if ($result->isSuccess()) {
            Notification::make()
                ->title('Aviso actualizado en Firestore')
                ->body('Los cambios se han sincronizado con éxito y la app ScoreBox los reflejará de inmediato.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Error al sincronizar con Firestore')
                ->body($result->error() ?? 'No se pudo guardar la configuración.')
                ->danger()
                ->send();
        }
    }
}
