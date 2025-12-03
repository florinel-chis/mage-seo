<?php

namespace App\Filament\Pages;

use App\Models\SeoDraft;
use BackedEnum;
use Filament\Pages\Page;

class DraftPreview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.draft-preview';

    protected static string $routePath = '/seo-drafts/{record}/preview';

    public SeoDraft $record;

    public function mount(SeoDraft $record): void
    {
        $this->record = $record;
    }

    public function approve(): void
    {
        $this->record->update(['status' => 'APPROVED']);
        $this->refresh();
    }

    public function reject(): void
    {
        $this->record->update(['status' => 'REJECTED']);
        $this->refresh();
    }
}
