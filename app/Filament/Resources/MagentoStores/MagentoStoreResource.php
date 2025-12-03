<?php

namespace App\Filament\Resources\MagentoStores;

use App\Filament\Resources\MagentoStores\Pages\CreateMagentoStore;
use App\Filament\Resources\MagentoStores\Pages\EditMagentoStore;
use App\Filament\Resources\MagentoStores\Pages\ListMagentoStores;
use App\Filament\Resources\MagentoStores\Schemas\MagentoStoreForm;
use App\Filament\Resources\MagentoStores\Tables\MagentoStoresTable;
use App\Models\MagentoStore;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MagentoStoreResource extends Resource
{
    protected static ?string $model = MagentoStore::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Magento Stores';

    protected static ?string $modelLabel = 'Magento Store';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MagentoStoreForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MagentoStoresTable::configure($table);
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
            'index' => ListMagentoStores::route('/'),
            'create' => CreateMagentoStore::route('/create'),
            'edit' => EditMagentoStore::route('/{record}/edit'),
        ];
    }
}
