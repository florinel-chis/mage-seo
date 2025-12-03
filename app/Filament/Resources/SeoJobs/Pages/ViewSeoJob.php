<?php

namespace App\Filament\Resources\SeoJobs\Pages;

use App\Filament\Resources\SeoJobs\SeoJobResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSeoJob extends ViewRecord
{
    protected static string $resource = SeoJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
