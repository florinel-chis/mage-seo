<?php

namespace App\Filament\Resources\LlmLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LlmLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                BadgeColumn::make('agent_type')
                    ->label('Agent')
                    ->colors([
                        'primary' => 'writer',
                        'success' => 'auditor',
                    ])
                    ->sortable(),

                TextColumn::make('product.sku')
                    ->label('Product SKU')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('model')
                    ->label('Model')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                IconColumn::make('success')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('execution_time_ms')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? ($state < 1000 ? $state.'ms' : round($state / 1000, 2).'s') : 'N/A')
                    ->sortable(),

                TextColumn::make('total_tokens')
                    ->label('Tokens')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'N/A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('response_status')
                    ->label('HTTP')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state >= 200 && $state < 300,
                        'danger' => fn ($state) => $state >= 400,
                        'warning' => fn ($state) => $state >= 300 && $state < 400,
                    ])
                    ->toggleable(),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->toggleable()
                    ->color('danger'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('agent_type')
                    ->label('Agent Type')
                    ->options([
                        'writer' => 'Writer',
                        'auditor' => 'Auditor',
                    ]),

                TernaryFilter::make('success')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Success')
                    ->falseLabel('Failed'),

                SelectFilter::make('model')
                    ->label('Model')
                    ->options([
                        'gemini-2.5-flash' => 'Gemini 2.5 Flash',
                        'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                        'gpt-4o-mini' => 'GPT-4o Mini',
                        'gpt-4o' => 'GPT-4o',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View Details'),
            ])
            ->bulkActions([
                // No bulk actions for logs (read-only)
            ]);
    }
}
