<?php

namespace App\Filament\Resources\LlmLogs\Pages;

use App\Filament\Resources\LlmLogs\LlmLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLlmLogs extends ListRecords
{
    protected static string $resource = LlmLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}
