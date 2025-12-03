<?php

namespace Tests\Unit;

use App\Services\Magento\Client as MagentoClient;
use App\Services\Magento\MagentoApiException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MagentoClientTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated(): void
    {
        $client = new MagentoClient('http://magento.test', 'test_token');
        $this->assertInstanceOf(MagentoClient::class, $client);
    }

    /** @test */
    public function it_fetches_a_product_successfully(): void
    {
        Http::fake([
            'http://magento.test/products/123' => Http::response([
                'id' => 123,
                'sku' => 'TESTSKU',
                'name' => 'Test Product',
            ], 200),
        ]);

        $client = new MagentoClient('http://magento.test', 'test_token');
        $product = $client->getProduct(123);

        $this->assertEquals(123, $product['id']);
        $this->assertEquals('TESTSKU', $product['sku']);
        $this->assertEquals('Test Product', $product['name']);
    }

    /** @test */
    public function it_throws_an_exception_on_failed_product_fetch(): void
    {
        Http::fake([
            'http://magento.test/products/456' => Http::response([], 500),
        ]);

        $client = new MagentoClient('http://magento.test', 'test_token');

        $this->expectException(MagentoApiException::class);
        $this->expectExceptionCode(500);

        $client->getProduct(456);
    }
}
