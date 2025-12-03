# LLM Pipeline Guide

This document explains the Writer-Auditor LLM pipeline implementation, including prompt engineering, response parsing, and hallucination detection.

## Table of Contents

- [Pipeline Overview](#pipeline-overview)
- [Writer Agent](#writer-agent)
- [Auditor Agent](#auditor-agent)
- [Product Data Enrichment](#product-data-enrichment)
- [Prompt Engineering](#prompt-engineering)
- [Response Validation](#response-validation)
- [Configuration](#configuration)
- [Testing](#testing)

## Pipeline Overview

### Architecture

The Writer-Auditor pipeline is a **two-stage LLM architecture** designed to generate high-quality SEO content while minimizing hallucinations.

```
┌─────────────────┐
│  Product Data   │
│  (from Magento) │
└────────┬────────┘
         │
         ▼
┌────────────────────────────────┐
│      Writer Agent              │
│  (Gemini 2.5 Flash)            │
│                                │
│  Input: Product attributes     │
│  Output: SEO metadata          │
│   - meta_title                 │
│   - meta_description           │
│   - meta_keywords              │
└────────┬───────────────────────┘
         │
         ▼
┌────────────────────────────────┐
│     Auditor Agent              │
│  (Gemini 2.5 Flash)            │
│                                │
│  Input: Product data + SEO     │
│  Output: Validation report     │
│   - is_safe (bool)             │
│   - confidence_score (0-1)     │
│   - potential_hallucinations[] │
└────────┬───────────────────────┘
         │
         ▼
┌────────────────────────────────┐
│      SeoDraft Model            │
│  (Database)                    │
│                                │
│  Status: APPROVED or           │
│          PENDING_REVIEW        │
└────────────────────────────────┘
```

### Key Principles

1. **Writer Must Not Invent**: Only use facts from product data
2. **Auditor Must Be Conservative**: Flag anything questionable
3. **Structured Output**: JSON responses for type safety
4. **Complete Logging**: All API calls logged for debugging
5. **Auto-Approval**: High-confidence safe content approved automatically

## Writer Agent

### Purpose

Generate compelling SEO metadata that:
- Accurately describes the product
- Includes relevant keywords
- Stays within character limits (meta title: 60, meta description: 160)
- Uses only information present in product data

### Implementation

**File**: `app/Services/LLM/WriterAuditor.php`
**Method**: `callWriterAgent(array $productData, LlmConfiguration $config): array`

### System Prompt

The system prompt instructs the Writer on its role and constraints:

```
You are an expert SEO content writer for e-commerce products.
Your task is to generate meta_title, meta_description, and meta_keywords
for the product based ONLY on the provided product data.

CRITICAL RULES:
- ONLY use information explicitly present in the product data
- NEVER invent features, specifications, or benefits
- NEVER make assumptions about product capabilities
- If information is missing, omit it rather than guess
- Focus on factual descriptions, not marketing fluff

Output must be valid JSON with this structure:
{
  "meta_title": "...",
  "meta_description": "...",
  "meta_keywords": "..."
}

Character limits:
- meta_title: 50-60 characters (optimal)
- meta_description: 150-160 characters (optimal)
- meta_keywords: comma-separated, relevant terms
```

### User Prompt

The user prompt contains the actual product data:

```json
{
  "sku": "PRODUCT-SKU",
  "name": "Product Name",
  "description": "Full product description...",
  "attributes": {
    "color": "Red",
    "size": "Large",
    "material": "Cotton",
    "weight": "500g"
  },
  "components": [
    {
      "name": "Component 1",
      "description": "Component description..."
    }
  ]
}
```

### Response Format

```json
{
  "meta_title": "Product Name - Color Size | Brand",
  "meta_description": "Buy Product Name in Red, Large size. Made from premium Cotton material. Weight: 500g. Perfect for...",
  "meta_keywords": "product name, red, large, cotton, weight 500g, category"
}
```

### Temperature Settings

```php
'temperature' => 0.3, // Low temperature for factual, consistent output
'topP' => 0.95,
'topK' => 40,
```

**Reasoning**: Low temperature (0.3) reduces creativity and increases consistency, which is ideal for factual SEO content.

## Auditor Agent

### Purpose

Validate Writer's output by:
- Checking all claims against source product data
- Flagging unsupported claims
- Identifying numerical mismatches
- Detecting specification contradictions
- Assigning confidence score

### Implementation

**File**: `app/Services/LLM/WriterAuditor.php`
**Method**: `callAuditorAgent(array $seoContent, array $productInfo, LlmConfiguration $config): array`

### System Prompt

```
You are a strict fact-checker and hallucination detector for SEO content.
Your task is to validate generated SEO content against the original product data
and flag ANY claims that are not explicitly supported.

VALIDATION RULES:
- Compare every claim in the SEO content to the product data
- Flag claims that cannot be verified from the data
- Flag numerical values that don't match exactly
- Flag specifications that contradict the data
- Be CONSERVATIVE: when in doubt, flag it

Output must be valid JSON:
{
  "is_safe": true|false,
  "confidence_score": 0.0-1.0,
  "potential_hallucinations": [
    {
      "type": "unsupported_claim|numerical_mismatch|spec_mismatch|ambiguous_claim",
      "message": "Description of the issue",
      "severity": "high|medium|low"
    }
  ]
}

Severity guidelines:
- high: Factually incorrect claims, wrong specs
- medium: Unsupported but plausible claims
- low: Minor optimization opportunities
```

### User Prompt

```json
{
  "product_data": {
    "sku": "PRODUCT-SKU",
    "name": "Product Name",
    "description": "...",
    "attributes": {...}
  },
  "generated_seo": {
    "meta_title": "...",
    "meta_description": "...",
    "meta_keywords": "..."
  }
}
```

### Response Format

#### Safe Content (No Issues)

```json
{
  "is_safe": true,
  "confidence_score": 0.95,
  "potential_hallucinations": []
}
```

#### Flagged Content (Issues Found)

```json
{
  "is_safe": false,
  "confidence_score": 0.65,
  "potential_hallucinations": [
    {
      "type": "unsupported_claim",
      "message": "Meta description mentions 'wireless charging' but this feature is not in product attributes",
      "severity": "high"
    },
    {
      "type": "numerical_mismatch",
      "message": "Meta description says 'weighs 450g' but product attributes show 'weight: 500g'",
      "severity": "high"
    },
    {
      "type": "ambiguous_claim",
      "message": "Meta title includes 'premium quality' which is subjective and not in product data",
      "severity": "low"
    }
  ]
}
```

### Confidence Score Calculation

The Auditor assigns a confidence score based on:
- Number of flags: More flags = lower confidence
- Severity of flags: High severity = lower confidence
- Coverage of claims: More verified claims = higher confidence

**Auto-Approval Threshold**: `confidence_score > 0.9` AND `is_safe = true`

## Product Data Enrichment

### Overview

For bundle and configurable products, the platform automatically enriches product data with component details before sending to the Writer.

**File**: `app/Services/LLM/WriterAuditor.php`
**Methods**: `enrichBundleComponents()`, `enrichConfigurableComponents()`

### Bundle Products

**Problem**: Bundle products in Magento only contain component SKUs, not full details.

**Solution**: Fetch and merge component data.

**Before Enrichment**:
```json
{
  "sku": "BUNDLE-001",
  "name": "Starter Kit",
  "type_id": "bundle",
  "product_options": {
    "bundle_options": [
      {
        "title": "Choose Color",
        "product_links": [
          {"sku": "COMP-RED"},
          {"sku": "COMP-BLUE"}
        ]
      }
    ]
  }
}
```

**After Enrichment**:
```json
{
  "sku": "BUNDLE-001",
  "name": "Starter Kit",
  "type_id": "bundle",
  "product_options": {
    "bundle_options": [
      {
        "title": "Choose Color",
        "product_links": [
          {
            "sku": "COMP-RED",
            "name": "Red Component",
            "description": "High-quality red variant...",
            "attributes": {"color": "Red"}
          },
          {
            "sku": "COMP-BLUE",
            "name": "Blue Component",
            "description": "Premium blue variant...",
            "attributes": {"color": "Blue"}
          }
        ]
      }
    ]
  }
}
```

**Benefit**: Writer can include specific component details in SEO content.

### Configurable Products

**Problem**: Configurable products have variants with different attributes.

**Solution**: Fetch all variant data and merge.

**Before Enrichment**:
```json
{
  "sku": "CONFIG-001",
  "name": "T-Shirt",
  "type_id": "configurable",
  "product_options": {
    "configurable_product_links": [101, 102, 103]
  }
}
```

**After Enrichment**:
```json
{
  "sku": "CONFIG-001",
  "name": "T-Shirt",
  "type_id": "configurable",
  "product_options": {
    "configurable_product_links": [101, 102, 103],
    "variants": [
      {
        "id": 101,
        "sku": "TSHIRT-RED-S",
        "name": "T-Shirt - Red - Small",
        "attributes": {"color": "Red", "size": "Small"}
      },
      {
        "id": 102,
        "sku": "TSHIRT-RED-M",
        "name": "T-Shirt - Red - Medium",
        "attributes": {"color": "Red", "size": "Medium"}
      },
      {
        "id": 103,
        "sku": "TSHIRT-BLUE-S",
        "name": "T-Shirt - Blue - Small",
        "attributes": {"color": "Blue", "size": "Small"}
      }
    ]
  }
}
```

**Benefit**: Writer knows all available colors and sizes for comprehensive SEO.

### Implementation Details

```php
protected function enrichBundleComponents(array &$productData): void
{
    if ($productData['product_type'] !== 'bundle') {
        return;
    }

    $bundleOptions = $productData['product_options']['bundle_options'] ?? [];

    foreach ($bundleOptions as &$option) {
        foreach ($option['product_links'] as &$link) {
            $componentSku = $link['sku'];
            $component = Product::where('sku', $componentSku)->first();

            if ($component) {
                $link['name'] = $component->name;
                $link['description'] = $component->description;
                $link['attributes'] = $component->attributes;
            }
        }
    }

    $productData['product_options']['bundle_options'] = $bundleOptions;
}
```

## Prompt Engineering

### Best Practices

1. **Be Explicit**: Clearly state what the LLM should and should not do
2. **Provide Examples**: Show desired output format
3. **Set Constraints**: Define character limits, required fields
4. **Use JSON Schema**: Enforce structured output
5. **Iterate and Test**: Refine prompts based on real results

### Customization

Prompts are stored in the `llm_configurations` table and can be customized per job:

```php
LlmConfiguration::create([
    'name' => 'Custom Prompts for Electronics',
    'writer_system_prompt' => 'You are an expert in electronics SEO...',
    'writer_user_prompt_template' => 'Generate SEO for: {{product_data}}',
    'auditor_system_prompt' => 'You are a strict fact-checker for electronics...',
    'auditor_user_prompt_template' => 'Validate: {{seo_content}} against {{product_data}}',
    'model' => 'gemini-2.5-flash',
]);
```

**Variables in Templates**:
- `{{product_data}}`: Replaced with JSON product data
- `{{seo_content}}`: Replaced with generated SEO content
- `{{product_name}}`, `{{product_sku}}`: Individual fields

### Template Example

```
Generate SEO metadata for the following product:

Product Name: {{product_name}}
SKU: {{product_sku}}
Description: {{product_description}}

Full Data:
{{product_data}}

Remember: Only use information from the data above.
```

## Response Validation

### JSON Parsing

All LLM responses are validated as JSON:

```php
$responseText = $this->callGeminiApi(...);

try {
    $parsed = json_decode($responseText, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    Log::error('Invalid JSON from LLM', ['response' => $responseText]);
    throw new Exception('LLM returned invalid JSON');
}
```

### Schema Validation

**Writer Response Schema**:
```php
$required = ['meta_title', 'meta_description', 'meta_keywords'];

foreach ($required as $field) {
    if (!isset($parsed[$field])) {
        throw new Exception("Missing required field: $field");
    }
}
```

**Auditor Response Schema**:
```php
$required = ['is_safe', 'confidence_score', 'potential_hallucinations'];

foreach ($required as $field) {
    if (!isset($parsed[$field])) {
        throw new Exception("Missing required field: $field");
    }
}

// Validate types
if (!is_bool($parsed['is_safe'])) {
    throw new Exception("is_safe must be boolean");
}

if (!is_numeric($parsed['confidence_score']) || $parsed['confidence_score'] < 0 || $parsed['confidence_score'] > 1) {
    throw new Exception("confidence_score must be between 0 and 1");
}

if (!is_array($parsed['potential_hallucinations'])) {
    throw new Exception("potential_hallucinations must be array");
}
```

### Error Recovery

If validation fails:
1. Log full request and response to `llm_logs`
2. Throw exception to trigger job retry
3. After max retries, mark job as failed
4. Alert admin via failed jobs table

## Configuration

### LlmConfiguration Model

**File**: `app/Models/LlmConfiguration.php`

**Fields**:
- `name`: Display name (e.g., "Default Prompts", "Custom Electronics")
- `writer_system_prompt`: Writer system instructions
- `writer_user_prompt_template`: Writer user prompt template
- `auditor_system_prompt`: Auditor system instructions
- `auditor_user_prompt_template`: Auditor user prompt template
- `model`: LLM model to use (e.g., "gemini-2.5-flash")

### Creating Custom Configurations

**Via Tinker**:
```php
php artisan tinker

LlmConfiguration::create([
    'name' => 'Fashion Products',
    'writer_system_prompt' => 'You are an SEO expert for fashion e-commerce...',
    'writer_user_prompt_template' => 'Generate SEO for fashion product: {{product_data}}',
    'auditor_system_prompt' => 'Validate fashion SEO content...',
    'auditor_user_prompt_template' => 'Check: {{seo_content}} vs {{product_data}}',
    'model' => 'gemini-2.5-flash',
]);
```

**Via Filament**:
1. Navigate to **Admin > LLM Configurations**
2. Click **New Configuration**
3. Fill in prompts and model
4. Save

**Assign to Job**:
```php
SeoJob::create([
    'llm_configuration_id' => 2, // Use custom config
    'product_ids' => [1, 2, 3],
    // ...
]);
```

## Testing

### Unit Tests

**File**: `tests/Unit/WriterAuditorTest.php`

```php
public function test_writer_generates_valid_seo()
{
    $writerAuditor = app(WriterAuditor::class);
    $product = Product::factory()->create();

    $result = $writerAuditor->generate($product);

    $this->assertArrayHasKey('generated_draft', $result);
    $this->assertArrayHasKey('meta_title', $result['generated_draft']);
    $this->assertArrayHasKey('meta_description', $result['generated_draft']);
    $this->assertArrayHasKey('meta_keywords', $result['generated_draft']);
}

public function test_auditor_flags_hallucinations()
{
    $writerAuditor = app(WriterAuditor::class);

    // Mock product data without "wireless" feature
    $productData = ['name' => 'Product', 'description' => 'A product', 'attributes' => []];

    // Mock SEO content claiming "wireless"
    $seoContent = [
        'meta_title' => 'Wireless Product',
        'meta_description' => 'This wireless product...',
        'meta_keywords' => 'wireless, product',
    ];

    $audit = $writerAuditor->callAuditorAgent($seoContent, $productData, null);

    $this->assertFalse($audit['is_safe']);
    $this->assertGreaterThan(0, count($audit['potential_hallucinations']));
}
```

### Integration Tests

**File**: `tests/Feature/SeoJobWorkflowTest.php`

```php
public function test_full_seo_generation_workflow()
{
    Queue::fake();

    $product = Product::factory()->create();
    $job = SeoJob::create([
        'product_ids' => [$product->id],
        'status' => 'PENDING',
    ]);

    ProcessProduct::dispatch($job, $product);

    Queue::assertPushed(ProcessProduct::class);

    // Actually run the job (not faked)
    Queue::fake(false);
    $jobInstance = new ProcessProduct($job, $product);
    $jobInstance->handle(app(WriterAuditor::class));

    $this->assertDatabaseHas('seo_drafts', [
        'product_id' => $product->id,
        'seo_job_id' => $job->id,
    ]);

    $draft = SeoDraft::where('product_id', $product->id)->first();
    $this->assertNotNull($draft->generated_draft);
    $this->assertNotNull($draft->audit_flags);
    $this->assertIsFloat($draft->confidence_score);
}
```

### Manual Testing

```bash
# Test Writer on a single product
php artisan tinker

$product = Product::first();
$writer = app(\App\Services\LLM\WriterAuditor::class);
$result = $writer->generate($product);
dd($result);

# Inspect LLM logs
php artisan tinker

$log = \App\Models\LlmLog::latest()->first();
echo $log->request_body;
echo $log->response_body;
```

## Performance Optimization

### Caching

Cache frequently used configurations:

```php
$config = Cache::remember('llm_config_default', 3600, function () {
    return LlmConfiguration::where('name', 'Default')->first();
});
```

### Batch Processing

Process multiple products in parallel:

```bash
# Run multiple queue workers
php artisan queue:work --concurrency=4
```

### Prompt Optimization

- **Shorter prompts** = fewer tokens = lower cost
- **Remove redundant instructions**
- **Use templates** to avoid repeating common text

## Troubleshooting

### Writer Not Following Instructions

**Symptom**: Writer invents features or ignores constraints.

**Solutions**:
1. Make system prompt more explicit and emphatic (use CAPS, repetition)
2. Lower temperature (try 0.1-0.2)
3. Add examples of correct behavior to prompt
4. Use stronger language: "NEVER", "MUST NOT", "STRICTLY FORBIDDEN"

### Auditor Too Lenient

**Symptom**: Auditor doesn't flag obvious hallucinations.

**Solutions**:
1. Add more explicit validation rules to system prompt
2. Provide examples of hallucinations to detect
3. Use phrase: "When in doubt, flag it as a potential issue"

### Invalid JSON Responses

**Symptom**: LLM returns malformed JSON.

**Solutions**:
1. Add JSON schema example to system prompt
2. Use `responseMimeType: "application/json"` in API request
3. Validate and retry on parse error
4. Inspect `llm_logs` to see what was returned

### Low Confidence Scores

**Symptom**: All drafts marked PENDING_REVIEW.

**Solutions**:
1. Improve product data quality (more detailed descriptions/attributes)
2. Adjust confidence threshold (lower from 0.9 to 0.85)
3. Review Auditor prompt to ensure it's not too conservative

## Further Reading

- [Architecture Guide](ARCHITECTURE.md)
- [API Integration](API_INTEGRATION.md)
- [Google Gemini Documentation](https://ai.google.dev/docs)
- [Prompt Engineering Best Practices](https://ai.google.dev/docs/prompt_best_practices)
