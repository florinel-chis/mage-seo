<?php

namespace App\Filament\Resources\LlmConfigurations\Pages;

use App\Filament\Resources\LlmConfigurations\LlmConfigurationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLlmConfigurations extends ListRecords
{
    protected static string $resource = LlmConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
