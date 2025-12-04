# Troubleshooting Guide

Common issues and solutions for MageSEO.

## Table of Contents

- [Installation Issues](#installation-issues)
- [API Integration Issues](#api-integration-issues)
- [Queue and Job Issues](#queue-and-job-issues)
- [LLM Pipeline Issues](#llm-pipeline-issues)
- [Filament Admin Issues](#filament-admin-issues)
- [Performance Issues](#performance-issues)
- [Database Issues](#database-issues)

## Installation Issues

### Composer Dependencies Fail to Install

**Symptom**: `composer install` fails with dependency conflicts.

**Solutions**:

1. **Clear Composer cache**:
   ```bash
   composer clear-cache
   composer install
   ```

2. **Update Composer**:
   ```bash
   composer self-update
   ```

3. **Check PHP version**:
   ```bash
   php -v
   # Should be 8.2 or higher
   ```

4. **Install missing PHP extensions**:
   ```bash
   # Ubuntu/Debian
   sudo apt install php8.4-mysql php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-redis
   ```

### npm install Fails

**Symptom**: `npm install` errors or package conflicts.

**Solutions**:

1. **Update Node.js**:
   ```bash
   node -v
   # Should be 18 or higher
   # Update if needed: https://nodejs.org
   ```

2. **Clear npm cache**:
   ```bash
   npm cache clean --force
   rm -rf node_modules package-lock.json
   npm install
   ```

3. **Use correct npm version**:
   ```bash
   npm install -g npm@latest
   ```

### Application Key Not Set

**Symptom**: `No application encryption key has been specified.`

**Solution**:
```bash
php artisan key:generate
```

### Storage Directory Not Writable

**Symptom**: `The stream or file "storage/logs/laravel.log" could not be opened: failed to open stream: Permission denied`

**Solutions**:

1. **Set permissions**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

2. **Set ownership** (if web server runs as www-data):
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   ```

3. **For development**:
   ```bash
   chmod -R 777 storage bootstrap/cache
   # Note: Never use 777 in production!
   ```

## API Integration Issues

### Magento API: 401 Unauthorized

**Symptom**: `MagentoApiException: Unauthorized (401)`

**Causes & Solutions**:

1. **Invalid token**:
   - Verify `MAGENTO_TOKEN` in `.env` is correct
   - Check token hasn't expired in Magento Admin
   - Regenerate token if needed

2. **Wrong Magento URL**:
   - Ensure `MAGENTO_BASE_URL` includes protocol (`https://`)
   - Remove trailing slash from URL
   - Example: `MAGENTO_BASE_URL=https://store.example.com`

3. **Integration disabled**:
   - Log in to Magento Admin
   - Navigate to System > Extensions > Integrations
   - Ensure integration is Active

### Magento API: 404 Product Not Found

**Symptom**: `MagentoApiException: Not Found (404)`

**Causes & Solutions**:

1. **Product doesn't exist**:
   - Verify product ID in Magento
   - Check product is enabled and visible

2. **Wrong store view**:
   - Ensure API request uses correct store view
   - Check product is assigned to store view

3. **Product ID vs SKU confusion**:
   - Magento API uses product ID, not SKU
   - Fetch by SKU: `/V1/products/{sku}`
   - Fetch by ID: `/V1/products/{id}`

### Gemini API: 401 Invalid API Key

**Symptom**: `Gemini API error: 401 Unauthorized`

**Solutions**:

1. **Verify API key**:
   ```bash
   # Check .env file
   cat .env | grep OPENAI_API_KEY
   ```

2. **Regenerate key**:
   - Visit [Google AI Studio](https://aistudio.google.com/)
   - Create new API key
   - Update `.env`:
     ```bash
     OPENAI_API_KEY=your_new_key_here
     ```

3. **Clear config cache**:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

### Gemini API: 429 Rate Limit Exceeded

**Symptom**: `Gemini API error: 429 Too Many Requests`

**Solutions**:

1. **Reduce queue worker concurrency**:
   ```bash
   # Stop workers
   php artisan queue:restart

   # Start with lower concurrency
   php artisan queue:work --concurrency=1
   ```

2. **Implement rate limiting**:
   - Add delay between jobs
   - Upgrade to Gemini paid tier for higher limits

3. **Check usage**:
   - View `llm_logs` table to see request frequency
   - Monitor token usage

### Gemini API: Invalid JSON Response

**Symptom**: JSON parsing error when processing LLM response.

**Solutions**:

1. **Check LLM logs**:
   ```php
   php artisan tinker
   >>> LlmLog::latest()->first()->response_body;
   ```

2. **Verify prompt includes JSON instructions**:
   - Ensure system prompt requests JSON output
   - Add example JSON structure to prompt

3. **Retry job**:
   ```bash
   php artisan queue:retry {job_id}
   ```

## Queue and Job Issues

### Queue Jobs Not Processing

**Symptom**: Jobs stay in `PENDING` status, never complete.

**Solutions**:

1. **Check Redis connection**:
   ```bash
   redis-cli ping
   # Should return: PONG
   ```

2. **Start queue worker**:
   ```bash
   php artisan queue:work
   ```

3. **Check worker is running**:
   ```bash
   ps aux | grep "queue:work"
   ```

4. **Restart queue worker**:
   ```bash
   php artisan queue:restart
   ```

5. **Check for errors**:
   ```bash
   php artisan pail
   # Or
   tail -f storage/logs/laravel.log
   ```

### Jobs Fail Immediately

**Symptom**: All jobs fail without processing.

**Solutions**:

1. **Check failed jobs table**:
   ```bash
   php artisan queue:failed
   ```

2. **View exception details**:
   ```bash
   php artisan tinker
   >>> DB::table('failed_jobs')->latest()->first()->exception;
   ```

3. **Common causes**:
   - Missing API keys in `.env`
   - Database connection issues
   - Service not registered (check providers)

4. **Retry failed jobs**:
   ```bash
   # Retry all
   php artisan queue:retry all

   # Retry specific job
   php artisan queue:retry {id}
   ```

### Jobs Time Out

**Symptom**: Jobs fail with `Illuminate\Queue\MaxAttemptsExceededException`

**Solutions**:

1. **Increase timeout**:
   ```bash
   php artisan queue:work --timeout=180
   ```

2. **Increase max tries in job**:
   ```php
   public $tries = 5;
   public $backoff = [60, 120, 300]; // Exponential backoff
   ```

3. **Optimize LLM calls**:
   - Reduce prompt size
   - Use faster model

### Queue Worker Memory Leak

**Symptom**: Worker consumes increasing memory, crashes.

**Solutions**:

1. **Limit jobs per worker**:
   ```bash
   php artisan queue:work --max-jobs=100
   ```

2. **Limit worker time**:
   ```bash
   php artisan queue:work --max-time=3600
   ```

3. **Use Supervisor to auto-restart**:
   ```ini
   [program:seo-worker]
   command=php artisan queue:work --max-jobs=1000
   autostart=true
   autorestart=true
   ```

## LLM Pipeline Issues

### Writer Generates Hallucinated Content

**Symptom**: Generated SEO content includes features not in product data.

**Solutions**:

1. **Update Writer prompt**:
   - Make instructions more explicit
   - Add warnings about hallucinations
   - Lower temperature (0.2 or 0.1)

2. **Example improved prompt**:
   ```
   CRITICAL: You MUST ONLY use information explicitly present in the product data.
   NEVER invent, assume, or extrapolate features.
   If a feature is not mentioned, DO NOT include it in the SEO content.
   ```

3. **Check product data quality**:
   - Ensure all relevant attributes are included
   - Verify descriptions are comprehensive

### Auditor Flags Everything

**Symptom**: All drafts marked `PENDING_REVIEW`, confidence scores very low.

**Solutions**:

1. **Review Auditor prompt**:
   - May be too conservative
   - Adjust severity guidelines

2. **Lower auto-approval threshold**:
   ```php
   // In ProcessProduct job
   $status = ($is_safe && $confidence_score > 0.85) // Lower from 0.9
       ? 'APPROVED'
       : 'PENDING_REVIEW';
   ```

3. **Check audit flags**:
   - View specific flags in Filament
   - Determine if they're valid concerns

### Auditor Never Flags Anything

**Symptom**: All content auto-approved, confidence always high.

**Solutions**:

1. **Strengthen Auditor prompt**:
   ```
   Be EXTREMELY conservative. Flag ANY claim that is not 100% verifiable from the data.
   When in doubt, FLAG IT.
   ```

2. **Test with known hallucinations**:
   - Manually create SEO content with false claims
   - Run through Auditor
   - Verify it flags the issues

### Bundle/Configurable Product Data Missing

**Symptom**: SEO for bundles/configurables lacks component details.

**Solutions**:

1. **Verify enrichment runs**:
   ```php
   // In WriterAuditor::generate()
   Log::debug('Product type', ['type' => $productData['product_type']]);
   Log::debug('Product options', ['options' => $productData['product_options']]);
   ```

2. **Check Magento data structure**:
   - Fetch product directly via Magento API
   - Verify `extension_attributes` present

3. **Manual enrichment test**:
   ```bash
   php artisan tinker
   >>> $product = Product::where('product_type', 'bundle')->first();
   >>> $writer = app(WriterAuditor::class);
   >>> $result = $writer->generate($product);
   >>> dd($result);
   ```

## Filament Admin Issues

### 403 Forbidden on Admin Panel

**Symptom**: Cannot access `/admin` route, 403 error.

**Solutions**:

1. **Check user exists**:
   ```bash
   php artisan tinker
   >>> User::first();
   ```

2. **Seed default admin**:
   ```bash
   php artisan db:seed --class=UserSeeder
   ```

3. **Check Filament auth config**:
   ```php
   // config/filament.php
   'auth' => [
       'guard' => 'web',
   ],
   ```

### Custom Blade Views Not Rendering

**Symptom**: `ViewField` components show blank or HTML as text.

**Solutions**:

1. **Clear view cache**:
   ```bash
   php artisan view:clear
   ```

2. **Check view file exists**:
   ```bash
   ls -la resources/views/filament/forms/components/
   ```

3. **Verify ViewField syntax**:
   ```php
   ViewField::make('field_name')
       ->view('filament.forms.components.my-view')
       ->columnSpanFull();
   ```

4. **Check Blade syntax in view**:
   - Ensure no unclosed tags
   - Verify `@php @endphp` blocks are correct

### Infolist/Form Not Displaying

**Symptom**: Empty form or infolist in Filament resource.

**Solutions**:

1. **Check resource methods**:
   ```php
   public static function form(Form $form): Form
   {
       return ProductForm::configure($form);
   }
   ```

2. **Verify schema files exist**:
   ```bash
   ls app/Filament/Resources/Products/Schemas/
   ```

3. **Clear Filament cache**:
   ```bash
   php artisan filament:clear-cache
   ```

### Filament Icons Not Showing

**Symptom**: Icons appear as empty boxes or broken.

**Solutions**:

1. **Rebuild assets**:
   ```bash
   npm run build
   ```

2. **Check icon syntax**:
   ```php
   protected static ?string $navigationIcon = 'heroicon-o-cube';
   // Not: 'heroicon-cube' (missing 'o-')
   ```

3. **Clear browser cache**:
   - Hard refresh: Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac)

## Performance Issues

### Slow Page Loads

**Symptom**: Admin panel pages take 5+ seconds to load.

**Solutions**:

1. **Enable query logging**:
   ```php
   DB::enableQueryLog();
   // ... run code
   dd(DB::getQueryLog());
   ```

2. **Optimize queries with eager loading**:
   ```php
   // Bad (N+1)
   $drafts = SeoDraft::all();
   foreach ($drafts as $draft) {
       echo $draft->product->name; // N queries
   }

   // Good
   $drafts = SeoDraft::with('product')->get();
   ```

3. **Add database indexes**:
   ```sql
   CREATE INDEX idx_seo_drafts_status ON seo_drafts(status);
   ```

4. **Enable opcache** (production):
   ```ini
   ; php.ini
   opcache.enable=1
   ```

### High Memory Usage

**Symptom**: PHP process uses excessive memory (1GB+).

**Solutions**:

1. **Increase PHP memory limit**:
   ```ini
   ; php.ini
   memory_limit = 512M
   ```

2. **Process records in chunks**:
   ```php
   Product::chunk(100, function ($products) {
       foreach ($products as $product) {
           // Process
       }
   });
   ```

3. **Use cursor for large datasets**:
   ```php
   foreach (Product::cursor() as $product) {
       // Low memory usage
   }
   ```

### Database Queries Slow

**Symptom**: Individual queries take seconds to execute.

**Solutions**:

1. **Analyze query**:
   ```sql
   EXPLAIN SELECT * FROM seo_drafts WHERE status = 'PENDING_REVIEW';
   ```

2. **Add missing indexes**:
   ```sql
   CREATE INDEX idx_status ON seo_drafts(status);
   ```

3. **Optimize MySQL**:
   ```ini
   ; my.cnf
   innodb_buffer_pool_size = 1G
   ```

## Database Issues

### Migration Fails

**Symptom**: `php artisan migrate` errors.

**Solutions**:

1. **Check database exists**:
   ```bash
   mysql -u root -p -e "SHOW DATABASES;"
   ```

2. **Create database if missing**:
   ```sql
   CREATE DATABASE mageseo;
   ```

3. **Verify credentials**:
   ```bash
   # Test connection
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

4. **Rollback and retry**:
   ```bash
   php artisan migrate:rollback
   php artisan migrate
   ```

### Data Not Saving

**Symptom**: Model changes not persisted to database.

**Solutions**:

1. **Check `$fillable` property**:
   ```php
   protected $fillable = ['field1', 'field2'];
   ```

2. **Verify database transaction**:
   ```php
   DB::beginTransaction();
   try {
       $model->save();
       DB::commit();
   } catch (\Exception $e) {
       DB::rollback();
       throw $e;
   }
   ```

3. **Check for validation errors**:
   ```php
   if (!$model->save()) {
       dd($model->errors());
   }
   ```

### Connection Timeout

**Symptom**: `SQLSTATE[HY000] [2002] Connection timed out`

**Solutions**:

1. **Check MySQL is running**:
   ```bash
   sudo systemctl status mysql
   ```

2. **Verify host/port**:
   ```bash
   # .env
   DB_HOST=127.0.0.1  # Not 'localhost' if using TCP
   DB_PORT=3306
   ```

3. **Test direct connection**:
   ```bash
   mysql -h 127.0.0.1 -u root -p
   ```

## Getting More Help

### Enable Debug Mode

**Development Only**:
```bash
# .env
APP_DEBUG=true
LOG_LEVEL=debug
```

**Never enable in production!**

### Check Logs

```bash
# Real-time logs
php artisan pail

# Tail log file
tail -f storage/logs/laravel.log

# Last 100 lines
tail -n 100 storage/logs/laravel.log

# Search for errors
grep "ERROR" storage/logs/laravel.log
```

### Test API Connections

```bash
php artisan tinker

# Test Magento
>>> $client = app(\App\Services\Magento\Client::class);
>>> $client->getProduct(123);

# Test Gemini
>>> $writer = app(\App\Services\LLM\WriterAuditor::class);
>>> $product = Product::first();
>>> $writer->generate($product);
```

### Community Support

- **GitHub Issues**: [Report bugs](https://github.com/florinel-chis/mage-seo/issues)
- **GitHub Discussions**: [Ask questions](https://github.com/florinel-chis/mage-seo/discussions)
- **Stack Overflow**: Tag `laravel` and `filament`

### Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check application health
php artisan about

# List routes
php artisan route:list

# List registered services
php artisan list

# Inspect database
php artisan db:show
php artisan db:table seo_drafts
```

## Further Reading

- [Laravel Logging Documentation](https://laravel.com/docs/logging)
- [Laravel Queue Troubleshooting](https://laravel.com/docs/queues#dealing-with-failed-jobs)
- [Filament Documentation](https://filamentphp.com/docs)
- [Architecture Guide](ARCHITECTURE.md)
- [Development Guide](DEVELOPMENT.md)
