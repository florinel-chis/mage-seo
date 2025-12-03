<?php

namespace App\Filament\Resources\SeoJobs;

use App\Filament\Resources\SeoJobs\Pages;
use App\Filament\Resources\SeoJobs\Schemas\SeoJobForm;
use App\Filament\Resources\SeoJobs\Tables\SeoJobsTable;
use App\Models\SeoJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SeoJobResource extends Resource
{
    protected static ?string $model = SeoJob::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Schema $schema): Schema
    {
        return SeoJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeoJobs::route('/'),
            'create' => Pages\CreateSeoJob::route('/create'),
            'edit' => Pages\EditSeoJob::route('/{record}/edit'),
        ];
    }
}