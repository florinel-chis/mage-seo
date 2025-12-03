<?php

namespace App\Filament\Resources\SeoJobs\Pages;

use App\Filament\Resources\SeoJobs\SeoJobResource;
use App\Jobs\ProcessProduct;
use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSeoJob extends CreateRecord
{
    protected static string $resource = SeoJobResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set user_id
        $data['user_id'] = auth()->id();

        // Determine product IDs based on selection method
        $selectionMethod = $data['selection_method'] ?? 'all';
        $productIds = [];

        if ($selectionMethod === 'all') {
            $productIds = Product::query()->pluck('id')->toArray();
        } elseif ($selectionMethod === 'sku_filter') {
            $skuFilter = $data['sku_filter'] ?? '';
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

            $productIds = $query->pluck('id')->toArray();
        } elseif ($selectionMethod === 'manual') {
            $productIds = $data['product_ids'] ?? [];
        }

        // Store product IDs and filter criteria
        $data['product_ids'] = $productIds;
        $data['filter_criteria'] = [
            'method' => $selectionMethod,
            'sku_filter' => $data['sku_filter'] ?? null,
        ];

        // Set total products count
        $data['total_products'] = count($productIds);
        $data['processed_products'] = 0;
        $data['status'] = 'PENDING';

        // Remove temporary form fields (keep llm_config_id - it's a real field)
        unset($data['selection_method']);
        unset($data['sku_filter']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $seoJob = $this->record;
        $productIds = $seoJob->product_ids ?? [];

        // Dispatch ProcessProduct job for each selected product
        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                ProcessProduct::dispatch($seoJob, $product);
            }
        }

        Notification::make()
            ->success()
            ->title('SEO Generation Started')
            ->body("Processing {$seoJob->total_products} product(s). Jobs dispatched to queue.")
            ->send();
    }
}
