# Deployment Guide

This guide covers deploying the Laravel SEO Platform to production environments.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Server Requirements](#server-requirements)
- [Deployment Options](#deployment-options)
- [Production Configuration](#production-configuration)
- [SSL/HTTPS Setup](#sslhttps-setup)
- [Queue Workers](#queue-workers)
- [Monitoring](#monitoring)
- [Backup Strategy](#backup-strategy)
- [Security Checklist](#security-checklist)

## Prerequisites

Before deploying to production, ensure you have:

- Domain name with DNS configured
- SSL certificate (Let's Encrypt recommended)
- Production database (MySQL 8.0+)
- Redis server for queues/cache
- Server access (SSH, FTP, or hosting control panel)
- Environment variables configured
- Backup strategy in place

## Server Requirements

### Minimum Specifications

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| **CPU** | 2 cores | 4+ cores |
| **RAM** | 2 GB | 4+ GB |
| **Storage** | 20 GB SSD | 50+ GB SSD |
| **PHP** | 8.2 | 8.4+ |
| **MySQL** | 8.0 | 8.0+ |
| **Redis** | 6.x | 7.x+ |

### PHP Extensions Required

```bash
php -m | grep -E 'pdo|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo|redis'
```

Required extensions:
- `pdo_mysql`
- `mbstring`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`
- `redis`
- `gd` or `imagick` (for image processing)
- `zip`
- `curl`

### Server Software

**Web Server**: Apache 2.4+ or Nginx 1.18+
**Process Manager**: Supervisor (for queue workers)
**SSL**: Certbot (for Let's Encrypt)

## Deployment Options

### Option 1: VPS/Dedicated Server (DigitalOcean, Linode, AWS EC2)

#### Step 1: Server Setup

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required software
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.4 and extensions
sudo apt install -y php8.4 php8.4-cli php8.4-fpm php8.4-mysql php8.4-redis \
    php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-curl php8.4-gd php8.4-zip

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Install Nginx
sudo apt install -y nginx
sudo systemctl enable nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 18+
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Supervisor
sudo apt install -y supervisor
sudo systemctl enable supervisor
```

#### Step 2: Deploy Application

```bash
# Create deployment directory
sudo mkdir -p /var/www/seo-platform
sudo chown $USER:$USER /var/www/seo-platform
cd /var/www/seo-platform

# Clone repository (or upload via FTP/SFTP)
git clone https://github.com/yourusername/laravel-seo-platform.git .

# Install dependencies
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/seo-platform
sudo chmod -R 755 /var/www/seo-platform
sudo chmod -R 775 /var/www/seo-platform/storage
sudo chmod -R 775 /var/www/seo-platform/bootstrap/cache
```

#### Step 3: Configure Environment

```bash
# Copy and edit .env
cp .env.example .env
nano .env

# Set production values
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_HOST=127.0.0.1
DB_DATABASE=seo_platform_prod
DB_USERNAME=seo_user
DB_PASSWORD=secure_password_here

# Redis
REDIS_HOST=127.0.0.1

# Magento & OpenAI
MAGENTO_BASE_URL=https://your-magento.com
MAGENTO_TOKEN=your_production_token
OPENAI_API_KEY=your_gemini_api_key

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 4: Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/seo-platform
```

**Nginx Configuration**:
```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/seo-platform/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/seo-platform /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Step 5: Configure Queue Workers

```bash
sudo nano /etc/supervisor/conf.d/seo-platform-worker.conf
```

**Supervisor Configuration**:
```ini
[program:seo-platform-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/seo-platform/artisan queue:work redis --tries=3 --timeout=90 --max-jobs=1000 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/seo-platform/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start seo-platform-worker:*
```

#### Step 6: Setup Cron for Scheduled Tasks

```bash
sudo crontab -e -u www-data
```

Add:
```
* * * * * cd /var/www/seo-platform && php artisan schedule:run >> /dev/null 2>&1
```

### Option 2: Laravel Forge

1. Create server on [Laravel Forge](https://forge.laravel.com)
2. Connect GitHub repository
3. Configure deployment script:
   ```bash
   cd /home/forge/yourdomain.com
   git pull origin main
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan queue:restart
   ```
4. Setup queue worker via Forge dashboard
5. Enable SSL (automatic via Forge)
6. Configure environment variables via Forge

### Option 3: Ploi.io

Similar to Forge, with automated deployments and server management.

### Option 4: Docker Production

```bash
# Build production image
docker build -t seo-platform:production .

# Run with docker-compose
docker-compose -f docker-compose.prod.yml up -d
```

## Production Configuration

### Environment Variables

**Critical Settings**:
```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:generated_key_here

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=warning

# Session/Cache
SESSION_DRIVER=redis
CACHE_STORE=redis

# Queue
QUEUE_CONNECTION=redis

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Database Optimization

```sql
-- MySQL Configuration Tuning (my.cnf or my.ini)
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M
```

### Redis Configuration

```bash
# redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
```

### PHP-FPM Tuning

```ini
; /etc/php/8.4/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500
```

## SSL/HTTPS Setup

### Using Let's Encrypt (Free)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### Force HTTPS

In `.env`:
```bash
APP_URL=https://yourdomain.com
```

In `app/Providers/AppServiceProvider.php`:
```php
public function boot(): void
{
    if ($this->app->environment('production')) {
        \URL::forceScheme('https');
    }
}
```

## Queue Workers

### Monitoring Workers

```bash
# Check worker status
sudo supervisorctl status seo-platform-worker:*

# Restart workers
sudo supervisorctl restart seo-platform-worker:*

# View worker logs
tail -f /var/www/seo-platform/storage/logs/worker.log
```

### Scaling Workers

Edit supervisor config to increase `numprocs`:
```ini
numprocs=8  # Increase from 4 to 8
```

Reload:
```bash
sudo supervisorctl reread
sudo supervisorctl update
```

## Monitoring

### Application Monitoring

**Laravel Telescope** (optional, for staging):
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

**Access**: `https://yourdomain.com/telescope`

### Server Monitoring

**Recommended Tools**:
- **UptimeRobot**: Uptime monitoring
- **New Relic**: APM
- **Sentry**: Error tracking
- **Datadog**: Infrastructure monitoring

### Log Monitoring

**Papertrail Integration**:
```bash
# Install remote_syslog2
cd /tmp
wget https://github.com/papertrail/remote_syslog2/releases/download/v0.20/remote_syslog_linux_amd64.tar.gz
tar xzf remote_syslog_linux_amd64.tar.gz
sudo mv remote_syslog/remote_syslog /usr/local/bin/

# Configure
sudo nano /etc/log_files.yml
```

```yaml
files:
  - /var/www/seo-platform/storage/logs/laravel.log
destination:
  host: logs.papertrailapp.com
  port: YOUR_PORT
  protocol: tls
```

## Backup Strategy

### Database Backups

**Automated Daily Backups**:
```bash
# Create backup script
sudo nano /usr/local/bin/backup-database.sh
```

```bash
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/mysql"
DB_NAME="seo_platform_prod"
DB_USER="seo_user"
DB_PASS="secure_password"

mkdir -p $BACKUP_DIR

mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/backup_$TIMESTAMP.sql.gz

# Delete backups older than 7 days
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +7 -delete
```

```bash
# Make executable
sudo chmod +x /usr/local/bin/backup-database.sh

# Add to crontab
sudo crontab -e
```

```
0 2 * * * /usr/local/bin/backup-database.sh
```

### File Backups

Backup `/var/www/seo-platform/storage` regularly:
```bash
rsync -avz /var/www/seo-platform/storage/ /var/backups/storage/
```

### Remote Backups

Use AWS S3, DigitalOcean Spaces, or similar:
```bash
# Install AWS CLI
sudo apt install awscli

# Configure
aws configure

# Backup to S3
aws s3 sync /var/backups/ s3://your-bucket/backups/
```

## Security Checklist

### Before Going Live

- [ ] `APP_DEBUG=false` in `.env`
- [ ] Strong `APP_KEY` generated
- [ ] Database credentials secure and unique
- [ ] Magento and Gemini API keys secured
- [ ] `.env` file not accessible via web (outside public root)
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] `storage/` and `bootstrap/cache/` writable by web server
- [ ] SSL certificate installed and auto-renewal configured
- [ ] Firewall configured (only ports 22, 80, 443 open)
- [ ] SSH key authentication enabled (password auth disabled)
- [ ] Server updates automated (`unattended-upgrades`)
- [ ] Database backups automated
- [ ] Monitoring and alerting configured
- [ ] Error tracking (Sentry) enabled
- [ ] Rate limiting configured for API routes
- [ ] CSRF protection enabled (Laravel default)
- [ ] XSS protection enabled (Laravel default)
- [ ] Security headers configured (see below)

### Security Headers

Add to Nginx config:
```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';";
```

### Firewall (UFW)

```bash
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## Performance Optimization

### Opcache

Enable PHP Opcache in `/etc/php/8.4/fpm/php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### Redis Cache

Cache frequently accessed data:
```php
// Cache LLM configurations
$config = Cache::remember('llm_config_1', 3600, fn() => LlmConfiguration::find(1));
```

### Database Indexing

```sql
-- Add indexes for common queries
CREATE INDEX idx_seo_drafts_status ON seo_drafts(status);
CREATE INDEX idx_llm_logs_created_at ON llm_logs(created_at);
CREATE INDEX idx_products_sku ON products(sku);
```

### CDN

Use Cloudflare or AWS CloudFront for static assets.

## Troubleshooting Deployment

### 500 Internal Server Error

- Check logs: `tail -f storage/logs/laravel.log`
- Check Nginx error log: `tail -f /var/log/nginx/error.log`
- Ensure storage permissions: `sudo chmod -R 775 storage`
- Clear cache: `php artisan config:clear && php artisan cache:clear`

### Queue Workers Not Running

- Check supervisor status: `sudo supervisorctl status`
- Restart workers: `sudo supervisorctl restart seo-platform-worker:*`
- Check worker logs: `tail -f storage/logs/worker.log`

### Database Connection Failed

- Verify credentials in `.env`
- Test connection: `php artisan tinker` → `DB::connection()->getPdo();`
- Ensure MySQL is running: `sudo systemctl status mysql`

## Rollback Strategy

In case of issues:

```bash
# Rollback to previous commit
git reset --hard HEAD~1

# Reinstall dependencies
composer install --optimize-autoloader --no-dev

# Rollback database migrations
php artisan migrate:rollback

# Clear and recache
php artisan config:clear
php artisan config:cache

# Restart workers
sudo supervisorctl restart seo-platform-worker:*
```

## Further Reading

- [Laravel Deployment Documentation](https://laravel.com/docs/deployment)
- [Laravel Forge](https://forge.laravel.com)
- [DigitalOcean Laravel Deployment Guide](https://www.digitalocean.com/community/tutorials/how-to-deploy-a-laravel-application-with-nginx-on-ubuntu)
- [Security Best Practices](https://laravel.com/docs/security)
