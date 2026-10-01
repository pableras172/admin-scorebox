<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Firestore\FirestoreAppAnnouncementGateway;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
use Filament\Schemas\Components\Utilities\Set;
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
        $data = $gateway->get();

        if (! empty($data['image_url']) && str_contains((string) $data['image_url'], '/storage/announcements/')) {
            $parts = explode('/storage/', (string) $data['image_url']);
            $data['image_file'] = end($parts);
        }

        $this->form->fill($data);
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

                        Select::make('type')
                            ->label('Tipo de banner / Estilo visual')
                            ->options([
                                'info' => 'ℹ️ Informativo (Azul / Neutro)',
                                'warning' => '⚠️ Aviso Importante (Amarillo)',
                                'success' => '✨ Novedad / Actualización (Verde)',
                                'promo' => '💜 Promoción ScoreBox (Morado)',
                                'ad' => '📢 Anuncio Publicitario / Patrocinado (Oculto a usuarios PRO)',
                            ])
                            ->default('info')
                            ->required()
                            ->live()
                            ->native(false)
                            ->afterStateUpdated(function (string $state, Set $set): void {
                                if ($state === 'ad') {
                                    $set('hide_for_pro', true);
                                }
                            }),

                        Toggle::make('hide_for_pro')
                            ->label('Ocultar a usuarios PRO (Sin anuncios)')
                            ->helperText('Los usuarios con suscripción Premium o código PRO nunca verán este banner. Se activa automáticamente para Anuncios Publicitarios.')
                            ->default(false),

                        TextInput::make('title')
                            ->label('Título del aviso o patrocinador')
                            ->placeholder('Ej: ¡Nueva versión disponible! / Patrocinado por...')
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
                            ->placeholder('Ej: Ver tienda / Más información / Actualizar'),

                        TextInput::make('action_url')
                            ->label('Enlace de acción o web del patrocinador (Opcional)')
                            ->placeholder('Ej: https://sponsor-web.com o enlace a Play Store')
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

        // Compute final image_url
        if (! empty($formData['image_file'])) {
            $formData['image_url'] = asset('storage/'.ltrim((string) $formData['image_file'], '/'));
        } elseif (! empty($formData['image_url'])) {
            $formData['image_url'] = trim((string) $formData['image_url']);
        } else {
            $formData['image_url'] = '';
        }

        if (($formData['type'] ?? '') === 'ad') {
            $formData['hide_for_pro'] = true;
        }

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
