# MageSEO

> AI-powered SEO content generation platform for Magento 2 e-commerce stores with intelligent hallucination detection and approval workflows.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat&logo=php)](https://php.net)
[![Filament](https://img.shields.io/badge/Filament-4.2-FFAA00?style=flat)](https://filamentphp.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Overview

MageSEO is a production-ready SaaS application that automates SEO metadata generation for Magento 2 product catalogs using a **Writer-Auditor LLM pipeline**. The system integrates with Google Gemini AI to generate meta titles, descriptions, and keywords while automatically detecting and flagging potential hallucinations before human review.

### Key Features

- **AI-Powered Content Generation**: Writer-Auditor dual-agent pipeline for high-quality SEO content
- **Hallucination Detection**: Automated validation system flags unsupported claims and inaccuracies
- **Magento 2 Integration**: Seamless product data sync via REST API
- **Approval Workflow**: Review interface with diff preview and audit summaries
- **Bulk Processing**: Async queue-based generation for large product catalogs
- **Bundle/Configurable Support**: Enriched component data for complex product types
- **LLM Observability**: Complete logging of all API calls, requests, responses, and performance metrics
- **Admin Dashboard**: Built with Filament 4.2 for modern, intuitive management

## Tech Stack

| Component | Technology |
|-----------|-----------|
| **Backend Framework** | Laravel 12 (PHP 8.2+) |
| **Admin Panel** | Filament 4.2 |
| **Database** | MySQL 8.0 |
| **Queue System** | Laravel Queue with Redis |
| **LLM Provider** | Google Gemini API (gemini-2.5-flash) |
| **External Integration** | Magento 2 REST API |
| **Frontend Assets** | Vite + Laravel Mix |

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- MySQL 8.0+
- Redis 6.x+
- Node.js 18+ and npm

### Quick Setup

```bash
# Clone the repository
git clone https://github.com/florinel-chis/mage-seo.git
cd mageseo

# Configure environment FIRST
cp .env.example .env
# Edit .env with your database, Redis, Magento, and Google Gemini API key

# Install dependencies and setup (requires .env to be configured)
composer setup

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed default admin and LLM configurations
php artisan db:seed

# Build assets
npm install
npm run build
```

### Docker Setup

```bash
# Start all services (app, nginx, mysql, redis, worker)
docker-compose up -d

# Run migrations inside container
docker exec -it laravel-app php artisan migrate

# Seed database
docker exec -it laravel-app php artisan db:seed

# Access application at http://localhost
```

## Configuration

### Environment Variables

Configure the following in your `.env` file:

```bash
# Application
APP_NAME="MageSEO"
APP_URL=http://localhost:8003

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mageseo
DB_USERNAME=root
DB_PASSWORD=

# Queue & Cache
QUEUE_CONNECTION=redis
CACHE_STORE=database
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1

# Magento Integration
MAGENTO_BASE_URL=https://your-magento-store.com
MAGENTO_TOKEN=your_integration_token_here

# Google Gemini API Configuration
GEMINI_API_KEY=your_google_gemini_api_key
GEMINI_MODEL=gemini-2.5-flash
```

### Default Credentials

After seeding, you can log in to the admin panel:

- **URL**: `http://localhost:8003/admin`
- **Email**: `admin@example.com`
- **Password**: `password`

**Important**: Change these credentials in production!

## Usage

### Starting the Application

#### Quick Start (Recommended)

```bash
# Start server on port 8003 with automatic setup
./start.sh

# Stop server and cleanup
./stop.sh
```

#### Manual Start

```bash
# Terminal 1: Laravel server
php artisan serve --port=8003

# Terminal 2: Queue worker
php artisan queue:work --tries=3 --timeout=90

# Terminal 3: Real-time logs (optional)
php artisan pail

# Terminal 4: Vite dev server (for asset changes)
npm run dev
```

### Workflow

1. **Configure Magento Store**
   - Navigate to `Admin > Magento Stores`
   - Add your Magento store with base URL and token
   - Sync product catalog

2. **Create SEO Job**
   - Go to `Admin > SEO Jobs`
   - Select products or product IDs
   - Choose LLM configuration (prompts and model settings)
   - Submit job

3. **Review Generated Drafts**
   - Jobs process asynchronously via queue
   - Navigate to `Admin > SEO Drafts`
   - Review generated content with audit summaries
   - Approve, reject, or edit content

4. **Monitor LLM API Calls**
   - View `Admin > LLM API Logs`
   - Inspect full request/response data
   - Monitor token usage and performance metrics

## Architecture

### Writer-Auditor Pipeline

The system uses a dual-agent LLM architecture:

```
Product Data → Writer Agent → SEO Content → Auditor Agent → Validation Report
                    ↓                              ↓
              (meta_title,                  (confidence_score,
               meta_description,             potential_hallucinations)
               meta_keywords)
```

**Writer Agent**: Generates SEO metadata strictly from provided product data (name, description, attributes, components).

**Auditor Agent**: Validates generated content against source data and flags:
- Unsupported claims (features not in product data)
- Numerical mismatches
- Specification contradictions
- Ambiguous claims

**Auto-Approval Logic**: Drafts with `confidence_score > 0.9` and `is_safe = true` are automatically approved.

### Database Schema

Key models and relationships:

- **MagentoStore**: Magento store configurations
- **Product**: Product catalog with attributes (JSON)
- **LlmConfiguration**: Custom prompts and model settings
- **SeoJob**: Bulk generation job tracker
- **SeoDraft**: Generated SEO content with audit results
  - Status: `PENDING_REVIEW`, `APPROVED`, `REJECTED`, `SYNCED`
  - Fields: `original_data`, `generated_draft`, `audit_flags`, `confidence_score`
- **LlmLog**: Complete API call logging (requests, responses, tokens, timing)

### Queue System

Background jobs for async processing:

- **ProcessProduct**: Fetch product → Generate SEO → Audit → Save draft
- **FetchMagentoProductsJob**: Bulk product sync from Magento

Queue workers use Redis for job management with retry logic and timeout handling.

## Documentation

Comprehensive documentation is available in the `/docs` folder:

- [Architecture Guide](docs/ARCHITECTURE.md) - System design and component interactions
- [API Integration](docs/API_INTEGRATION.md) - Magento REST API integration details
- [LLM Pipeline](docs/LLM_PIPELINE.md) - Writer-Auditor implementation
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment instructions
- [Development Guide](docs/DEVELOPMENT.md) - Contributing and local development
- [Troubleshooting](docs/TROUBLESHOOTING.md) - Common issues and solutions

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Unit
php artisan test tests/Feature

# Run with coverage
php artisan test --coverage
```

## Code Quality

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Analyze code with PHPStan (if configured)
vendor/bin/phpstan analyse
```

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure:
- Code follows PSR-12 standards (use `./vendor/bin/pint`)
- Tests pass (`php artisan test`)
- Documentation is updated

## Security

If you discover any security vulnerabilities, please email info@magendoo.ro instead of using the issue tracker.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

- Built with [Laravel](https://laravel.com)
- Admin panel powered by [Filament](https://filamentphp.com)
- AI content generation via [Google Gemini](https://ai.google.dev)
- Inspired by production SEO automation needs

## Support

- **Issues**: [GitHub Issues](https://github.com/florinel-chis/mage-seo/issues)
- **Discussions**: [GitHub Discussions](https://github.com/florinel-chis/mage-seo/discussions)
- **Documentation**: [/docs](docs/)

---

**Made with ❤️ for e-commerce teams seeking scalable SEO automation**
