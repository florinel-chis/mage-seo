<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\LLM\WriterAuditor;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WriterAuditorTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated(): void
    {
        $auditor = new WriterAuditor('dummy-api-key');
        $this->assertInstanceOf(WriterAuditor::class, $auditor);
    }

    /** @test */
    public function generate_returns_expected_structure(): void
    {
        // We are testing the placeholder implementation for now.
        // The placeholder does not make actual HTTP calls,
        // so we don't need to mock Http::fake() here.

        $product = Product::factory()->make([
            'name' => 'Test Product Name',
            'description' => 'Test Product Description',
        ]);

        $auditor = new WriterAuditor('dummy-api-key');
        $result = $auditor->generate($product);

        $this->assertArrayHasKey('generated_draft', $result);
        $this->assertArrayHasKey('audit', $result);

        $this->assertArrayHasKey('meta_title', $result['generated_draft']);
        $this->assertArrayHasKey('meta_description', $result['generated_draft']);
        $this->assertArrayHasKey('meta_keywords', $result['generated_draft']);

        $this->assertArrayHasKey('is_safe', $result['audit']);
        $this->assertArrayHasKey('confidence_score', $result['audit']);
        $this->assertArrayHasKey('potential_hallucinations', $result['audit']);

        $this->assertEquals("Generated Title for Test Product Name", $result['generated_draft']['meta_title']);
    }
}
