<?php

namespace App\Filament\Resources\MagentoStores\Pages;

use App\Filament\Resources\MagentoStores\MagentoStoreResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMagentoStore extends EditRecord
{
    protected static string $resource = MagentoStoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
