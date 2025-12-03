<?php

namespace App\Filament\Resources\SeoDrafts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeoDraftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->copyable(),

                TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->product?->name),

                TextColumn::make('generated_draft')
                    ->label('Meta Title (Preview)')
                    ->limit(60)
                    ->formatStateUsing(fn ($state) => is_array($state) && isset($state['meta_title'])
                        ? $state['meta_title']
                        : 'N/A'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'APPROVED' => 'success',
                        'PENDING_REVIEW' => 'warning',
                        'REJECTED' => 'danger',
                        'SYNCED' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('confidence_score')
                    ->label('Score')
                    ->formatStateUsing(fn ($state) => number_format($state * 100, 1) . '%')
                    ->color(fn ($state): string => match (true) {
                        $state >= 0.9 => 'success',
                        $state >= 0.7 => 'warning',
                        default => 'danger',
                    })
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                TextColumn::make('seoJob.id')
                    ->label('Job ID')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Generated At')
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
