<?php

namespace App\Filament\Resources\MagentoStores\Pages;

use App\Filament\Resources\MagentoStores\MagentoStoreResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMagentoStores extends ListRecords
{
    protected static string $resource = MagentoStoreResource::class;

    protected static ?string $pollingInterval = '5s';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
