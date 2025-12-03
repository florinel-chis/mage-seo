<?php

namespace App\Filament\Resources\MagentoStores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MagentoStoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Store Name')
                    ->placeholder('My Magento Store')
                    ->columnSpanFull(),

                TextInput::make('url')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->label('Store URL')
                    ->placeholder('https://example.com')
                    ->helperText('Base URL of your Magento store (without trailing slash)')
                    ->columnSpanFull(),

                Textarea::make('api_token')
                    ->required()
                    ->rows(3)
                    ->label('API Integration Token')
                    ->placeholder('Paste your Magento integration token here')
                    ->helperText('Generate this token in Magento Admin: System > Extensions > Integrations')
                    ->columnSpanFull(),
            ]);
    }
}
