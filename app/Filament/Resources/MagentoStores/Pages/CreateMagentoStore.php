<?php

namespace App\Filament\Resources\MagentoStores\Pages;

use App\Filament\Resources\MagentoStores\MagentoStoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMagentoStore extends CreateRecord
{
    protected static string $resource = MagentoStoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
