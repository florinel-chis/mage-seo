<?php

namespace App\Filament\Resources\LlmConfigurations\Pages;

use App\Filament\Resources\LlmConfigurations\LlmConfigurationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLlmConfiguration extends EditRecord
{
    protected static string $resource = LlmConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function afterSave(): void
    {
        // If this config is set to active, deactivate other configs of same type
        if ($this->record->is_active) {
            \App\Models\LlmConfiguration::where('prompt_type', $this->record->prompt_type)
                ->where('id', '!=', $this->record->id)
                ->where(function ($query) {
                    // Only deactivate configs for same store or global configs
                    if ($this->record->magento_store_id) {
                        $query->where('magento_store_id', $this->record->magento_store_id)
                            ->orWhereNull('magento_store_id');
                    } else {
                        $query->whereNull('magento_store_id');
                    }
                })
                ->update(['is_active' => false]);
        }
    }
}
