<?php

namespace App\Filament\Resources\SeoDrafts\Pages;

use App\Filament\Resources\SeoDrafts\SeoDraftResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoDrafts extends ListRecords
{
    protected static string $resource = SeoDraftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
