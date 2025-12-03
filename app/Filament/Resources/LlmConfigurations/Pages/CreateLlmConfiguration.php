<?php

namespace App\Filament\Resources\LlmConfigurations\Pages;

use App\Filament\Resources\LlmConfigurations\LlmConfigurationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLlmConfiguration extends CreateRecord
{
    protected static string $resource = LlmConfigurationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        // Auto-increment version if creating another config of same type
        if (!isset($data['version'])) {
            $latestVersion = \App\Models\LlmConfiguration::where('prompt_type', $data['prompt_type'])
                ->max('version');
            $data['version'] = ($latestVersion ?? 0) + 1;
        }

        return $data;
    }

    protected function afterCreate(): void
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
