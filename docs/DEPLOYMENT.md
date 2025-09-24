# Deployment Guide - Guía de Despliegue

## Estrategia de Despliegue

JamWithJamie está diseñado para desplegarse en entornos de producción modernos con separación clara entre backend (CodeIgniter 4) y frontend (Vue.js 3). Esta guía cubre múltiples escenarios de despliegue desde desarrollo hasta producción.

---

## 🏗️ Arquitectura de Despliegue

### Configuración Recomendada

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Load Balancer │    │   Web Server    │    │   Database      │
│   (Nginx/HAProxy│───→│   (Apache/Nginx)│───→│   (MySQL 8.0+)  │
│   SSL Termination│    │   PHP-FPM 8.1+ │    │   Master/Slave  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
                       ┌─────────────────┐
                       │   File Storage  │
                       │   (Local/S3)    │
                       └─────────────────┘
```

### Componentes del Sistema

- **Frontend**: Vue.js 3 SPA (compilado y servido estáticamente)
- **Backend**: CodeIgniter 4 API (PHP-FPM)
- **Database**: MySQL 8.0+ con InnoDB
- **Web Server**: Nginx (recomendado) o Apache
- **File Storage**: Sistema de archivos local o AWS S3
- **Cache**: Redis (opcional, para sesiones/cache)

---

## 🐳 Docker Deployment

### Dockerfile - Backend

```dockerfile
# Dockerfile
FROM php:8.1-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/writable

EXPOSE 9000

CMD ["php-fpm"]
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: jamwithjamie-app
    restart: unless-stopped
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - jamwithjamie-network
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    container_name: jamwithjamie-nginx
    restart: unless-stopped
    ports:
      - "8080:80"
      - "8443:443"
    volumes:
      - ./:/var/www/html
      - ./docker/nginx/conf.d/:/etc/nginx/conf.d/
      - ./docker/nginx/ssl/:/etc/nginx/ssl/
    networks:
      - jamwithjamie-network
    depends_on:
      - app

  db:
    image: mysql:8.0
    container_name: jamwithjamie-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: jamwithjamie
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_PASSWORD: db_password
      MYSQL_USER: jamwithjamie_user
    volumes:
      - db_data:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
    networks:
      - jamwithjamie-network
    ports:
      - "3306:3306"

  redis:
    image: redis:alpine
    container_name: jamwithjamie-redis
    restart: unless-stopped
    networks:
      - jamwithjamie-network

networks:
  jamwithjamie-network:
    driver: bridge

volumes:
  db_data:
```

### Nginx Configuration

```nginx
# docker/nginx/conf.d/jamwithjamie.conf
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php index.html;

    # Handle Vue.js SPA routing
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API routes
    location /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param HTTPS off;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }

    location ~ /(writable|tests|docs)/ {
        deny all;
    }
}
```

---

## ☁️ Cloud Deployment - AWS

### EC2 + RDS Setup

#### 1. Launch EC2 Instance

```bash
# Amazon Linux 2 AMI
# Instance Type: t3.medium (production) / t3.micro (development)
# Security Groups: HTTP (80), HTTPS (443), SSH (22)

# Connect to instance
ssh -i your-key.pem ec2-user@your-instance-ip
```

#### 2. Install Dependencies

```bash
# Update system
sudo yum update -y

# Install PHP 8.1
sudo amazon-linux-extras install php8.1 -y

# Install required PHP extensions
sudo yum install -y php-fpm php-mysql php-json php-zip php-gd php-mbstring php-xml

# Install Nginx
sudo amazon-linux-extras install nginx1 -y

# Install Node.js for frontend build
curl -sL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 3. Setup Application

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/your-repo/jamwithjamie.git
cd jamwithjamie

# Install PHP dependencies
sudo composer install --no-dev --optimize-autoloader

# Build frontend
cd frontend
npm install
npm run build
cd ..

# Set permissions
sudo chown -R nginx:nginx /var/www/jamwithjamie
sudo chmod -R 755 writable/
```

#### 4. Configure Database (RDS)

```bash
# Create RDS MySQL instance
# Engine: MySQL 8.0
# Instance Class: db.t3.micro (dev) / db.t3.small (prod)
# Storage: 20GB SSD
# Multi-AZ: Enabled (production)
```

#### 5. Environment Configuration

```bash
# Copy environment file
cp env.example .env

# Edit configuration
sudo vim .env
```

```env
# .env (Production)
CI_ENVIRONMENT = production

# Database
database.default.hostname = your-rds-endpoint.amazonaws.com
database.default.database = jamwithjamie
database.default.username = admin
database.default.password = your-secure-password
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

# App
app.baseURL = https://yourdomain.com
app.indexPage = ''

# JWT
jwt.secret = your-jwt-secret-key
jwt.expire = 3600

# Email (SES)
email.fromEmail = noreply@yourdomain.com
email.fromName = JamWithJamie
```

#### 6. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo yum install certbot python3-certbot-nginx -y

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal cron
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -
```

---

## 🔧 Server Configuration

### Nginx Production Config

```nginx
# /etc/nginx/sites-available/jamwithjamie
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/jamwithjamie/public;
    index index.php index.html;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/javascript
        application/xml+rss
        application/json;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;

    # Frontend SPA
    location / {
        try_files $uri $uri/ /index.html;

        # Cache static assets
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
    }

    # API Routes
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Login rate limiting
    location /api/auth/login {
        limit_req zone=login burst=3 nodelay;
        try_files $uri /index.php?$query_string;
    }

    # PHP Processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param HTTPS on;
    }

    # Security - Deny access
    location ~ /\. {
        deny all;
    }

    location ~ /(writable|tests|docs|app|system)/ {
        deny all;
    }

    # File upload size
    client_max_body_size 10M;
}
```

### PHP-FPM Configuration

```ini
; /etc/php/8.1/fpm/pool.d/jamwithjamie.conf
[jamwithjamie]
user = nginx
group = nginx

listen = /run/php/php8.1-fpm.sock
listen.owner = nginx
listen.group = nginx

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500

; PHP configuration
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 10M
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
```

---

## 📊 Database Migration

### Production Migration

```bash
# Run migrations
php spark migrate

# Seed essential data
php spark db:seed ProductionSeeder

# Backup before migration
mysqldump -u username -p jamwithjamie > backup_pre_migration.sql
```

### Migration Script

```php
<?php
// database/seeds/ProductionSeeder.php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $this->call('AdminUserSeeder');

        // Setup basic geographic data
        $this->call('GeographicSeeder');

        // Create default services
        $this->call('ServiceSeeder');

        // Setup roles and permissions
        $this->call('RolePermissionSeeder');
    }
}
```

---

## 🚀 Frontend Build Pipeline

### Build Process

```bash
# Development build
cd frontend
npm run dev

# Production build
npm run build

# Build with analysis
npm run build -- --analyze
```

### Build Configuration

```javascript
// frontend/vite.config.js (Production)
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  build: {
    outDir: '../public/build',
    emptyOutDir: true,
    manifest: true,
    minify: 'terser',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'vue-router', 'axios'],
          charts: ['chart.js', 'vue-chartjs']
        }
      }
    }
  },
  define: {
    'process.env.NODE_ENV': '"production"'
  }
})
```

### Environment Variables

```bash
# frontend/.env.production
VITE_API_URL=https://yourdomain.com/api
VITE_APP_NAME=JamWithJamie
VITE_DEBUG=false
VITE_UPLOAD_URL=https://yourdomain.com/api/upload
```

---

## 🔄 CI/CD Pipeline

### GitHub Actions Workflow

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql

      - name: Install dependencies
        run: composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader

      - name: Run tests
        run: php spark test

  build-frontend:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json

      - name: Install dependencies
        run: cd frontend && npm ci

      - name: Build frontend
        run: cd frontend && npm run build

      - name: Upload build artifacts
        uses: actions/upload-artifact@v3
        with:
          name: frontend-build
          path: public/build/

  deploy:
    needs: [test, build-frontend]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - uses: actions/checkout@v3

      - name: Download build artifacts
        uses: actions/download-artifact@v3
        with:
          name: frontend-build
          path: public/build/

      - name: Deploy to server
        uses: appleboy/ssh-action@v0.1.5
        with:
          host: ${{ secrets.HOST }}
          username: ${{ secrets.USERNAME }}
          key: ${{ secrets.PRIVATE_KEY }}
          script: |
            cd /var/www/jamwithjamie
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php spark migrate
            sudo systemctl reload php8.1-fpm
            sudo systemctl reload nginx
```

---

## 📈 Monitoring y Performance

### Application Monitoring

```php
// app/Config/Events.php
Events::on('pre_system', function () {
    if (ENVIRONMENT === 'production') {
        // Application Performance Monitoring
        $startTime = microtime(true);

        register_shutdown_function(function () use ($startTime) {
            $executionTime = microtime(true) - $startTime;
            $memoryUsage = memory_get_peak_usage(true);

            log_message('info', "Performance: {$executionTime}s, Memory: " .
                       number_format($memoryUsage / 1024 / 1024, 2) . "MB");
        });
    }
});
```

### Log Configuration

```php
// app/Config/Logger.php
public array $handlers = [
    'production' => [
        'class'     => 'CodeIgniter\Log\Handlers\FileHandler',
        'level'     => 'error',
        'filename'  => 'errors.log',
    ],
    'performance' => [
        'class'     => 'CodeIgniter\Log\Handlers\FileHandler',
        'level'     => 'info',
        'filename'  => 'performance.log',
    ]
];
```

### Health Check Endpoint

```php
// app/Controllers/HealthController.php
<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class HealthController extends ResourceController
{
    public function index(): ResponseInterface
    {
        $health = [
            'status' => 'ok',
            'timestamp' => date('c'),
            'version' => '2.0.0',
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
            ]
        ];

        $status = array_reduce($health['checks'], function($carry, $check) {
            return $carry && $check['status'] === 'ok';
        }, true) ? 200 : 503;

        return $this->response
            ->setStatusCode($status)
            ->setJSON($health);
    }

    private function checkDatabase(): array
    {
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            return ['status' => 'ok', 'message' => 'Database connected'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
```

---

## 🛡️ Security Hardening

### Server Security

```bash
# Firewall configuration
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Fail2ban for SSH protection
sudo yum install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Disable root SSH
sudo sed -i 's/PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo systemctl restart sshd
```

### Application Security

```php
// app/Config/Security.php
public string $csrfProtection  = 'cookie';
public int $csrfExpire         = 7200;
public bool $csrfRegenerate    = true;
public bool $csrfRedirect      = true;
public string $csrfSameSite    = 'Strict';

// Headers
public array $headers = [
    'X-Frame-Options'           => 'SAMEORIGIN',
    'X-Content-Type-Options'    => 'nosniff',
    'X-Permitted-Cross-Domain-Policies' => 'none',
    'Referrer-Policy'           => 'strict-origin-when-cross-origin',
];
```

---

## 📋 Deployment Checklist

### Pre-Deployment

- [ ] Run all tests (unit, integration, e2e)
- [ ] Update version numbers
- [ ] Build frontend assets
- [ ] Review environment variables
- [ ] Backup current database
- [ ] Check disk space and resources

### Deployment

- [ ] Deploy code to staging first
- [ ] Run database migrations
- [ ] Clear application caches
- [ ] Update file permissions
- [ ] Restart services (PHP-FPM, Nginx)
- [ ] Verify SSL certificates

### Post-Deployment

- [ ] Run smoke tests
- [ ] Check application logs
- [ ] Monitor performance metrics
- [ ] Verify all features working
- [ ] Update documentation
- [ ] Notify team of deployment

### Rollback Plan

```bash
# Quick rollback script
#!/bin/bash
# rollback.sh

echo "Rolling back to previous version..."

# Restore code
git reset --hard HEAD~1

# Restore database
mysql jamwithjamie < backup_pre_deployment.sql

# Restart services
sudo systemctl restart php8.1-fpm
sudo systemctl restart nginx

echo "Rollback completed"
```

---

*Documentación actualizada: Septiembre 2025*