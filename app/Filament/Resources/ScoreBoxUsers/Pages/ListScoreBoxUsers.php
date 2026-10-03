<?php

namespace App\Filament\Resources\ScoreBoxUsers\Pages;

use App\Filament\Resources\ScoreBoxUsers\ScoreBoxUserResource;
use Filament\Resources\Pages\ListRecords;

class ListScoreBoxUsers extends ListRecords
{
    protected static string $resource = ScoreBoxUserResource::class;

    public function getTitle(): string
    {
        return 'Listado de usuarios Firestore';
    }

    public function mount(): void
    {
        parent::mount();

        session()->put($this->getTablePerPageSessionKey(), 50);
        $this->tableRecordsPerPage = 50;
    }

    public function setPage(int|string $page, ?string $pageName = null): void
    {
        parent::setPage($page, $pageName);

        $this->flushCachedTableRecords();
    }

    public function updatedTableRecordsPerPage(): void
    {
        $this->flushCachedTableRecords();
    }

    public function updatedTableFilters(): void
    {
        $this->flushCachedTableRecords();
    }

    public function updatedTableSearch(): void
    {
        $this->flushCachedTableRecords();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
