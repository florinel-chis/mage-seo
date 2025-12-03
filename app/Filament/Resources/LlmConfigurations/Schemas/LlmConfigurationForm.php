<?php

namespace App\Filament\Resources\LlmConfigurations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LlmConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Basic Information
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Configuration Name')
                    ->placeholder('e.g., SEO Writer v2 - GPT-4')
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->rows(2)
                    ->maxLength(1000)
                    ->label('Description')
                    ->placeholder('Describe the purpose and settings of this configuration')
                    ->columnSpanFull(),

                Select::make('prompt_type')
                    ->required()
                    ->options([
                        'writer' => 'Writer (generates SEO content)',
                        'auditor' => 'Auditor (validates content)',
                    ])
                    ->label('Prompt Type')
                    ->default('writer'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Only one configuration per type can be active at a time')
                    ->default(false),

                Select::make('magento_store_id')
                    ->relationship('magentoStore', 'name')
                    ->label('Magento Store (Optional)')
                    ->helperText('Leave empty for global configuration')
                    ->nullable()
                    ->searchable()
                    ->columnSpanFull(),

                // LLM Provider Settings
                Select::make('provider')
                    ->required()
                    ->options([
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic (Claude)',
                        'together' => 'Together AI',
                        'ollama' => 'Ollama (Local)',
                    ])
                    ->default('openai')
                    ->label('Provider')
                    ->reactive(),

                TextInput::make('model')
                    ->required()
                    ->label('Model')
                    ->placeholder('gpt-4o-mini, gpt-4, claude-3-opus, etc.')
                    ->helperText('Model identifier from the provider')
                    ->default('gpt-4o-mini'),

                // Model Parameters
                TextInput::make('temperature')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(2)
                    ->step(0.01)
                    ->default(0.70)
                    ->label('Temperature')
                    ->helperText('0 = deterministic, 2 = very creative. Recommended: 0.7 for Writer, 0.3 for Auditor'),

                TextInput::make('max_tokens')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4096)
                    ->default(500)
                    ->label('Max Tokens')
                    ->helperText('Maximum response length'),

                TextInput::make('top_p')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(1)
                    ->step(0.01)
                    ->nullable()
                    ->label('Top P')
                    ->helperText('Nucleus sampling. Leave empty to use default'),

                TextInput::make('frequency_penalty')
                    ->numeric()
                    ->minValue(-2)
                    ->maxValue(2)
                    ->step(0.01)
                    ->nullable()
                    ->label('Frequency Penalty')
                    ->helperText('Reduce repetition. Range: -2.0 to 2.0'),

                TextInput::make('presence_penalty')
                    ->numeric()
                    ->minValue(-2)
                    ->maxValue(2)
                    ->step(0.01)
                    ->nullable()
                    ->label('Presence Penalty')
                    ->helperText('Encourage new topics. Range: -2.0 to 2.0'),

                // Prompts
                Textarea::make('system_prompt')
                    ->required()
                    ->rows(10)
                    ->label('System Prompt')
                    ->helperText('The role and instructions for the AI. This sets the behavior and constraints.')
                    ->placeholder('You are an expert SEO copywriter...')
                    ->columnSpanFull(),

                Textarea::make('user_prompt_template')
                    ->required()
                    ->rows(10)
                    ->label('User Prompt Template')
                    ->helperText('Use {{product_json}} placeholder for product data. For auditor, also use {{generated_content}}.')
                    ->placeholder('Generate SEO metadata for:\n\n{{product_json}}')
                    ->columnSpanFull(),

                // Response Schema (JSON)
                Textarea::make('response_schema')
                    ->rows(10)
                    ->label('Response Schema (JSON)')
                    ->helperText('JSON schema defining expected response structure. Used for validation.')
                    ->placeholder('{"type": "object", "properties": {...}}')
                    ->columnSpanFull()
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                    ->dehydrateStateUsing(fn ($state) => $state ? json_decode($state, true) : null),

                // Version tracking
                TextInput::make('version')
                    ->numeric()
                    ->default(1)
                    ->label('Version')
                    ->helperText('Increment when making significant changes'),
            ]);
    }
}
