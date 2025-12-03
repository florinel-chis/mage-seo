<?php

namespace App\Filament\Widgets;

use App\Models\MagentoStore;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MagentoSyncStatus extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Magento Product Sync Status';

    protected static ?string $pollingInterval = '5s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MagentoStore::query()
                    ->whereIn('sync_status', ['syncing', 'failed'])
                    ->orWhere('last_sync_completed_at', '>=', now()->subHours(24))
                    ->latest('last_sync_started_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sync_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'syncing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'syncing' => 'heroicon-o-arrow-path',
                        'completed' => 'heroicon-o-check-circle',
                        'failed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('products_fetched')
                    ->label('Progress')
                    ->formatStateUsing(function (MagentoStore $record): string {
                        if ($record->total_products) {
                            $percentage = round(($record->products_fetched / $record->total_products) * 100);
                            return "{$record->products_fetched} / {$record->total_products} ({$percentage}%)";
                        }
                        return $record->products_fetched;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_sync_started_at')
                    ->label('Started')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since(),

                Tables\Columns\TextColumn::make('last_sync_completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->placeholder('In progress'),

                Tables\Columns\TextColumn::make('sync_error')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(function (MagentoStore $record): ?string {
                        return $record->sync_error;
                    })
                    ->placeholder('-')
                    ->color('danger'),
            ])
            ->defaultSort('last_sync_started_at', 'desc');
    }
}
