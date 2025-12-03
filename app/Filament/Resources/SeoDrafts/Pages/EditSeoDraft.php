<?php

namespace App\Filament\Resources\SeoDrafts\Pages;

use App\Filament\Resources\SeoDrafts\SeoDraftResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoDraft extends EditRecord
{
    protected static string $resource = SeoDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Reconstruct generated_draft JSON from individual fields
        $generatedDraft = $this->record->generated_draft ?? [];

        if (isset($data['meta_title'])) {
            $generatedDraft['meta_title'] = $data['meta_title'];
            unset($data['meta_title']);
        }

        if (isset($data['meta_description'])) {
            $generatedDraft['meta_description'] = $data['meta_description'];
            unset($data['meta_description']);
        }

        if (isset($data['meta_keywords'])) {
            $generatedDraft['meta_keywords'] = $data['meta_keywords'];
            unset($data['meta_keywords']);
        }

        $data['generated_draft'] = $generatedDraft;

        return $data;
    }
}
