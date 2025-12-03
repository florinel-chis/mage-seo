<?php

namespace App\Filament\Resources\LlmLogs\Pages;

use App\Filament\Resources\LlmLogs\LlmLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLlmLog extends ViewRecord
{
    protected static string $resource = LlmLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => LlmLogResource::getUrl('index')),
        ];
    }
}
