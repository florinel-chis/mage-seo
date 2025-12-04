# Development Guide

This guide helps developers set up a local development environment and contribute to MageSEO.

## Table of Contents

- [Local Setup](#local-setup)
- [Development Workflow](#development-workflow)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Database Management](#database-management)
- [Debugging](#debugging)
- [Contributing](#contributing)

## Local Setup

### Prerequisites

Install the following on your development machine:

- PHP 8.2+ ([php.net](https://php.net))
- Composer ([getcomposer.org](https://getcomposer.org))
- MySQL 8.0+ or Docker
- Redis or Docker
- Node.js 18+ and npm ([nodejs.org](https://nodejs.org))
- Git

### Installation Steps

#### Option 1: Local Installation

```bash
# Clone repository
git clone https://github.com/florinel-chis/mage-seo.git
cd mageseo

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Configure .env for local development
# Edit database, Redis, and API credentials
nano .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database with test data
php artisan db:seed

# Build assets
npm run dev
```

**Start Development Servers**:

```bash
# Terminal 1: Laravel development server
./start.sh
# Or manually: php artisan serve --port=8003

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Vite dev server (for hot reload)
npm run dev

# Terminal 4: Real-time logs (optional)
php artisan pail
```

**Access Application**:
- Homepage: http://localhost:8003
- Admin Panel: http://localhost:8003/admin
- Default Login: admin@example.com / password

#### Option 2: Docker Installation

```bash
# Start all services
docker-compose up -d

# Install dependencies inside container
docker exec -it laravel-app composer install
docker exec -it laravel-app npm install

# Run migrations
docker exec -it laravel-app php artisan migrate

# Seed database
docker exec -it laravel-app php artisan db:seed

# Build assets
docker exec -it laravel-app npm run build
```

**Access Application**: http://localhost

### Environment Configuration

**`.env` for Local Development**:

```bash
APP_NAME="SEO Platform (Local)"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8003

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mageseo
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

# Use test Magento store or leave empty
MAGENTO_BASE_URL=
MAGENTO_TOKEN=

# Use your development API key
OPENAI_API_KEY=your_gemini_api_key_here
```

## Development Workflow

### Branch Strategy

- **main**: Production-ready code
- **develop**: Development branch (merge PRs here)
- **feature/feature-name**: Feature branches
- **bugfix/bug-description**: Bug fix branches
- **hotfix/critical-fix**: Emergency production fixes

### Creating a Feature

```bash
# Create feature branch from develop
git checkout develop
git pull origin develop
git checkout -b feature/my-new-feature

# Make changes...
# Commit frequently with clear messages
git add .
git commit -m "Add: Implement new feature X"

# Push to GitHub
git push origin feature/my-new-feature

# Create Pull Request on GitHub
```

### Commit Message Conventions

Follow conventional commits:

- `Add: New feature or file`
- `Update: Modify existing feature`
- `Fix: Bug fix`
- `Refactor: Code refactoring without behavior change`
- `Docs: Documentation changes`
- `Test: Add or update tests`
- `Style: Code formatting (no logic change)`
- `Chore: Maintenance tasks, dependency updates`

**Examples**:
```
Add: Implement LLM logging system
Fix: Resolve audit summary icon size issue
Update: Improve bundle component enrichment
Refactor: Extract WriterAuditor methods
Docs: Add API integration guide
Test: Add unit tests for WriterAuditor
```

## Code Standards

### PSR-12 Compliance

Use Laravel Pint to format code:

```bash
# Format all files
./vendor/bin/pint

# Format specific directory
./vendor/bin/pint app/Services

# Check without fixing
./vendor/bin/pint --test
```

### Code Style Guidelines

**Controllers**:
```php
class SeoDraftController extends Controller
{
    public function index(): View
    {
        $drafts = SeoDraft::with('product')->paginate(20);

        return view('seo-drafts.index', compact('drafts'));
    }
}
```

**Services**:
```php
class WriterAuditor
{
    public function __construct(
        private string $apiKey
    ) {}

    public function generate(Product $product, ?LlmConfiguration $config = null): array
    {
        // Implementation
    }
}
```

**Models**:
```php
class SeoDraft extends Model
{
    protected $fillable = ['product_id', 'seo_job_id', 'status'];

    protected $casts = [
        'original_data' => 'array',
        'generated_draft' => 'array',
        'audit_flags' => 'array',
        'confidence_score' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### Filament Resource Structure

Follow project conventions:

```
app/Filament/Resources/{ModelName}/
├── {ModelName}Resource.php
├── Pages/
│   ├── List{ModelName}.php
│   ├── Create{ModelName}.php
│   ├── Edit{ModelName}.php
│   └── View{ModelName}.php
├── Schemas/
│   ├── {ModelName}Form.php
│   └── {ModelName}Infolist.php
└── Tables/
    └── {ModelName}Table.php
```

**Example Resource**:
```php
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function form(Form $form): Form
    {
        return ProductForm::configure($form);
    }
}
```

## Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/WriterAuditorTest.php

# Run with coverage
php artisan test --coverage

# Run with parallel execution
php artisan test --parallel
```

### Writing Tests

**Unit Test Example**:
```php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LLM\WriterAuditor;
use App\Models\Product;

class WriterAuditorTest extends TestCase
{
    public function test_generates_valid_seo_content(): void
    {
        $writer = app(WriterAuditor::class);
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'description' => 'Test description',
        ]);

        $result = $writer->generate($product);

        $this->assertArrayHasKey('generated_draft', $result);
        $this->assertArrayHasKey('meta_title', $result['generated_draft']);
        $this->assertNotEmpty($result['generated_draft']['meta_title']);
    }
}
```

**Feature Test Example**:
```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\SeoJob;
use App\Models\Product;
use Illuminate\Support\Facades\Queue;

class SeoJobWorkflowTest extends TestCase
{
    public function test_creating_job_dispatches_queue(): void
    {
        Queue::fake();

        $product = Product::factory()->create();
        $job = SeoJob::create([
            'product_ids' => [$product->id],
            'status' => 'PENDING',
        ]);

        // Trigger job dispatch logic
        // ...

        Queue::assertPushed(ProcessProduct::class);
    }
}
```

### Test Database

Tests use a separate database configured in `phpunit.xml`:

```xml
<env name="DB_DATABASE" value="mageseo_test"/>
```

Create the test database:
```bash
mysql -u root -p -e "CREATE DATABASE mageseo_test"
```

### Factories

Define model factories in `database/factories/`:

```php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->regexify('[A-Z]{3}[0-9]{6}'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'attributes' => [
                'color' => $this->faker->safeColorName(),
                'size' => $this->faker->randomElement(['Small', 'Medium', 'Large']),
            ],
        ];
    }
}
```

**Usage**:
```php
$product = Product::factory()->create();
$products = Product::factory()->count(10)->create();
```

## Database Management

### Migrations

**Create Migration**:
```bash
php artisan make:migration create_table_name
```

**Run Migrations**:
```bash
# Run all pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Reset and re-run all migrations
php artisan migrate:fresh

# Reset, re-run, and seed
php artisan migrate:fresh --seed
```

**Migration Example**:
```php
public function up(): void
{
    Schema::create('seo_drafts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        $table->foreignId('seo_job_id')->constrained()->onDelete('cascade');
        $table->json('original_data')->nullable();
        $table->json('generated_draft')->nullable();
        $table->json('audit_flags')->nullable();
        $table->decimal('confidence_score', 3, 2)->nullable();
        $table->string('status')->default('PENDING_REVIEW');
        $table->timestamps();
    });
}
```

### Seeders

**Create Seeder**:
```bash
php artisan make:seeder ProductSeeder
```

**Seeder Example**:
```php
public function run(): void
{
    Product::factory()->count(50)->create();

    LlmConfiguration::create([
        'name' => 'Default Prompts',
        'writer_system_prompt' => 'You are an SEO expert...',
        'auditor_system_prompt' => 'You are a fact-checker...',
        'model' => 'gemini-2.5-flash',
    ]);
}
```

**Run Seeders**:
```bash
php artisan db:seed
php artisan db:seed --class=ProductSeeder
```

### Tinker (REPL)

Interactive PHP shell for testing:

```bash
php artisan tinker

# Examples
>>> $product = Product::first();
>>> $writer = app(App\Services\LLM\WriterAuditor::class);
>>> $result = $writer->generate($product);
>>> dd($result);

>>> SeoDraft::where('status', 'PENDING_REVIEW')->count();
>>> LlmLog::latest()->first()->response_body;
```

## Debugging

### Laravel Debugbar

Install for development (optional):
```bash
composer require barryvdh/laravel-debugbar --dev
```

Displays queries, views, routes, and performance metrics at bottom of page.

### Logging

**Log Levels**:
```php
use Illuminate\Support\Facades\Log;

Log::debug('Debug information', ['context' => $data]);
Log::info('Informational message');
Log::warning('Warning message');
Log::error('Error occurred', ['exception' => $e]);
```

**View Logs**:
```bash
# Real-time log monitoring
php artisan pail

# Or tail log file
tail -f storage/logs/laravel.log
```

### Query Debugging

**Enable Query Log**:
```php
DB::enableQueryLog();

// Run queries...

dd(DB::getQueryLog());
```

**Log All Queries**:
```php
// In AppServiceProvider::boot()
DB::listen(function ($query) {
    Log::debug('Query executed', [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
    ]);
});
```

### Xdebug Setup

Install Xdebug for step debugging:

```bash
sudo apt install php8.4-xdebug
```

Configure `php.ini`:
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

**VS Code** `launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}"
            }
        }
    ]
}
```

## Contributing

### Pull Request Process

1. **Fork** the repository
2. **Create feature branch** from `develop`
3. **Make changes** following code standards
4. **Add tests** for new features
5. **Run tests** and ensure they pass
6. **Format code** with Laravel Pint
7. **Commit** with clear messages
8. **Push** to your fork
9. **Create Pull Request** to `develop` branch
10. **Address review feedback**

### PR Checklist

- [ ] Code follows PSR-12 standards
- [ ] Tests added/updated and passing
- [ ] Documentation updated
- [ ] No merge conflicts with `develop`
- [ ] Descriptive PR title and description
- [ ] Breaking changes noted in description

### Code Review Guidelines

**Reviewers Should Check**:
- Code quality and readability
- Test coverage
- Performance implications
- Security considerations
- Documentation completeness

**Feedback Should Be**:
- Constructive and respectful
- Specific with examples
- Focused on code, not person

## IDE Setup

### VS Code Extensions

Recommended extensions:

- **PHP Intelephense**: PHP intellisense
- **Laravel Extension Pack**: Laravel helpers
- **Prettier**: Code formatting
- **ESLint**: JavaScript linting
- **GitLens**: Git insights
- **Docker**: Docker support

### PHPStorm Setup

1. **Install Laravel Plugin**
2. **Configure PHP Interpreter**: Settings > PHP
3. **Enable Laravel Support**: Settings > Laravel Plugin
4. **Configure Code Style**: Settings > Code Style > PHP (PSR-12)

## Performance Profiling

### Laravel Telescope

Install Telescope for local debugging:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

**Access**: http://localhost:8003/telescope

**Features**:
- Request monitoring
- Database query analysis
- Job inspection
- Exception tracking
- Log viewer
- Cache monitoring

### Blackfire.io

For deep performance profiling:

```bash
# Install Blackfire probe
wget -q -O - https://packages.blackfire.io/gpg.key | sudo apt-key add -
echo "deb http://packages.blackfire.io/debian any main" | sudo tee /etc/apt/sources.list.d/blackfire.list
sudo apt update
sudo apt install blackfire-agent blackfire-php

# Configure with your Blackfire credentials
sudo blackfire-agent --register
```

## Troubleshooting Development

### Common Issues

**Port 8003 Already in Use**:
```bash
# Kill existing process
./stop.sh

# Or manually
lsof -ti:8003 | xargs kill -9
```

**Queue Jobs Not Processing**:
```bash
# Restart queue worker
php artisan queue:restart
php artisan queue:work
```

**Cache Issues**:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Permission Errors**:
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R $USER:$USER storage bootstrap/cache
```

## Further Reading

- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [Architecture Guide](ARCHITECTURE.md)
- [Testing Guide](../tests/README.md)
