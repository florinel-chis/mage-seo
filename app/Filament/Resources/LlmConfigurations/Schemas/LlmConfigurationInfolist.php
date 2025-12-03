<?php

namespace App\Filament\Resources\LlmConfigurations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class LlmConfigurationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('name')
                    ->label('Configuration Name')
                    ->weight(FontWeight::Bold)
                    ->columnSpanFull(),

                TextEntry::make('description')
                    ->label('Description')
                    ->columnSpanFull(),

                TextEntry::make('prompt_type')
                    ->label('Prompt Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'writer' => 'success',
                        'auditor' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextEntry::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),

                TextEntry::make('version')
                    ->label('Version'),

                TextEntry::make('magentoStore.name')
                    ->label('Magento Store')
                    ->default('Global (all stores)'),

                TextEntry::make('provider')
                    ->label('Provider')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'together' => 'Together AI',
                        'ollama' => 'Ollama',
                        default => ucfirst($state),
                    }),

                TextEntry::make('model')
                    ->label('Model')
                    ->copyable(),

                TextEntry::make('temperature')
                    ->label('Temperature'),

                TextEntry::make('max_tokens')
                    ->label('Max Tokens'),

                TextEntry::make('top_p')
                    ->label('Top P')
                    ->default('Not set'),

                TextEntry::make('frequency_penalty')
                    ->label('Frequency Penalty')
                    ->default('Not set'),

                TextEntry::make('presence_penalty')
                    ->label('Presence Penalty')
                    ->default('Not set'),

                TextEntry::make('usage_count')
                    ->label('Times Used')
                    ->default(0),

                TextEntry::make('last_used_at')
                    ->label('Last Used')
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return 'Never';
                        }
                        return $state->format('M j, Y g:i A') . ' (' . $state->diffForHumans() . ')';
                    }),

                TextEntry::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->since(),

                TextEntry::make('system_prompt')
                    ->label('System Prompt')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(fn ($state) => '<pre class="text-xs bg-gray-100 p-4 rounded overflow-auto max-h-96">' . htmlspecialchars($state) . '</pre>'),

                TextEntry::make('user_prompt_template')
                    ->label('User Prompt Template')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(fn ($state) => '<pre class="text-xs bg-gray-100 p-4 rounded overflow-auto max-h-96">' . htmlspecialchars($state) . '</pre>'),

                TextEntry::make('response_schema')
                    ->label('Response Schema')
                    ->columnSpanFull()
                    ->html()
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? '<pre class="text-xs bg-gray-100 p-4 rounded overflow-auto max-h-96">' . json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>'
                        : '<span class="text-gray-500">Not configured</span>'
                    ),
            ]);
    }
}
