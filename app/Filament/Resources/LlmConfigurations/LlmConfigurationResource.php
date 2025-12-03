<?php

namespace App\Filament\Resources\LlmConfigurations;

use App\Filament\Resources\LlmConfigurations\Pages;
use App\Filament\Resources\LlmConfigurations\Schemas\LlmConfigurationForm;
use App\Filament\Resources\LlmConfigurations\Schemas\LlmConfigurationInfolist;
use App\Filament\Resources\LlmConfigurations\Tables\LlmConfigurationsTable;
use App\Models\LlmConfiguration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class LlmConfigurationResource extends Resource
{
    protected static ?string $model = LlmConfiguration::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'LLM Configurations';

    protected static ?string $modelLabel = 'LLM Configuration';

    protected static ?string $pluralModelLabel = 'LLM Configurations';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return LlmConfigurationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LlmConfigurationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LlmConfigurationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLlmConfigurations::route('/'),
            'create' => Pages\CreateLlmConfiguration::route('/create'),
            'edit' => Pages\EditLlmConfiguration::route('/{record}/edit'),
            'view' => Pages\ViewLlmConfiguration::route('/{record}'),
        ];
    }
}
