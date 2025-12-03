<?php

namespace App\Filament\Widgets;

use App\Models\SeoDraft;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestDrafts extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Latest SEO Drafts';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SeoDraft::query()
                    ->with(['product', 'seoJob'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->url(fn (SeoDraft $record): string => route('filament.admin.resources.seo-drafts.edit', ['record' => $record]))
                    ->color('primary'),

                Tables\Columns\TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('generated_draft.meta_title')
                    ->label('Generated Title')
                    ->limit(50)
                    ->tooltip(function (SeoDraft $record): string {
                        return $record->generated_draft['meta_title'] ?? 'N/A';
                    }),

                Tables\Columns\TextColumn::make('confidence_score')
                    ->label('Confidence')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state * 100, 1) . '%' : 'N/A')
                    ->color(fn ($state) => match (true) {
                        $state >= 0.9 => 'success',
                        $state >= 0.7 => 'warning',
                        default => 'danger',
                    })
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING_REVIEW' => 'warning',
                        'APPROVED' => 'success',
                        'REJECTED' => 'danger',
                        'SYNCED' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ]);
    }
}
