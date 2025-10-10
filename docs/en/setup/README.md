# Setup Guide - Pharmedice Customer Area Backend

> Complete installation and configuration guide for the Pharmedice Customer Area backend system.

## 🎯 Prerequisites

Before you begin, ensure you have the following installed on your system:

### Required Software
- **PHP 8.2+** with extensions:
  - BCMath PHP Extension
  - Ctype PHP Extension
  - cURL PHP Extension
  - DOM PHP Extension
  - Fileinfo PHP Extension
  - JSON PHP Extension
  - Mbstring PHP Extension
  - OpenSSL PHP Extension
  - PCRE PHP Extension
  - PDO PHP Extension
  - Tokenizer PHP Extension
  - XML PHP Extension
- **Composer** (PHP package manager)
- **PostgreSQL 12+** (database)
- **Git** (version control)

### Optional Software
- **Redis** (for caching and queues)
- **Docker & Docker Compose** (for containerized development)
- **AWS CLI** (for S3 configuration)

### Cloud Services
- **AWS S3 Bucket** (for file storage)
- **Email Service** (SMTP, AWS SES, or similar)

## ⚡ Quick Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/pharmedice-customer-area-backend.git
cd pharmedice-customer-area-backend
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Generate JWT secret key
php artisan jwt:secret
```

### 4. Configure Database
Edit the `.env` file with your database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pharmedice_customer_area
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 5. Run Database Migrations
```bash
# Create database tables
php artisan migrate

# Seed with test data (optional)
php artisan db:seed
```

### 6. Start Development Server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 🔧 Detailed Configuration

### Environment Variables

Create and configure your `.env` file with the following settings:

#### Application Settings
```env
APP_NAME="Pharmedice Customer Area"
APP_ENV=local
APP_KEY=base64:your_generated_key_here
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000
```

#### Database Configuration
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pharmedice_customer_area
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

#### JWT Authentication
```env
JWT_SECRET=your_jwt_secret_here
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

#### Email Configuration

**For Development (Log emails to file):**
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@pharmedice.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**For Production (SMTP):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@pharmedice.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**For Production (AWS SES):**
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
MAIL_FROM_ADDRESS="noreply@pharmedice.com"
MAIL_FROM_NAME="${APP_NAME}"
```

#### AWS S3 Configuration
```env
AWS_ACCESS_KEY_ID=your_access_key_id
AWS_SECRET_ACCESS_KEY=your_secret_access_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=pharmedice-customer-documents
AWS_USE_PATH_STYLE_ENDPOINT=false
```

#### Cache Configuration (Optional)
```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Queue Configuration (Optional)
```env
QUEUE_CONNECTION=redis
```

### AWS S3 Setup

#### 1. Create S3 Bucket
1. Log in to AWS Console
2. Navigate to S3 service
3. Create a new bucket (e.g., `pharmedice-customer-documents`)
4. Configure bucket permissions for your application

#### 2. Create IAM User
1. Navigate to IAM service
2. Create new user with programmatic access
3. Attach policy with S3 permissions:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:GetObject",
                "s3:PutObject",
                "s3:DeleteObject"
            ],
            "Resource": "arn:aws:s3:::pharmedice-customer-documents/*"
        },
        {
            "Effect": "Allow",
            "Action": [
                "s3:ListBucket"
            ],
            "Resource": "arn:aws:s3:::pharmedice-customer-documents"
        }
    ]
}
```

#### 3. Configure CORS (if needed for direct uploads)
```json
[
    {
        "AllowedHeaders": ["*"],
        "AllowedMethods": ["GET", "PUT", "POST"],
        "AllowedOrigins": ["https://yourdomain.com"],
        "ExposeHeaders": ["ETag"]
    }
]
```

## 🐳 Docker Setup (Optional)

### Using Docker Compose

Create a `docker-compose.yml` file:

```yaml
version: '3.8'
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=db
      - DB_DATABASE=pharmedice
      - DB_USERNAME=laravel
      - DB_PASSWORD=secret
    volumes:
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: postgres:15
    environment:
      POSTGRES_DB: pharmedice
      POSTGRES_USER: laravel
      POSTGRES_PASSWORD: secret
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  postgres_data:
```

### Run with Docker
```bash
# Build and start containers
docker-compose up -d

# Install dependencies inside container
docker-compose exec app composer install

# Run migrations
docker-compose exec app php artisan migrate

# Generate keys
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan jwt:secret
```

## 🧪 Verify Installation

### 1. Test Database Connection
```bash
php artisan migrate:status
```

### 2. Test API Endpoints
```bash
# Test health endpoint
curl http://localhost:8000/api/health

# Test login endpoint
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@pharmedice.com","senha":"admin123"}'
```

### 3. Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --filter="SignupTest|EmailVerificationTest"
```

### 4. Test File Upload (if S3 configured)
```bash
# Create a test document (requires admin authentication)
curl -X POST http://localhost:8000/api/laudos \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "usuario_id=USER_ID" \
  -F "titulo=Test Document" \
  -F "descricao=Test upload" \
  -F "arquivo=@/path/to/test.pdf"
```

## 📊 Database Setup

### Create Database
```sql
-- Connect to PostgreSQL as superuser
CREATE DATABASE pharmedice_customer_area;
CREATE USER pharmedice_user WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE pharmedice_customer_area TO pharmedice_user;
```

### Run Migrations
```bash
# Run all migrations
php artisan migrate

# Run specific migration
php artisan migrate --path=/database/migrations/2025_10_08_173217_usuarios.php

# Rollback migrations (if needed)
php artisan migrate:rollback
```

### Seed Test Data
```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder
```

### Default Test Users
After running seeders, you'll have these test users:

- **Admin User**: `admin@pharmedice.com` / `admin123`
- **Regular User**: `joao@exemplo.com` / `123456`

## 🔐 Security Configuration

### File Permissions (Linux/Mac)
```bash
# Set appropriate permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Web Server Configuration

#### Apache (.htaccess)
The project includes a `.htaccess` file in the `public` directory.

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/pharmedice/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

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

## 🚀 Production Deployment

### Environment Configuration
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use production database
DB_HOST=your-production-db-host
DB_DATABASE=pharmedice_production

# Use production email service
MAIL_MAILER=ses

# Use production S3 bucket
AWS_BUCKET=pharmedice-production-documents
```

### Optimization Commands
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### Security Checklist
- [ ] Set `APP_DEBUG=false` in production
- [ ] Use HTTPS for all communications
- [ ] Configure proper CORS settings
- [ ] Set up rate limiting
- [ ] Configure secure headers
- [ ] Use environment-specific S3 buckets
- [ ] Set up monitoring and logging
- [ ] Configure backup strategies
- [ ] Use strong database passwords
- [ ] Secure file permissions

## 🆘 Troubleshooting

### Common Issues

#### "Class not found" errors
```bash
composer dump-autoload
```

#### JWT secret not set
```bash
php artisan jwt:secret
```

#### Database connection refused
- Check PostgreSQL is running
- Verify database credentials in `.env`
- Ensure database exists

#### File upload fails
- Check AWS S3 credentials
- Verify bucket exists and permissions are correct
- Check file size limits in PHP configuration

#### Email verification not working
- Check email configuration in `.env`
- Verify email service credentials
- Check application logs: `tail -f storage/logs/laravel.log`

### Performance Issues
- Enable Redis for caching and queues
- Optimize database queries
- Use CDN for static assets
- Configure proper web server caching

### Getting Help
1. Check the logs: `storage/logs/laravel.log`
2. Run tests to identify issues: `php artisan test`
3. Check environment configuration: `php artisan config:show`
4. Verify database connectivity: `php artisan migrate:status`

---

For more specific configuration details, check the individual configuration files in the `config/` directory.