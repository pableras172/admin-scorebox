<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppAnnouncements;

use App\Filament\Resources\AppAnnouncements\Pages\CreateAppAnnouncement;
use App\Filament\Resources\AppAnnouncements\Pages\EditAppAnnouncement;
use App\Filament\Resources\AppAnnouncements\Pages\ListAppAnnouncements;
use App\Filament\Resources\AppAnnouncements\Schemas\AppAnnouncementForm;
use App\Filament\Resources\AppAnnouncements\Tables\AppAnnouncementsTable;
use App\Models\AppAnnouncement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AppAnnouncementResource extends Resource
{
    protected static ?string $model = AppAnnouncement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración App';
    }

    public static function getNavigationLabel(): string
    {
        return 'Avisos en App (Banners)';
    }

    public static function getModelLabel(): string
    {
        return 'aviso';
    }

    public static function getPluralModelLabel(): string
    {
        return 'avisos y banners';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AppAnnouncement::active()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return AppAnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppAnnouncementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppAnnouncements::route('/'),
            'create' => CreateAppAnnouncement::route('/create'),
            'edit' => EditAppAnnouncement::route('/{record}/edit'),
        ];
    }
}
