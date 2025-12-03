<?php

namespace App\Jobs;

use App\Models\MagentoStore;
use App\Models\Product;
use App\Services\Magento\Client as MagentoClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchMagentoProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes timeout for fetching

    public $tries = 3;

    protected $magentoStore;

    protected $page;

    protected $pageSize = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(MagentoStore $magentoStore, int $page = 1)
    {
        $this->magentoStore = $magentoStore;
        $this->page = $page;
    }

    /**
     * Execute the job.
     */
    public function handle(MagentoClient $magentoClient): void
    {
        Log::info("Fetching products for store '{$this->magentoStore->name}', page {$this->page}");

        try {
            // Mark as syncing on first page
            if ($this->page === 1) {
                $this->magentoStore->update([
                    'sync_status' => 'syncing',
                    'last_sync_started_at' => now(),
                    'products_fetched' => 0,
                    'total_products' => null,
                    'sync_error' => null,
                ]);
            }

            // Configure the client for this specific store
            $magentoClient = new MagentoClient(
                $this->magentoStore->url,
                $this->magentoStore->api_token
            );

            // Magento 2 REST API endpoint for products with search criteria
            // Example: /rest/V1/products?searchCriteria[pageSize]=20&searchCriteria[currentPage]=1
            $endpoint = "products?searchCriteria[pageSize]={$this->pageSize}&searchCriteria[currentPage]={$this->page}";
            $productsData = $magentoClient->get($endpoint);

            $items = $productsData['items'] ?? [];
            $totalCount = $productsData['total_count'] ?? 0;

            // Update total count on first page
            if ($this->page === 1) {
                $this->magentoStore->update(['total_products' => $totalCount]);
            }

            foreach ($items as $magentoProduct) {
                Product::updateOrCreate(
                    ['sku' => $magentoProduct['sku']],
                    [
                        'type_id' => $magentoProduct['type_id'] ?? null,
                        'name' => $magentoProduct['name'],
                        'description' => $magentoProduct['description'] ?? null,
                        'attributes' => $magentoProduct['custom_attributes'] ?? [],
                        'extension_attributes' => $magentoProduct['extension_attributes'] ?? null,
                    ]
                );
            }

            // Update progress
            $fetchedCount = count($items);
            $this->magentoStore->increment('products_fetched', $fetchedCount);

            // Check if there are more products to fetch
            if ($totalCount > ($this->page * $this->pageSize) && $fetchedCount == $this->pageSize) {
                // Dispatch the job for the next page
                FetchMagentoProductsJob::dispatch($this->magentoStore, $this->page + 1);
            } else {
                // Sync completed
                $this->magentoStore->update([
                    'sync_status' => 'completed',
                    'last_sync_completed_at' => now(),
                ]);

                Log::info("Sync completed for store '{$this->magentoStore->name}'. Total products fetched: {$this->magentoStore->products_fetched}");
            }

            Log::info("Finished fetching products for store '{$this->magentoStore->name}', page {$this->page}. Fetched {$fetchedCount} products.");

        } catch (\Exception $e) {
            // Mark as failed
            $this->magentoStore->update([
                'sync_status' => 'failed',
                'sync_error' => $e->getMessage(),
            ]);

            Log::error("Failed to fetch products for store '{$this->magentoStore->name}', page {$this->page}: ".$e->getMessage());
            throw $e;
        }
    }
}
