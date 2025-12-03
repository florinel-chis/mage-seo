# Architecture Guide

This document provides a comprehensive overview of the Laravel SEO Platform's architecture, design patterns, and component interactions.

## Table of Contents

- [System Overview](#system-overview)
- [Core Components](#core-components)
- [Data Flow](#data-flow)
- [Database Design](#database-design)
- [Service Layer](#service-layer)
- [Queue Architecture](#queue-architecture)
- [Frontend Architecture](#frontend-architecture)

## System Overview

The Laravel SEO Platform is built using a **service-oriented architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                     Filament Admin Panel                     │
│  (User Interface for Management & Review)                    │
└────────────────┬────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│                   Laravel Application                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Controllers │  │   Services   │  │    Models    │     │
│  │  (Filament)  │  │  (Business)  │  │  (Eloquent)  │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└────────────────┬────────────────────────────────────────────┘
                 │
       ┌─────────┼─────────┐
       │         │         │
┌──────▼─────┐ ┌▼─────────▼┐ ┌──────────────┐
│   MySQL    │ │   Redis    │ │ Magento API  │
│  Database  │ │   Queue    │ │ (External)   │
└────────────┘ └────────────┘ └──────────────┘
                                      │
                                ┌─────▼──────┐
                                │  Gemini AI │
                                │   (LLM)    │
                                └────────────┘
```

## Core Components

### 1. Filament Admin Panel

**Purpose**: Provides the user interface for all management operations.

**Key Resources**:
- `MagentoStoreResource`: Configure Magento integrations
- `ProductResource`: View and manage product catalog
- `LlmConfigurationResource`: Customize prompts and model settings
- `SeoJobResource`: Create and monitor bulk generation jobs
- `SeoDraftResource`: Review, approve, and edit generated content
- `LlmLogResource`: Inspect API calls and debug issues

**Location**: `app/Filament/Resources/`

### 2. Service Layer

**Purpose**: Encapsulates business logic and external API interactions.

#### WriterAuditor Service

**File**: `app/Services/LLM/WriterAuditor.php`

**Responsibilities**:
- Orchestrate Writer-Auditor pipeline
- Call Google Gemini API with structured prompts
- Parse and validate LLM responses
- Log all API interactions
- Handle errors and retries

**Key Methods**:
```php
public function generate(Product $product, ?LlmConfiguration $config = null): array
public function setContext(?int $productId, ?int $seoDraftId, ?int $seoJobId): self
protected function callWriterAgent(array $productData, LlmConfiguration $config): array
protected function callAuditorAgent(array $seoContent, array $productInfo, LlmConfiguration $config): array
protected function callGeminiApi(string $systemPrompt, string $userPrompt, ?LlmConfiguration $config, string $agentType, ?array $productData): string
```

**Service Provider**: `App\Providers\LlmServiceProvider` (Singleton)

#### Magento Client

**File**: `app/Services/Magento/Client.php`

**Responsibilities**:
- Authenticate with Magento REST API
- Fetch product data by ID
- Handle API errors and rate limiting
- Transform Magento responses

**Key Methods**:
```php
public function getProduct(int $productId): array
public function getProducts(array $productIds): array
```

**Service Provider**: `App\Providers\MagentoServiceProvider` (Singleton)

### 3. Queue System

**Purpose**: Process long-running tasks asynchronously.

#### ProcessProduct Job

**File**: `app/Jobs/ProcessProduct.php`

**Workflow**:
1. Receive `SeoJob` and `Product` models
2. Set logging context on `WriterAuditor`
3. Call `WriterAuditor::generate()`
4. Create `SeoDraft` with generated content and audit results
5. Auto-approve if `confidence_score > 0.9` and `is_safe = true`
6. Increment `SeoJob::processed_products`
7. Update job status to `COMPLETED` when all products processed

**Error Handling**: Failed jobs are retried up to 3 times with exponential backoff.

#### FetchMagentoProductsJob

**File**: `app/Jobs/FetchMagentoProductsJob.php`

**Purpose**: Bulk sync product catalog from Magento store.

**Configuration**:
- Connection: Redis
- Queue: `default`
- Timeout: 90 seconds
- Max Tries: 3

### 4. Model Layer

**Purpose**: Represent database entities and relationships.

#### Key Models

**Product** (`app/Models/Product.php`)
- Stores product data synced from Magento
- JSON field: `attributes` (custom product attributes)
- JSON fields: `product_type`, `product_options` (for bundles/configurables)
- Relationships: `hasMany(SeoDraft)`

**SeoDraft** (`app/Models/SeoDraft.php`)
- Stores generated SEO content and audit results
- JSON fields: `original_data`, `generated_draft`, `audit_flags`
- Status: `PENDING_REVIEW`, `APPROVED`, `REJECTED`, `SYNCED`
- Relationships: `belongsTo(Product)`, `belongsTo(SeoJob)`, `hasMany(LlmLog)`

**SeoJob** (`app/Models/SeoJob.php`)
- Tracks bulk generation jobs
- Fields: `status`, `total_products`, `processed_products`, `product_ids` (JSON)
- Relationships: `hasMany(SeoDraft)`, `belongsTo(LlmConfiguration)`

**LlmLog** (`app/Models/LlmLog.php`)
- Complete observability for LLM API calls
- Request/response logging with full payloads
- Performance metrics: execution time, token usage
- Relationships: `belongsTo(Product)`, `belongsTo(SeoDraft)`, `belongsTo(SeoJob)`

## Data Flow

### SEO Generation Flow

```
1. User Action
   └─> Filament: Create SeoJob
       └─> Controller: Store job in database
           └─> Dispatch ProcessProduct jobs to queue

2. Queue Processing (per product)
   └─> ProcessProduct::handle()
       └─> WriterAuditor::setContext()
       └─> WriterAuditor::generate()
           ├─> Enrich product data (bundles/configurables)
           ├─> Call Writer Agent (Gemini API)
           │   └─> Log request/response (LlmLog)
           ├─> Call Auditor Agent (Gemini API)
           │   └─> Log request/response (LlmLog)
           └─> Return merged result
       └─> Create SeoDraft
           ├─> Auto-approve if confidence > 0.9
           └─> PENDING_REVIEW otherwise
       └─> Increment SeoJob::processed_products

3. User Review
   └─> Filament: SeoDrafts list
       └─> Filter by status
       └─> View draft details
           ├─> See original product data
           ├─> See generated content (editable)
           ├─> See audit summary
           └─> Approve/Reject/Edit
```

### Magento Product Sync Flow

```
1. User Action
   └─> Filament: Sync Products from MagentoStore

2. Background Job
   └─> FetchMagentoProductsJob::handle()
       └─> MagentoClient::getProducts()
           └─> For each product:
               ├─> Fetch full product data
               ├─> Enrich bundle/configurable components
               └─> Upsert Product model

3. Result
   └─> Products available for SEO generation
```

## Database Design

### Entity Relationship Diagram

```
┌─────────────────┐
│  MagentoStore   │
│─────────────────│
│ id              │
│ name            │
│ base_url        │
│ token           │
│ last_sync_at    │
└─────────────────┘
         │
         │ 1:N (products synced from)
         │
         ▼
┌─────────────────┐       ┌──────────────────┐
│    Product      │◄──────┤   SeoDraft       │
│─────────────────│  1:N  │──────────────────│
│ id              │       │ id               │
│ sku             │       │ product_id       │
│ name            │       │ seo_job_id       │
│ description     │       │ original_data    │
│ attributes      │       │ generated_draft  │
│ product_type    │       │ audit_flags      │
│ product_options │       │ confidence_score │
└─────────────────┘       │ status           │
         │                └──────────────────┘
         │                        │
         │                        │ N:1
         │                        ▼
         │                ┌──────────────────┐
         └────────────────┤    SeoJob        │
                     N:1  │──────────────────│
                          │ id               │
                          │ llm_config_id    │
                          │ status           │
                          │ total_products   │
                          │ processed_products│
                          │ product_ids      │
                          └──────────────────┘
                                  │
                                  │ N:1
                                  ▼
                          ┌──────────────────┐
                          │LlmConfiguration  │
                          │──────────────────│
                          │ id               │
                          │ name             │
                          │ writer_prompt    │
                          │ auditor_prompt   │
                          │ model            │
                          └──────────────────┘

┌─────────────────┐
│    LlmLog       │
│─────────────────│
│ id              │
│ agent_type      │
│ product_id      │──────┐
│ seo_draft_id    │──────┤ Foreign keys
│ seo_job_id      │──────┘
│ request_body    │
│ response_body   │
│ execution_time  │
│ total_tokens    │
└─────────────────┘
```

### Key Design Decisions

1. **JSON Fields**: Used for flexible data storage (attributes, audit flags, original product snapshots)
2. **Status Enums**: String-based for readability and database portability
3. **Soft Deletes**: Not used; audit trail maintained via LlmLog
4. **Timestamps**: All models have `created_at` and `updated_at`
5. **Foreign Keys**: Nullable with `SET NULL` on delete for historical data preservation

## Service Layer

### Design Patterns

#### Singleton Pattern

Both `WriterAuditor` and `MagentoClient` are registered as singletons:

```php
// LlmServiceProvider
$this->app->singleton(WriterAuditor::class, function ($app) {
    return new WriterAuditor(config('services.openai.api_key'));
});

// MagentoServiceProvider
$this->app->singleton(Client::class, function ($app) {
    return new Client(
        config('services.magento.base_url'),
        config('services.magento.token')
    );
});
```

**Benefit**: Reuse HTTP clients, maintain state (e.g., logging context), avoid repeated initialization.

#### Dependency Injection

Services are injected via method parameters:

```php
public function handle(WriterAuditor $writerAuditor): void
{
    $writerAuditor->setContext(...);
    $result = $writerAuditor->generate($this->product);
}
```

**Benefit**: Testability, loose coupling, Laravel container handles instantiation.

## Queue Architecture

### Redis Configuration

```php
// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],
```

### Job Lifecycle

1. **Dispatch**: `ProcessProduct::dispatch($seoJob, $product)`
2. **Serialize**: Job data serialized and pushed to Redis
3. **Worker**: `php artisan queue:work` picks up job
4. **Execute**: `handle()` method runs
5. **Complete**: Job removed from queue on success
6. **Retry**: On failure, retry up to `$tries` times
7. **Failed**: Move to `failed_jobs` table after max retries

### Monitoring

- `php artisan queue:work --verbose`: See real-time processing
- `php artisan queue:failed`: View failed jobs
- `php artisan queue:retry all`: Retry all failed jobs
- Filament dashboard: Monitor `SeoJob::processed_products` vs `total_products`

## Frontend Architecture

### Filament Structure

Filament resources follow a consistent structure:

```
app/Filament/Resources/{ModelName}/
├── {ModelName}Resource.php     # Main resource definition
├── Pages/
│   ├── List{ModelName}.php     # List/table view
│   ├── Create{ModelName}.php   # Create form (if applicable)
│   ├── Edit{ModelName}.php     # Edit form
│   └── View{ModelName}.php     # Read-only detail view
├── Schemas/
│   ├── {ModelName}Form.php     # Form schema (create/edit)
│   └── {ModelName}Infolist.php # Infolist schema (view)
└── Tables/
    └── {ModelName}Table.php    # Table configuration (columns, filters, actions)
```

### Custom Blade Views

For complex UI elements, custom Blade views are used with `ViewField`:

```php
ViewField::make('audit_summary')
    ->view('filament.forms.components.audit-summary')
    ->columnSpanFull();
```

**Location**: `resources/views/filament/forms/components/`

**Examples**:
- `audit-summary.blade.php`: Displays audit flags with severity badges
- `original-product-data.blade.php`: Pretty-printed JSON viewer

## Performance Considerations

### Optimization Strategies

1. **Eager Loading**: Prevent N+1 queries
   ```php
   $drafts = SeoDraft::with(['product', 'seoJob', 'llmLogs'])->get();
   ```

2. **Queue Chunking**: Process large jobs in batches
   ```php
   foreach ($productIds->chunk(100) as $chunk) {
       ProcessProduct::dispatch($seoJob, $chunk);
   }
   ```

3. **Caching**: Store frequently accessed data
   ```php
   Cache::remember('llm_config_default', 3600, fn() => LlmConfiguration::first());
   ```

4. **Database Indexing**:
   - `products(sku)` - Unique index
   - `seo_drafts(status)` - For filtering
   - `llm_logs(created_at)` - For time-based queries

### Scaling Considerations

- **Horizontal Scaling**: Add more queue workers
- **Vertical Scaling**: Increase Redis memory, MySQL resources
- **Rate Limiting**: Implement for Magento API (future enhancement)
- **LLM Costs**: Monitor token usage via `LlmLog` aggregations

## Security Architecture

### Authentication

- Filament uses Laravel's built-in authentication
- Default guard: `web`
- Admin users stored in `users` table

### Authorization

- Filament policies control resource access
- Gates can be defined in `AuthServiceProvider`

### API Security

- **Magento Token**: Stored in `.env`, never committed
- **Gemini API Key**: Stored in `.env`, passed securely via HTTPS
- **Input Validation**: Pydantic-style validation on LLM responses

### Data Protection

- **Sensitive Data**: Never logged (API keys, passwords)
- **JSON Escaping**: All user input escaped in Blade views
- **SQL Injection**: Protected by Eloquent query builder

## Error Handling

### Exception Hierarchy

```
Exception
├── MagentoApiException (custom)
│   └── Thrown by Magento Client on API errors
└── General Laravel Exceptions
    ├── ModelNotFoundException
    ├── ValidationException
    └── HttpException
```

### Logging Strategy

- **Application Logs**: `storage/logs/laravel.log`
- **Queue Logs**: `php artisan pail` (real-time)
- **LLM Logs**: `llm_logs` table (structured database logging)

**Log Levels**:
- `ERROR`: API failures, job failures
- `WARNING`: Audit flags, low confidence scores
- `INFO`: Job completions, product syncs
- `DEBUG`: Request/response details (via LlmLog)

## Testing Strategy

### Test Organization

```
tests/
├── Unit/
│   ├── WriterAuditorTest.php
│   ├── MagentoClientTest.php
│   └── Models/
│       ├── ProductTest.php
│       └── SeoDraftTest.php
├── Feature/
│   ├── SeoJobWorkflowTest.php
│   ├── SeoDraftApprovalTest.php
│   └── LlmLoggingTest.php
└── TestCase.php
```

### Testing Best Practices

1. **Database Transactions**: Use `RefreshDatabase` trait
2. **Mocking External APIs**: Mock `WriterAuditor` and `MagentoClient`
3. **Queue Testing**: Use `Queue::fake()`
4. **Factories**: Define factories for all models

## Future Enhancements

- **Rate Limiting**: Token bucket for Magento API
- **Webhook Support**: Magento push notifications
- **Multi-Tenancy**: Support multiple organizations
- **Advanced Analytics**: Dashboard with charts and insights
- **Batch Operations**: Bulk approve/reject drafts
- **Sync to Magento**: Push approved content back to Magento
- **A/B Testing**: Compare LLM configurations

## Conclusion

This architecture provides a solid foundation for scalable, maintainable SEO automation. The service-oriented design, queue-based processing, and comprehensive logging ensure production readiness while maintaining developer productivity.

For implementation details, see:
- [LLM Pipeline](LLM_PIPELINE.md)
- [API Integration](API_INTEGRATION.md)
- [Development Guide](DEVELOPMENT.md)
