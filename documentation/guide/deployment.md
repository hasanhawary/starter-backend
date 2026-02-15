---
title: Deployment
description: Production deployment guide for multi-tenant application
---

# Deployment

This guide covers deploying the multi-tenant backend to production environments.

## Pre-Deployment Checklist

- [ ] All tests passing
- [ ] Environment variables configured (central + tenant)
- [ ] Database migrations ready (central + tenant)
- [ ] Assets compiled
- [ ] Error logging configured
- [ ] Backups configured
- [ ] SSL certificate installed
- [ ] Domain configured
- [ ] Email service configured
- [ ] Storage configured
- [ ] Tenant database strategy decided
- [ ] Queue worker configured
- [ ] Reverb configured

---

## Environment Configuration

### Production .env (Central)

```env
APP_NAME="Multi-Tenant App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Central Database
DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=central_db
DB_USERNAME=db_user
DB_PASSWORD=secure_password

# Tenant Database Strategy
TENANCY_DATABASE_MANAGER=sqlite
# or mysql for shared database

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SANCTUM_EXPIRATION=525600

APP_KEY=base64:your_generated_key
```

---

## Server Requirements

### Minimum Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher (for central database)
- Redis (recommended)
- Composer
- Node.js 14+ (for asset compilation)

### Recommended Specifications

- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+
- 4GB RAM minimum (for multi-tenant)
- 50GB storage minimum
- SSD storage recommended

---

## Deployment Steps

### 1. Prepare Server

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-redis \
  php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml \
  mysql-server redis-server nginx composer nodejs npm

# Start services
sudo systemctl start php8.2-fpm
sudo systemctl start mysql
sudo systemctl start redis-server
sudo systemctl start nginx
```

---

### 2. Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/yourusername/starter-backend-tenant.git
cd starter-backend-tenant
sudo chown -R www-data:www-data .
```

---

### 3. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

---

### 4. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with production values.

---

### 5. Setup Central Database

```bash
# Create central database
mysql -u root -p
CREATE DATABASE central_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run central migrations
php artisan migrate --database=central

# Seed central data
php artisan db:seed --class=CentralSeeder
```

---

### 6. Setup Tenant Database Strategy

#### Option A: SQLite (Per-Tenant Databases)

```bash
# Create tenant databases directory
mkdir -p storage/tenants
chmod 755 storage/tenants

# Migrations will create databases automatically
```

---

#### Option B: Shared MySQL Database

```env
TENANCY_DATABASE_MANAGER=mysql
```

---

### 7. Setup Storage

```bash
php artisan storage:link
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

---

### 8. Configure Web Server

#### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com *.yourdomain.com;
    root /var/www/starter-backend-tenant/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

### 9. Setup SSL Certificate

```bash
# Using Let's Encrypt with Certbot
sudo apt install certbot python3-certbot-nginx
sudo certbot certonly --nginx -d yourdomain.com -d '*.yourdomain.com'
```

Update Nginx configuration to use SSL:

```nginx
server {
    listen 443 ssl http2;
    server_name yourdomain.com *.yourdomain.com;
    
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    # ... rest of configuration
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name yourdomain.com *.yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

### 10. Setup Queue Worker

Create systemd service:

```ini
# /etc/systemd/system/laravel-queue.service
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/starter-backend-tenant
ExecStart=/usr/bin/php /var/www/starter-backend-tenant/artisan queue:work --sleep=3 --tries=3
Restart=unless-stopped
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
```

---

### 11. Setup Reverb (Real-Time)

Create systemd service:

```ini
# /etc/systemd/system/laravel-reverb.service
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/starter-backend-tenant
ExecStart=/usr/bin/php /var/www/starter-backend-tenant/artisan reverb:start
Restart=unless-stopped
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-reverb
sudo systemctl start laravel-reverb
```

---

## Optimization

### Cache Configuration

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

---

### Database Optimization

```bash
# Add indexes
php artisan migrate

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

### Asset Optimization

```bash
# Minify assets
npm run build

# Gzip compression
gzip -9 public/js/*.js
gzip -9 public/css/*.css
```

---

## Monitoring & Maintenance

### Setup Monitoring

```bash
# Install monitoring tools
sudo apt install htop iotop nethogs

# Monitor system
htop
```

---

### Backup Strategy

```bash
# Daily central database backup
0 2 * * * mysqldump -u root -p central_db > /backups/central-$(date +\%Y\%m\%d).sql

# Weekly tenant databases backup
0 3 * * 0 tar -czf /backups/tenants-$(date +\%Y\%m\%d).tar.gz /var/www/starter-backend-tenant/storage/tenants

# Weekly full backup
0 4 * * 0 tar -czf /backups/app-$(date +\%Y\%m\%d).tar.gz /var/www/starter-backend-tenant
```

---

### Log Rotation

```bash
# /etc/logrotate.d/laravel
/var/www/starter-backend-tenant/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

---

## Updating Production

### Safe Update Process

```bash
# 1. Backup databases
mysqldump -u root -p central_db > backup-central.sql
tar -czf backup-tenants.tar.gz /var/www/starter-backend-tenant/storage/tenants

# 2. Pull latest code
git pull origin main

# 3. Install dependencies
composer install --no-dev --optimize-autoloader

# 4. Run migrations (central)
php artisan migrate --database=central --force

# 5. Run migrations (tenants)
php artisan migrate:tenants --force

# 6. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:cache

# 7. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart laravel-queue
sudo systemctl restart laravel-reverb
```

---

## Security Hardening

### File Permissions

```bash
# Set correct permissions
sudo chown -R www-data:www-data /var/www/starter-backend-tenant
sudo chmod -R 755 /var/www/starter-backend-tenant
sudo chmod -R 775 storage/ bootstrap/cache/
```

---

### Firewall Configuration

```bash
# Allow only necessary ports
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

### Security Headers

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
```

---

## Troubleshooting Deployment

### Issue: 500 Internal Server Error

```bash
# Check error logs
tail -f storage/logs/laravel.log

# Check permissions
ls -la storage/
ls -la bootstrap/cache/

# Check database connection
php artisan tinker
>>> DB::connection('central')->getPdo()
```

---

### Issue: Queue Not Processing

```bash
# Check queue status
php artisan queue:failed

# Restart queue worker
sudo systemctl restart laravel-queue

# Check logs
tail -f storage/logs/laravel.log
```

---

### Issue: Tenant Database Not Found

```bash
# Check tenant database exists
ls -la storage/tenants/

# Verify tenant in central database
php artisan tinker
>>> App\Models\Central\Tenant::all()
```

---

## Performance Monitoring

### Key Metrics to Monitor

- Response time (target: < 200ms)
- Error rate (target: < 0.1%)
- CPU usage (target: < 70%)
- Memory usage (target: < 80%)
- Disk usage (target: < 80%)
- Database connections (target: < 100)
- Queue jobs processed per minute

---

### Monitoring Tools

- New Relic
- Datadog
- Scout APM
- Sentry (error tracking)
- Grafana (metrics visualization)

---

## See Also

- [Installation](/guide/installation) — Local setup
- [Configuration](/guide/configuration/) — Configuration options
- [Multi-Tenancy](/guide/multitenancy/) — Multi-tenant architecture
- [Troubleshooting](/guide/troubleshooting) — Common issues
