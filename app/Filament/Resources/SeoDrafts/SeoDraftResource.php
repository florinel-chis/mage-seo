<?php

namespace App\Filament\Resources\SeoDrafts;

use App\Filament\Pages\DraftPreview;
use App\Filament\Resources\SeoDrafts\Pages;
use App\Filament\Resources\SeoDrafts\Schemas\SeoDraftForm;
use App\Filament\Resources\SeoDrafts\Tables\SeoDraftsTable;
use App\Models\SeoDraft;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SeoDraftResource extends Resource
{
    protected static ?string $model = SeoDraft::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldSkipAuthorization = true;

    public static function form(Schema $schema): Schema
    {
        return SeoDraftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeoDraftsTable::configure($table);
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
            'index' => Pages\ListSeoDrafts::route('/'),
            'create' => Pages\CreateSeoDraft::route('/create'),
            'edit' => Pages\EditSeoDraft::route('/{record}/edit'),
        ];
    }
}