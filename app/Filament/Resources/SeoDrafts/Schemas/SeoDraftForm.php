<?php

namespace App\Filament\Resources\SeoDrafts\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class SeoDraftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Product Information (Read-Only)
                Placeholder::make('product_info')
                    ->label('Product Information')
                    ->content(fn ($record) => $record?->product
                        ? $record->product->sku . ' - ' . $record->product->name
                        : 'N/A')
                    ->columnSpanFull(),

                Placeholder::make('job_id')
                    ->label('SEO Job ID')
                    ->content(fn ($record) => $record?->seo_job_id ?? 'N/A'),

                Placeholder::make('confidence_score_display')
                    ->label('AI Confidence Score')
                    ->content(fn ($record) => $record?->confidence_score
                        ? number_format($record->confidence_score * 100, 1) . '%'
                        : 'N/A'),

                // Status Control
                Select::make('status')
                    ->label('Review Status')
                    ->options([
                        'PENDING_REVIEW' => 'Pending Review',
                        'APPROVED' => 'Approved',
                        'REJECTED' => 'Rejected',
                        'SYNCED' => 'Synced to Magento',
                    ])
                    ->required()
                    ->default('PENDING_REVIEW')
                    ->helperText('Approve or reject this SEO content')
                    ->columnSpanFull(),

                // Generated SEO Content (Editable)
                TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->maxLength(60)
                    ->helperText(fn ($state) => ($state ? strlen($state) : 0) . ' / 60 characters (optimal: 50-60)')
                    ->required()
                    ->columnSpanFull()
                    ->dehydrateStateUsing(fn ($state, $record) => $state)
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && is_array($record->generated_draft)) {
                            $component->state($record->generated_draft['meta_title'] ?? '');
                        }
                    }),

                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(3)
                    ->maxLength(160)
                    ->helperText(fn ($state) => ($state ? strlen($state) : 0) . ' / 160 characters (optimal: 150-160)')
                    ->required()
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && is_array($record->generated_draft)) {
                            $component->state($record->generated_draft['meta_description'] ?? '');
                        }
                    }),

                TextInput::make('meta_keywords')
                    ->label('Meta Keywords')
                    ->helperText('Comma-separated keywords')
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $record) {
                        if ($record && is_array($record->generated_draft)) {
                            $component->state($record->generated_draft['meta_keywords'] ?? '');
                        }
                    }),

                // Audit Information (Read-Only)
                ViewField::make('audit_summary')
                    ->label('Audit Summary')
                    ->view('filament.forms.components.audit-summary')
                    ->columnSpanFull()
                    ->hidden(fn ($record) => !$record || empty($record->audit_flags)),

                // Original Product Data (Read-Only, Collapsed)
                ViewField::make('original_data_display')
                    ->label('Original Product Data (For Reference)')
                    ->view('filament.forms.components.original-product-data')
                    ->columnSpanFull(),
            ]);
    }
}
