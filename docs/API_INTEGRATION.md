# API Integration Guide

This document details the integration with external APIs, specifically Magento 2 REST API and Google Gemini AI API.

## Table of Contents

- [Magento 2 REST API](#magento-2-rest-api)
- [Google Gemini API](#google-gemini-api)
- [Error Handling](#error-handling)
- [Rate Limiting](#rate-limiting)
- [Troubleshooting](#troubleshooting)

## Magento 2 REST API

### Overview

The platform integrates with Magento 2 via the REST API to fetch product data for SEO generation.

**Base URL**: Configured via `MAGENTO_BASE_URL` in `.env`
**Authentication**: Bearer token (Integration Token)
**API Version**: Magento 2.x REST API v1

### Client Implementation

**File**: `app/Services/Magento/Client.php`

The Magento client is a singleton service that handles all API interactions.

### Configuration

#### Generating Integration Token

1. Log in to Magento Admin Panel
2. Navigate to **System > Extensions > Integrations**
3. Click **Add New Integration**
4. Fill in:
   - Name: "SEO Platform"
   - Your Password: (your admin password)
5. Go to **API** tab
6. Select **Resource Access**: Custom
7. Grant access to:
   - `Catalog > Inventory > Products`
8. Click **Save**
9. Click **Activate** and authorize the integration
10. Copy the **Access Token** to `.env` as `MAGENTO_TOKEN`

#### Environment Variables

```bash
MAGENTO_BASE_URL=https://your-magento-store.com
MAGENTO_TOKEN=your_integration_access_token_here
```

### API Methods

#### Get Single Product

```php
use App\Services\Magento\Client;

$client = app(Client::class);
$product = $client->getProduct(123); // Product ID

// Returns array with product data:
[
    'id' => 123,
    'sku' => 'PRODUCT-SKU',
    'name' => 'Product Name',
    'price' => 99.99,
    'description' => 'Product description...',
    'custom_attributes' => [...],
    'extension_attributes' => [...],
]
```

#### Get Multiple Products

```php
$products = $client->getProducts([123, 456, 789]);

// Returns array of product arrays
```

### Product Data Structure

#### Simple Product

```json
{
  "id": 123,
  "sku": "SIMPLE-PRODUCT",
  "name": "Simple Product Name",
  "price": 49.99,
  "type_id": "simple",
  "attribute_set_id": 4,
  "status": 1,
  "visibility": 4,
  "custom_attributes": [
    {
      "attribute_code": "description",
      "value": "Product description text..."
    },
    {
      "attribute_code": "short_description",
      "value": "Short description..."
    },
    {
      "attribute_code": "meta_title",
      "value": "Existing meta title"
    }
  ]
}
```

#### Bundle Product

```json
{
  "id": 456,
  "sku": "BUNDLE-PRODUCT",
  "name": "Bundle Product Name",
  "type_id": "bundle",
  "extension_attributes": {
    "bundle_product_options": [
      {
        "option_id": 1,
        "title": "Choose Color",
        "required": true,
        "type": "select",
        "product_links": [
          {
            "id": "1",
            "sku": "component-red",
            "qty": 1
          },
          {
            "id": "2",
            "sku": "component-blue",
            "qty": 1
          }
        ]
      }
    ]
  }
}
```

#### Configurable Product

```json
{
  "id": 789,
  "sku": "CONFIGURABLE-PRODUCT",
  "name": "Configurable Product Name",
  "type_id": "configurable",
  "extension_attributes": {
    "configurable_product_options": [
      {
        "id": 1,
        "attribute_id": "93",
        "label": "Color",
        "values": [
          {
            "value_index": 50
          },
          {
            "value_index": 51
          }
        ]
      }
    ],
    "configurable_product_links": [101, 102, 103]
  }
}
```

### Component Enrichment

For bundle and configurable products, the platform automatically enriches component data:

**File**: `app/Services/LLM/WriterAuditor.php` (method: `enrichBundleComponents`, `enrichConfigurableComponents`)

**Process**:
1. Detect product type (`bundle` or `configurable`)
2. Extract component SKUs or product IDs
3. Fetch full product data for each component
4. Merge component details into product data

**Result**: LLM receives complete information about all product variants/components.

### Error Handling

The Magento client throws `MagentoApiException` on failures:

```php
use App\Services\Magento\MagentoApiException;

try {
    $product = $client->getProduct(123);
} catch (MagentoApiException $e) {
    Log::error('Magento API error', [
        'message' => $e->getMessage(),
        'product_id' => 123,
    ]);
}
```

**Common Error Codes**:
- `401 Unauthorized`: Invalid or expired token
- `404 Not Found`: Product doesn't exist
- `500 Internal Server Error`: Magento server issue
- `503 Service Unavailable`: Magento maintenance mode

## Google Gemini API

### Overview

The platform uses Google Gemini API (via OpenAI-compatible endpoint) for AI-powered SEO content generation.

**Model**: `gemini-2.5-flash`
**API**: Google AI Studio / Gemini API
**Authentication**: API Key

### Configuration

#### Generating API Key

1. Visit [Google AI Studio](https://aistudio.google.com/)
2. Sign in with Google account
3. Click **Get API Key**
4. Create a new project or select existing
5. Copy the API key to `.env` as `OPENAI_API_KEY`

#### Environment Variables

```bash
OPENAI_API_KEY=your_google_gemini_api_key_here
```

**Note**: The variable is named `OPENAI_API_KEY` for compatibility with OpenAI-like client libraries, but it's actually a Google Gemini API key.

### API Calls

#### Writer Agent

**Purpose**: Generate SEO metadata from product data.

**Request Structure**:
```json
{
  "contents": [
    {
      "role": "user",
      "parts": [
        {
          "text": "SYSTEM: {system_prompt}\n\nUSER: {user_prompt}"
        }
      ]
    }
  ],
  "generationConfig": {
    "temperature": 0.3,
    "topP": 0.95,
    "topK": 40,
    "maxOutputTokens": 8192,
    "responseMimeType": "application/json"
  }
}
```

**Response Structure**:
```json
{
  "candidates": [
    {
      "content": {
        "parts": [
          {
            "text": "{\"meta_title\":\"...\",\"meta_description\":\"...\",\"meta_keywords\":\"...\"}"
          }
        ]
      }
    }
  ],
  "usageMetadata": {
    "promptTokenCount": 1234,
    "candidatesTokenCount": 56,
    "totalTokenCount": 1290
  }
}
```

#### Auditor Agent

**Purpose**: Validate generated content and detect hallucinations.

**Request**: Similar structure to Writer, but includes both product data and generated SEO content.

**Response**:
```json
{
  "is_safe": true,
  "confidence_score": 0.95,
  "potential_hallucinations": [
    {
      "type": "unsupported_claim",
      "message": "Claim about 'wireless charging' not found in product attributes",
      "severity": "high"
    }
  ]
}
```

### Rate Limiting

**Gemini Free Tier**:
- 60 requests per minute
- 1,500 requests per day
- No cost (as of 2024)

**Gemini Paid Tier**:
- Higher rate limits
- Pay-per-token pricing

**Implementation**: Currently no rate limiting enforced. Consider implementing token bucket pattern for production use.

### Token Usage

Typical token usage per product:

| Agent | Prompt Tokens | Completion Tokens | Total |
|-------|--------------|------------------|-------|
| Writer | 1,000-1,500 | 100-200 | ~1,200-1,700 |
| Auditor | 1,200-1,800 | 150-300 | ~1,400-2,100 |
| **Total** | **~2,500** | **~300** | **~2,800** |

**Cost Estimate** (if using paid tier):
- Assuming $0.001 per 1K tokens
- Cost per product: ~$0.0028
- Cost for 1,000 products: ~$2.80

### Logging

All LLM API calls are logged to the `llm_logs` table:

```php
LlmLog::create([
    'agent_type' => 'writer', // or 'auditor'
    'product_id' => $productId,
    'seo_job_id' => $seoJobId,
    'model' => 'gemini-2.5-flash',
    'api_url' => $url,
    'request_body' => json_encode($request),
    'response_body' => $response->body(),
    'execution_time_ms' => $executionTime,
    'prompt_tokens' => $promptTokens,
    'completion_tokens' => $completionTokens,
    'total_tokens' => $totalTokens,
    'success' => true,
]);
```

**View Logs**: Admin Panel > LLM API Logs

## Error Handling

### Retry Strategy

Both Magento and Gemini API calls implement retry logic:

**Queue Jobs**:
```php
public $tries = 3;
public $backoff = 60; // seconds
```

**HTTP Timeouts**:
```php
Http::timeout(60)->post($url, $body);
```

### Error Logging

All API errors are logged with context:

```php
Log::error('Gemini API call failed', [
    'agent_type' => $agentType,
    'product_id' => $productId,
    'error' => $e->getMessage(),
    'request' => $requestBody,
]);
```

### Graceful Degradation

- **Magento API Down**: Jobs fail and retry, don't block other products
- **Gemini API Down**: Jobs fail and retry, error logged to `llm_logs`
- **Invalid API Response**: Validation errors caught, logged, job marked as failed

## Rate Limiting

### Current Implementation

**Status**: No active rate limiting

**Considerations for Production**:

1. **Magento API**: Implement token bucket pattern
   ```php
   // Pseudo-code
   $bucket = new TokenBucket(rate: 2.0, capacity: 10);
   $bucket->acquire(); // Blocks until token available
   $client->getProduct($id);
   ```

2. **Gemini API**: Respect free tier limits (60 RPM)
   ```php
   // Pseudo-code
   $limiter = RateLimiter::for('gemini', function ($job) {
       return Limit::perMinute(50); // Leave buffer
   });
   ```

3. **Queue Concurrency**: Limit workers
   ```bash
   php artisan queue:work --concurrency=2
   ```

## Troubleshooting

### Magento API Issues

#### 401 Unauthorized

**Cause**: Invalid or expired token
**Solution**:
1. Verify `MAGENTO_TOKEN` in `.env`
2. Check integration is still active in Magento Admin
3. Regenerate token if needed

#### 404 Product Not Found

**Cause**: Product ID doesn't exist
**Solution**:
1. Verify product exists in Magento
2. Check product is enabled and visible
3. Ensure correct store view is used

#### 500 Internal Server Error

**Cause**: Magento server issue
**Solution**:
1. Check Magento logs: `var/log/system.log`, `var/log/exception.log`
2. Verify Magento is not in maintenance mode
3. Check server resources (memory, disk)

### Gemini API Issues

#### 401 Invalid API Key

**Cause**: Wrong or expired API key
**Solution**:
1. Verify `OPENAI_API_KEY` in `.env`
2. Check API key is active in Google AI Studio
3. Regenerate key if needed

#### 429 Rate Limit Exceeded

**Cause**: Too many requests
**Solution**:
1. Implement rate limiting (see above)
2. Reduce queue worker concurrency
3. Consider upgrading to paid tier

#### Invalid JSON Response

**Cause**: LLM returned malformed JSON
**Solution**:
1. Check `llm_logs` table for response body
2. Verify prompt includes JSON format instructions
3. Retry job (may succeed on retry)

### Debugging API Calls

#### View LLM Logs

1. Navigate to **Admin > LLM API Logs**
2. Filter by agent type, status, or product
3. Click **View Details** to see full request/response

#### Enable Debug Logging

```php
// config/logging.php
'channels' => [
    'stack' => [
        'channels' => ['single', 'slack'],
        'level' => 'debug', // Change from 'info'
    ],
],
```

#### Monitor Queue

```bash
# Real-time queue monitoring
php artisan pail

# Check failed jobs
php artisan queue:failed

# Retry specific failed job
php artisan queue:retry {id}
```

## Best Practices

### Magento Integration

1. **Use Integration Tokens**: More secure than admin tokens
2. **Limit API Scope**: Only grant necessary permissions
3. **Handle Rate Limits**: Implement backoff/retry
4. **Cache Product Data**: Reduce redundant API calls
5. **Batch Requests**: Fetch multiple products in one call when possible

### Gemini Integration

1. **Structured Prompts**: Use clear, consistent prompt templates
2. **Temperature Control**: Use low temperature (0.3) for factual content
3. **Response Validation**: Always validate JSON structure
4. **Token Monitoring**: Track usage to predict costs
5. **Error Handling**: Log all failures with full context

### Security

1. **Never Commit API Keys**: Use `.env` and `.env.example`
2. **Rotate Tokens Regularly**: Update Magento and Gemini keys periodically
3. **Use HTTPS**: Always communicate over encrypted connections
4. **Validate Input**: Sanitize all data before sending to APIs
5. **Monitor Usage**: Watch for unusual patterns or unauthorized access

## Further Reading

- [Magento 2 REST API Documentation](https://developer.adobe.com/commerce/webapi/rest/)
- [Google Gemini API Documentation](https://ai.google.dev/docs)
- [Laravel HTTP Client](https://laravel.com/docs/http-client)
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
