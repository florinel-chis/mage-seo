<?php

namespace App\Filament\Resources\MagentoStores\Tables;

use App\Jobs\FetchMagentoProductsJob;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class MagentoStoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Store Name'),

                TextColumn::make('url')
                    ->searchable()
                    ->label('Store URL')
                    ->limit(40),

                TextColumn::make('sync_status')
                    ->label('Sync Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'syncing' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'idle' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('products_fetched')
                    ->label('Products')
                    ->formatStateUsing(function ($record): string {
                        if ($record->total_products) {
                            return "{$record->products_fetched} / {$record->total_products}";
                        }
                        return $record->products_fetched ?: '-';
                    })
                    ->sortable(),

                TextColumn::make('last_sync_completed_at')
                    ->label('Last Sync')
                    ->dateTime('M j, g:i A')
                    ->sortable()
                    ->since()
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->label('Added')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('fetch_products')
                    ->label('Fetch Products')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Fetch Products from Magento')
                    ->modalDescription('This will start a background job to fetch all products from this Magento store. Depending on the number of products, this may take several minutes.')
                    ->action(function ($record) {
                        FetchMagentoProductsJob::dispatch($record);

                        Notification::make()
                            ->title('Product fetch started')
                            ->body("Fetching products from {$record->name} in the background.")
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
