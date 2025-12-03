<?php

namespace App\Services\Magento;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use App\Services\Magento\MagentoApiException;

class Client
{
    protected PendingRequest $client;

    public function __construct(
        protected string $baseUrl,
        protected string $token,
        protected int $timeout = 30,
    ) {
        $this->client = Http::withToken($this->token)
            ->baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson();
    }

    /**
     * Get a product from Magento by its ID.
     *
     * @param int $productId
     * @return array
     * @throws MagentoApiException
     */
    public function getProduct(int $productId): array
    {
        $response = $this->client->get("products/{$productId}");

        if ($response->failed()) {
            throw new MagentoApiException("Failed to fetch product with ID: {$productId}", $response->status());
        }

        return $response->json();
    }

    /**
     * Make a generic GET request to the Magento API.
     *
     * @param string $endpoint
     * @return array
     * @throws MagentoApiException
     */
    public function get(string $endpoint): array
    {
        try {
            // Ensure endpoint starts with /rest/ if not already present
            if (!str_starts_with($endpoint, '/rest/') && !str_starts_with($endpoint, 'rest/')) {
                $endpoint = '/rest/V1/' . ltrim($endpoint, '/');
            }

            $response = $this->client->get($endpoint);

            if ($response->failed()) {
                $body = $response->body();
                throw new MagentoApiException(
                    "Failed to fetch from endpoint: {$endpoint}. Status: {$response->status()}. Response: {$body}",
                    $response->status()
                );
            }

            $data = $response->json();

            if (!is_array($data)) {
                throw new MagentoApiException("Invalid response from Magento API. Expected array, got: " . gettype($data));
            }

            return $data;
        } catch (MagentoApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new MagentoApiException("Error calling Magento API: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Test the connection to the Magento store.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $this->client->get('products?searchCriteria[pageSize]=1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
