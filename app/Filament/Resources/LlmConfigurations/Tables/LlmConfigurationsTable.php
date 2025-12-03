<?php

namespace App\Filament\Resources\LlmConfigurations\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LlmConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('prompt_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'writer' => 'success',
                        'auditor' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('provider')
                    ->label('Provider')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'together' => 'Together AI',
                        'ollama' => 'Ollama',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->model)
                    ->sortable(),

                TextColumn::make('temperature')
                    ->label('Temp')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('max_tokens')
                    ->label('Max Tokens')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('version')
                    ->label('Ver')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('usage_count')
                    ->label('Used')
                    ->sortable()
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->last_used_at ? 'Last used: ' . $record->last_used_at->diffForHumans() : 'Never used'),

                TextColumn::make('magentoStore.name')
                    ->label('Store')
                    ->placeholder('Global')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds to see usage updates
    }
}
