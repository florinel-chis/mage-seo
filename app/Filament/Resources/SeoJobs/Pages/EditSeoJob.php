<?php

namespace App\Filament\Resources\SeoJobs\Pages;

use App\Filament\Resources\SeoJobs\SeoJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoJob extends EditRecord
{
    protected static string $resource = SeoJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Extract selection method from filter_criteria
        $filterCriteria = $data['filter_criteria'] ?? [];
        $selectionMethod = $filterCriteria['method'] ?? 'all';

        // Add selection method to form data
        $data['selection_method'] = $selectionMethod;

        // If SKU filter was used, restore the SKU filter text
        if ($selectionMethod === 'sku_filter' && isset($filterCriteria['sku_filter'])) {
            $data['sku_filter'] = $filterCriteria['sku_filter'];
        }

        // product_ids is already in the data from the database
        // No need to modify it

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Reconstruct filter_criteria from form data
        $data['filter_criteria'] = [
            'method' => $data['selection_method'] ?? 'all',
            'sku_filter' => $data['sku_filter'] ?? null,
        ];

        // Remove temporary form fields
        unset($data['selection_method']);
        unset($data['sku_filter']);

        return $data;
    }
}
