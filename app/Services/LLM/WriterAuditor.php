<?php

namespace App\Services\LLM;

use App\Models\LlmConfiguration;
use App\Models\LlmLog;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WriterAuditor
{
    protected string $apiKey;

    protected string $model;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    // Context for logging
    protected ?int $productId = null;

    protected ?int $seoDraftId = null;

    protected ?int $seoJobId = null;

    public function __construct(string $apiKey, string $model = 'gemini-2.5-flash')
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Set logging context.
     */
    public function setContext(?int $productId = null, ?int $seoDraftId = null, ?int $seoJobId = null): self
    {
        $this->productId = $productId;
        $this->seoDraftId = $seoDraftId;
        $this->seoJobId = $seoJobId;

        return $this;
    }

    /**
     * Generate SEO content and audit it.
     *
     * @param  int|null  $llmConfigId  Optional LLM configuration ID to use specific prompts
     *
     * @throws Exception
     */
    public function generate(Product $product, ?int $llmConfigId = null): array
    {
        // Set product context for logging
        $this->productId = $product->id;

        $generatedContent = $this->callWriterAgent($product, $llmConfigId);
        $auditResult = $this->callAuditorAgent($product, $generatedContent, $llmConfigId);

        return [
            'generated_draft' => $generatedContent,
            'audit' => $auditResult,
        ];
    }

    /**
     * Extract only SEO-relevant attributes from product data.
     * Focus on product features/characteristics, NOT purchase options.
     */
    protected function extractSeoRelevantAttributes(array $attributes): array
    {
        // Only include attributes that describe WHAT the product IS
        // These are typically shown on product pages and matter for SEO
        $relevantAttributes = [
            // Content
            'short_description',
            'description',

            // Physical attributes
            'color',
            'size',
            'material',
            'weight',
            'dimensions',
            'length',
            'width',
            'height',

            // Brand/Model
            'brand',
            'manufacturer',
            'model',

            // Category context
            'category_ids',

            // Product-specific features (customize based on your catalog)
            'flavor',
            'scent',
            'capacity',
            'power',
            'compatibility',
            'type',
            'style',
            'finish',
            'pattern',
        ];

        $filtered = [];

        foreach ($attributes as $attr) {
            $code = $attr['attribute_code'] ?? null;
            $value = $attr['value'] ?? null;

            // Skip if attribute code not in relevant list
            if (! $code || ! in_array($code, $relevantAttributes)) {
                continue;
            }

            // Skip if value is empty, null, or zero
            if ($value === null || $value === '' || $value === '0' || $value === 0) {
                continue;
            }

            // Strip HTML tags from descriptions
            if (in_array($code, ['short_description', 'description']) && is_string($value)) {
                $value = strip_tags($value);
                $value = trim($value);
                if (empty($value)) {
                    continue;
                }
            }

            // Add to filtered attributes with clean format
            $filtered[$code] = $value;
        }

        return $filtered;
    }

    /**
     * Extract bundle/configurable product components for SEO.
     * This provides context about what's included or available.
     * Enriches bundle items with full product data from database.
     */
    protected function extractProductComponents(Product $product): array
    {
        $components = [];
        $extensionAttributes = $product->extension_attributes ?? [];

        // Bundle products: Extract included items WITH full product details
        if ($product->type_id === 'bundle' && isset($extensionAttributes['bundle_product_options'])) {
            $components['type'] = 'bundle';
            $components['includes'] = [];

            foreach ($extensionAttributes['bundle_product_options'] as $option) {
                $optionTitle = $option['title'] ?? 'Item';
                $items = [];

                foreach ($option['product_links'] ?? [] as $link) {
                    if (isset($link['sku'])) {
                        // Enrich with full product data from database
                        $linkedProduct = Product::where('sku', $link['sku'])->first();

                        $itemData = [
                            'sku' => $link['sku'],
                            'qty' => $link['qty'] ?? 1,
                        ];

                        // Add name if available
                        if ($linkedProduct && $linkedProduct->name) {
                            $itemData['name'] = $linkedProduct->name;
                        }

                        // Add description if available and not empty
                        if ($linkedProduct && $linkedProduct->description && trim($linkedProduct->description) !== '') {
                            $itemData['description'] = trim(strip_tags($linkedProduct->description));
                        }

                        // Add SEO-relevant attributes
                        if ($linkedProduct && ! empty($linkedProduct->attributes)) {
                            $relevantAttrs = $this->extractSeoRelevantAttributes($linkedProduct->attributes);
                            if (! empty($relevantAttrs)) {
                                $itemData['attributes'] = $relevantAttrs;
                            }
                        }

                        $items[] = $itemData;
                    }
                }

                if (! empty($items)) {
                    $components['includes'][] = [
                        'option_title' => $optionTitle,
                        'items' => $items,
                    ];
                }
            }
        }

        // Configurable products: Extract available options (colors, sizes, etc.)
        if ($product->type_id === 'configurable' && isset($extensionAttributes['configurable_product_options'])) {
            $components['type'] = 'configurable';
            $components['options'] = [];

            foreach ($extensionAttributes['configurable_product_options'] as $option) {
                $attributeCode = $option['attribute_id'] ?? $option['label'] ?? null;
                $values = [];

                foreach ($option['values'] ?? [] as $value) {
                    if (isset($value['value_index'])) {
                        $values[] = $value['label'] ?? $value['value_index'];
                    }
                }

                if ($attributeCode && ! empty($values)) {
                    $components['options'][] = [
                        'attribute' => $option['label'] ?? $attributeCode,
                        'values' => $values,
                    ];
                }
            }
        }

        return $components;
    }

    /**
     * Call Gemini API to generate SEO content using Writer configuration.
     */
    protected function callWriterAgent(Product $product, ?int $llmConfigId = null): array
    {
        // Get Writer configuration
        $config = $llmConfigId
            ? LlmConfiguration::find($llmConfigId)
            : LlmConfiguration::getActive('writer');

        if (! $config) {
            // Fallback to default prompts if no configuration found
            return $this->generateWithDefaultPrompts($product, 'writer');
        }

        // Record usage
        $config->recordUsage();

        // Prepare compact product data (only SEO-relevant attributes)
        $productData = [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'attributes' => $this->extractSeoRelevantAttributes($product->attributes ?? []),
        ];

        // Add bundle/configurable product components if available
        $components = $this->extractProductComponents($product);
        if (! empty($components)) {
            $productData['components'] = $components;
        }

        // Build prompt from configuration
        $systemPrompt = $config->system_prompt ?? $this->getDefaultWriterSystemPrompt();
        $userPrompt = $config->renderUserPrompt(['product_json' => $productData]);

        // Call Gemini API with logging
        $response = $this->callGeminiApi($systemPrompt, $userPrompt, $config, 'writer', $productData);

        // Parse SEO content from response
        return $this->parseWriterResponse($response);
    }

    /**
     * Call Gemini API to audit generated content using Auditor configuration.
     */
    protected function callAuditorAgent(Product $product, array $generatedContent, ?int $llmConfigId = null): array
    {
        // Get Auditor configuration
        $config = $llmConfigId
            ? LlmConfiguration::find($llmConfigId)
            : LlmConfiguration::getActive('auditor');

        if (! $config) {
            // Fallback to default prompts if no configuration found
            return $this->auditWithDefaultPrompts($product, $generatedContent);
        }

        // Record usage
        $config->recordUsage();

        // Prepare compact audit data (only SEO-relevant attributes)
        $productInfo = [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'attributes' => $this->extractSeoRelevantAttributes($product->attributes ?? []),
        ];

        // Add bundle/configurable product components if available
        $components = $this->extractProductComponents($product);
        if (! empty($components)) {
            $productInfo['components'] = $components;
        }

        $auditData = [
            'product' => $productInfo,
            'generated_content' => $generatedContent,
        ];

        // Build prompt from configuration
        $systemPrompt = $config->system_prompt ?? $this->getDefaultAuditorSystemPrompt();
        $userPrompt = $config->renderUserPrompt($auditData);

        // Call Gemini API with logging
        $response = $this->callGeminiApi($systemPrompt, $userPrompt, $config, 'auditor', $productInfo);

        // Parse audit results from response
        return $this->parseAuditorResponse($response);
    }

    /**
     * Make API call to Gemini with comprehensive logging.
     */
    protected function callGeminiApi(
        string $systemPrompt,
        string $userPrompt,
        ?LlmConfiguration $config = null,
        string $agentType = 'writer',
        ?array $productData = null
    ): string {
        $model = $config ? $config->model : $this->model;
        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

        // Build request body
        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt."\n\n".$userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [],
        ];

        // Add optional parameters from configuration
        if ($config) {
            if ($config->temperature !== null) {
                $body['generationConfig']['temperature'] = (float) $config->temperature;
            }
            if ($config->max_tokens !== null) {
                $body['generationConfig']['maxOutputTokens'] = $config->max_tokens;
            }
            if ($config->top_p !== null) {
                $body['generationConfig']['topP'] = (float) $config->top_p;
            }
        }

        // Start timing
        $startTime = microtime(true);
        $logData = [
            'agent_type' => $agentType,
            'product_id' => $this->productId,
            'seo_draft_id' => $this->seoDraftId,
            'seo_job_id' => $this->seoJobId,
            'llm_configuration_id' => $config?->id,
            'model' => $model,
            'api_url' => $url,
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_body' => json_encode($body, JSON_PRETTY_PRINT),
            'product_data' => $productData,
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $body);

            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000); // Convert to milliseconds

            // Capture response details
            $responseStatus = $response->status();
            $responseHeaders = $response->headers();
            $responseBody = $response->body();
            $data = $response->json();

            // Update log data with response
            $logData['response_status'] = $responseStatus;
            $logData['response_headers'] = $responseHeaders;
            $logData['response_body'] = $responseBody;
            $logData['execution_time_ms'] = $executionTime;

            if (! $response->successful()) {
                $errorMessage = 'Gemini API error: '.$responseBody;
                $logData['success'] = false;
                $logData['error_message'] = $errorMessage;

                // Log to database
                $this->logApiCall($logData);

                throw new Exception($errorMessage);
            }

            // Extract text from Gemini response
            $candidate = $data['candidates'][0] ?? null;

            if (! $candidate) {
                $errorMessage = 'No candidates in Gemini API response: '.json_encode($data);
                $logData['success'] = false;
                $logData['error_message'] = $errorMessage;

                // Log to database
                $this->logApiCall($logData);

                throw new Exception($errorMessage);
            }

            // Extract token usage
            if (isset($data['usageMetadata'])) {
                $logData['prompt_tokens'] = $data['usageMetadata']['promptTokenCount'] ?? null;
                $logData['completion_tokens'] = $data['usageMetadata']['candidatesTokenCount'] ?? null;
                $logData['total_tokens'] = $data['usageMetadata']['totalTokenCount'] ?? null;
            }

            // Check if we hit token limits
            if (isset($candidate['finishReason']) && $candidate['finishReason'] === 'MAX_TOKENS') {
                Log::warning('Gemini response hit MAX_TOKENS limit', [
                    'usage' => $data['usageMetadata'] ?? [],
                ]);
            }

            // Extract text from parts
            $text = null;
            if (isset($candidate['content']['parts'][0]['text'])) {
                $text = $candidate['content']['parts'][0]['text'];
            } elseif (isset($candidate['output'])) {
                $text = $candidate['output'];
            }

            if ($text === null) {
                $errorMessage = 'Could not extract text from Gemini API response: '.json_encode($data);
                $logData['success'] = false;
                $logData['error_message'] = $errorMessage;

                // Log to database
                $this->logApiCall($logData);

                throw new Exception($errorMessage);
            }

            // Success - add parsed output
            $logData['success'] = true;
            $logData['parsed_output'] = ['extracted_text' => substr($text, 0, 500)]; // Store first 500 chars

            // Log to database
            $this->logApiCall($logData);

            return $text;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Calculate execution time even for timeout
            $executionTime = round((microtime(true) - $startTime) * 1000);

            $errorMessage = 'Gemini API timeout: '.$e->getMessage();
            $logData['success'] = false;
            $logData['error_message'] = $errorMessage;
            $logData['execution_time_ms'] = $executionTime;

            // Log to database
            $this->logApiCall($logData);

            throw new Exception($errorMessage);
        } catch (Exception $e) {
            // If logging not already done, log the error
            if (! isset($logData['success'])) {
                $executionTime = round((microtime(true) - $startTime) * 1000);
                $logData['success'] = false;
                $logData['error_message'] = $e->getMessage();
                $logData['execution_time_ms'] = $executionTime;

                // Log to database
                $this->logApiCall($logData);
            }

            throw $e;
        }
    }

    /**
     * Log API call to database.
     */
    protected function logApiCall(array $data): void
    {
        try {
            LlmLog::create($data);
        } catch (Exception $e) {
            // Don't let logging failures break the application
            Log::error('Failed to log LLM API call', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Strip markdown code fences from response.
     */
    protected function stripMarkdownFences(string $response): string
    {
        // Remove ```json...``` or ```...``` wrappers
        $response = preg_replace('/^```(?:json)?\s*/m', '', $response);
        $response = preg_replace('/\s*```$/m', '', $response);

        return trim($response);
    }

    /**
     * Parse Writer response to extract SEO fields.
     */
    protected function parseWriterResponse(string $response): array
    {
        // Strip markdown code fences
        $response = $this->stripMarkdownFences($response);

        // Try to extract JSON from response
        if (preg_match('/\{[^{}]*"meta_title"[^{}]*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['meta_title'])) {
                $result = [
                    'meta_title' => $json['meta_title'] ?? '',
                    'meta_description' => $json['meta_description'] ?? '',
                    'meta_keywords' => $json['meta_keywords'] ?? '',
                ];

                // Enforce character limits as safeguard
                return $this->enforceCharacterLimits($result);
            }
        }

        // Fallback: try to parse line by line
        $result = [
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
        ];

        if (preg_match('/meta_title[:\s]+(.+)/i', $response, $matches)) {
            $result['meta_title'] = trim($matches[1], " \t\n\r\"'");
        }
        if (preg_match('/meta_description[:\s]+(.+)/i', $response, $matches)) {
            $result['meta_description'] = trim($matches[1], " \t\n\r\"'");
        }
        if (preg_match('/meta_keywords[:\s]+(.+)/i', $response, $matches)) {
            $result['meta_keywords'] = trim($matches[1], " \t\n\r\"'");
        }

        // Enforce character limits as safeguard
        return $this->enforceCharacterLimits($result);
    }

    /**
     * Enforce character limits on SEO content.
     * Truncates content if it exceeds the limits.
     */
    protected function enforceCharacterLimits(array $content): array
    {
        // meta_title: max 60 characters
        if (isset($content['meta_title']) && strlen($content['meta_title']) > 60) {
            Log::warning('Writer generated meta_title exceeding 60 chars, truncating', [
                'original_length' => strlen($content['meta_title']),
                'original' => $content['meta_title'],
            ]);
            $content['meta_title'] = substr($content['meta_title'], 0, 57) . '...';
        }

        // meta_description: max 160 characters
        if (isset($content['meta_description']) && strlen($content['meta_description']) > 160) {
            Log::warning('Writer generated meta_description exceeding 160 chars, truncating', [
                'original_length' => strlen($content['meta_description']),
                'original' => $content['meta_description'],
            ]);
            $content['meta_description'] = substr($content['meta_description'], 0, 157) . '...';
        }

        return $content;
    }

    /**
     * Parse Auditor response to extract validation results.
     */
    protected function parseAuditorResponse(string $response): array
    {
        // Strip markdown code fences
        $response = $this->stripMarkdownFences($response);

        // Try to extract JSON from response
        if (preg_match('/\{[^{}]*"is_safe"[^{}]*\}/s', $response, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['is_safe'])) {
                return [
                    'is_safe' => (bool) ($json['is_safe'] ?? true),
                    'confidence_score' => (float) ($json['confidence_score'] ?? 0.0),
                    'potential_hallucinations' => $json['potential_hallucinations'] ?? [],
                ];
            }
        }

        // Fallback: conservative defaults
        Log::warning('Could not parse auditor response, using conservative defaults', [
            'response' => $response,
        ]);

        return [
            'is_safe' => false,
            'confidence_score' => 0.5,
            'potential_hallucinations' => [
                [
                    'type' => 'parse_error',
                    'message' => 'Could not parse auditor response',
                    'severity' => 'medium',
                ],
            ],
        ];
    }

    /**
     * Generate content using default prompts when no configuration exists.
     */
    protected function generateWithDefaultPrompts(Product $product, string $type): array
    {
        $systemPrompt = $this->getDefaultWriterSystemPrompt();
        $productData = [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'attributes' => $this->extractSeoRelevantAttributes($product->attributes ?? []),
        ];
        $userPrompt = "Generate SEO metadata for this product:\n\n".json_encode($productData, JSON_PRETTY_PRINT);

        try {
            $response = $this->callGeminiApi($systemPrompt, $userPrompt, null, 'writer', $productData);

            return $this->parseWriterResponse($response);
        } catch (Exception $e) {
            Log::error('Failed to generate with default prompts', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
            throw $e;
        }
    }

    /**
     * Audit content using default prompts when no configuration exists.
     */
    protected function auditWithDefaultPrompts(Product $product, array $generatedContent): array
    {
        $systemPrompt = $this->getDefaultAuditorSystemPrompt();
        $productInfo = [
            'sku' => $product->sku,
            'name' => $product->name,
            'description' => $product->description,
            'attributes' => $this->extractSeoRelevantAttributes($product->attributes ?? []),
        ];
        $userPrompt = "Audit this SEO content against the product data:\n\n".json_encode([
            'product' => $productInfo,
            'generated_content' => $generatedContent,
        ], JSON_PRETTY_PRINT);

        try {
            $response = $this->callGeminiApi($systemPrompt, $userPrompt, null, 'auditor', $productInfo);

            return $this->parseAuditorResponse($response);
        } catch (Exception $e) {
            Log::error('Failed to audit with default prompts', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
            ]);
            throw $e;
        }
    }

    /**
     * Get default Writer system prompt.
     */
    protected function getDefaultWriterSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an SEO content generator. Generate compelling SEO metadata for e-commerce products.

CRITICAL RULES:
1. ONLY use information provided in the product data
2. DO NOT invent features, specifications, or claims
3. Stay factual and accurate to the source data
4. CHARACTER LIMITS ARE MANDATORY:
   - meta_title: MUST be 60 characters or less (strict limit)
   - meta_description: MUST be 160 characters or less (strict limit)
   - meta_keywords: comma-separated list
5. If you cannot fit information within character limits, prioritize the most important features
6. Count characters carefully before outputting

Output format (JSON):
{
  "meta_title": "...",
  "meta_description": "...",
  "meta_keywords": "..."
}

IMPORTANT: Double-check your character counts. meta_title ≤ 60 chars, meta_description ≤ 160 chars.
PROMPT;
    }

    /**
     * Get default Auditor system prompt.
     */
    protected function getDefaultAuditorSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an SEO content auditor. Validate generated content against source product data to detect hallucinations.

CRITICAL RULES:
1. Flag any claims not supported by product data
2. Check for invented features, specifications, or attributes
3. Verify numbers and measurements match source data
4. Be conservative - when in doubt, flag it

Output format (JSON):
{
  "is_safe": true/false,
  "confidence_score": 0.0-1.0,
  "potential_hallucinations": [
    {"type": "unsupported_claim|numerical_mismatch|spec_mismatch", "message": "...", "severity": "high|medium|low"}
  ]
}
PROMPT;
    }
}
