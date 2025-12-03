<?php

namespace App\Filament\Resources\SeoJobs\Pages;

use App\Filament\Resources\SeoJobs\SeoJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoJobs extends ListRecords
{
    protected static string $resource = SeoJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
