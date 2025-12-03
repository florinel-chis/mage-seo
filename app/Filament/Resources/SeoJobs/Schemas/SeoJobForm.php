<?php

namespace App\Filament\Resources\SeoJobs\Schemas;

use App\Models\LlmConfiguration;
use App\Models\Product;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SeoJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                // Job Configuration
                Select::make('magento_store_view')
                    ->label('Magento Store View')
                    ->options([
                        'default' => 'Default',
                        'en_us' => 'English (US)',
                        'de_de' => 'German (DE)',
                    ])
                    ->default('default')
                    ->required()
                    ->helperText('Select the Magento store view for SEO generation')
                    ->columnSpanFull(),

                // Product Selection Method
                Radio::make('selection_method')
                    ->label('Product Selection Method')
                    ->options([
                        'all' => 'All Products',
                        'sku_filter' => 'Filter by SKU',
                        'manual' => 'Manual Selection',
                    ])
                    ->default('all')
                    ->required()
                    ->reactive()
                    ->columnSpanFull()
                    ->helperText('Choose how to select products for SEO generation'),

                // SKU Filter (shown when sku_filter is selected)
                Textarea::make('sku_filter')
                    ->label('SKU Filter')
                    ->placeholder('Enter SKUs (one per line or comma-separated)')
                    ->rows(3)
                    ->columnSpanFull()
                    ->hidden(fn (Get $get) => $get('selection_method') !== 'sku_filter')
                    ->helperText('Enter SKUs to include. Use wildcards with * (e.g., ABC* matches ABC123, ABC456)'),

                // Manual Product Selection (shown when manual is selected)
                CheckboxList::make('product_ids')
                    ->label('Select Products')
                    ->options(fn () => Product::query()
                        ->orderBy('updated_at', 'desc')
                        ->limit(100)
                        ->get()
                        ->mapWithKeys(fn ($product) => [
                            $product->id => "{$product->sku} - {$product->name}"
                        ])
                    )
                    ->searchable()
                    ->columnSpanFull()
                    ->hidden(fn (Get $get) => $get('selection_method') !== 'manual')
                    ->helperText('Select products individually (showing latest 100 products)')
                    ->columns(2),

                // LLM Configuration (optional)
                Select::make('llm_config_id')
                    ->label('LLM Configuration (Optional)')
                    ->options(function () {
                        $writerConfigs = LlmConfiguration::where('prompt_type', 'writer')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($config) => [
                                $config->id => "{$config->name} (Writer)"
                            ]);

                        return $writerConfigs;
                    })
                    ->nullable()
                    ->helperText('Leave empty to use the active Writer configuration')
                    ->columnSpanFull(),

                // Preview/Info
                Placeholder::make('preview')
                    ->label('Selection Summary')
                    ->content(function (Get $get) {
                        $method = $get('selection_method');

                        if ($method === 'all') {
                            $count = Product::count();
                            return "Will process all {$count} products in the database.";
                        }

                        if ($method === 'sku_filter') {
                            $skuFilter = $get('sku_filter');
                            if (!$skuFilter) {
                                return 'Enter SKU filter to see product count.';
                            }

                            // Parse SKU filter
                            $skus = array_filter(
                                array_map('trim', preg_split('/[\n,]/', $skuFilter)),
                                fn ($sku) => !empty($sku)
                            );

                            $query = Product::query();
                            foreach ($skus as $sku) {
                                if (str_contains($sku, '*')) {
                                    $pattern = str_replace('*', '%', $sku);
                                    $query->orWhere('sku', 'like', $pattern);
                                } else {
                                    $query->orWhere('sku', $sku);
                                }
                            }

                            $count = $query->count();
                            return "Filter matches {$count} product(s).";
                        }

                        if ($method === 'manual') {
                            $selectedIds = $get('product_ids');
                            $count = is_array($selectedIds) ? count($selectedIds) : 0;
                            return "Selected {$count} product(s) manually.";
                        }

                        return '';
                    })
                    ->columnSpanFull(),
            ]);
    }
}
