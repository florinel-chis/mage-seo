<?php

namespace App\Filament\Resources\Products\Tables;

use App\Jobs\ProcessProduct;
use App\Models\SeoJob;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->placeholder('No description')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Imported')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('generateSeo')
                        ->label('Generate SEO Content')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Generate SEO Content')
                        ->modalDescription(fn (Collection $records) =>
                            'Generate SEO metadata for ' . $records->count() . ' selected product(s) using AI?'
                        )
                        ->modalSubmitActionLabel('Generate')
                        ->action(function (Collection $records) {
                            // Create SeoJob
                            $seoJob = SeoJob::create([
                                'user_id' => auth()->id(),
                                'magento_store_view' => 'default',
                                'product_ids' => $records->pluck('id')->toArray(),
                                'filter_criteria' => ['method' => 'bulk_selection'],
                                'status' => 'PENDING',
                                'total_products' => $records->count(),
                                'processed_products' => 0,
                            ]);

                            // Dispatch ProcessProduct job for each selected product
                            foreach ($records as $product) {
                                ProcessProduct::dispatch($seoJob, $product);
                            }

                            Notification::make()
                                ->success()
                                ->title('SEO Generation Started')
                                ->body("Processing {$records->count()} product(s). Job ID: {$seoJob->id}")
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
