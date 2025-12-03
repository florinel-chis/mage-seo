<?php

namespace App\Filament\Resources\LlmConfigurations\Pages;

use App\Filament\Resources\LlmConfigurations\LlmConfigurationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLlmConfiguration extends ViewRecord
{
    protected static string $resource = LlmConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
