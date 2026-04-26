# 🏥 eFiche Billing API

> Production-ready billing system for Rwanda's healthcare landscape, from urban hospitals to rural health posts with intermittent connectivity.

## 🚀 Quick Start

### Local Setup

```bash
# Clone the repository
git clone <repository-url>
cd efiche-billing-api

# Install dependencies
composer install

# Copy environment configuration
cp .env.example .env

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=efiche_billing
DB_USERNAME=root
DB_PASSWORD=

# Generate application key
php artisan key:generate

# Run migrations and seed data
php artisan migrate:fresh --seed

# Start the development server
php artisan serve
```

### Database Setup

**MySQL (Recommended)**
```bash
# Create database manually
mysql -u root -p
CREATE DATABASE efiche_billing;
EXIT

# Or use phpMyAdmin/your preferred tool
```

**PostgreSQL Alternative**
```bash
# Create database
psql -U postgres
CREATE DATABASE efiche_billing;
\c efiche_billing
GRANT ALL PRIVILEGES ON DATABASE efiche_billing TO your_user;
```

### Prerequisites

- **PHP**: 8.1+ with required extensions:
  - `mbstring`
  - `pdo_mysql` or `pdo_pgsql`
  - `bcmath`
  - `json`
  - `tokenizer`
  - `xml`

- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Composer**: Latest version
- **Web Server**: Apache or Nginx (optional, for production)

## 📋 Overview

The eFiche Billing API is a **production-ready Laravel backend** designed specifically for Efiche's healthcare system, handling:

- **🏥 Multi-facility operations** across hospitals and rural health posts
- **💰 Visit-based billing** with line items and insurance coverage
- **🔒 Race condition prevention** with PostgreSQL-level locking
- **📱 Mobile money integration** with webhook idempotency
- **🌐 Offline support** for intermittent connectivity
- **👥 Role-based access** (admin, cashier, staff)

## 🔐 Authentication

### Test Users

```
Admin:    admin@efiche.rw     / password123
Cashier:  cashier@efiche.rw   / password123  
Staff:    staff@efiche.rw      / password123
```

### API Authentication

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@efiche.rw","password":"password123"}'

# Use token for protected requests
curl -X GET http://localhost:8000/api/patients \
  -H "Authorization: Bearer {token}"
```

## 🧪 Testing

### Run All Tests

```bash
# PHPUnit tests
php artisan test

# Feature tests specifically
php artisan test --filter=Feature

# Authentication tests
php artisan test tests/Feature/AuthenticationTest.php

# Run tests with coverage
php artisan test --coverage-html
```

### Manual API Testing

```bash
# Comprehensive CRUD test script
php test_api_endpoints.php

# Test individual endpoints
php artisan serve
# Then in another terminal:
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@efiche.rw","password":"password123"}'
```

### Database Testing

```bash
# Fresh database for testing
php artisan migrate:fresh --seed

# Test with specific data
php artisan tinker
>>> $user = App\Models\User::where('email', 'admin@efiche.rw')->first();
>>> $user->name;
```

## 📱 Webhook Testing

### Manual Webhook Trigger

```bash
# Simulate mobile money payment confirmation
curl -X POST http://localhost:8000/api/webhooks/efichepay \
  -H "Content-Type: application/json" \
  -H "X-EfichePay-Signature: test-signature" \
  -d '{
    "eventId": "evt_test_123456",
    "status": "PAYMENT_COMPLETE",
    "orderNumber": "PAY-12345",
    "amount": 10000
  }'
```

### Webhook Testing Script

```bash
# Test webhook idempotency
for i in {1..3}; do
  curl -X POST http://localhost:8000/api/webhooks/efichepay \
    -H "Content-Type: application/json" \
    -d '{"eventId":"evt_test_123456","status":"PAYMENT_COMPLETE"}'
  echo "Request $i completed"
done
# Should only process once due to idempotency
```

## 📊 Key Features

### ✅ Race Condition Prevention

```php
// PostgreSQL row-level locking prevents double payments
$invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);
```

### ✅ Webhook Idempotency

```php
// Atomic webhook processing with unique constraints
try {
    $webhookEvent = WebhookEvent::create(['event_id' => $payload['eventId']]);
} catch (QueryException $e) {
    // Handle duplicate - webhook already processed
}
```

### ✅ Per-Facility Insurance

```bash
# Get facility-specific insurance options
curl -X GET http://localhost:8000/api/facilities/1/insurances \
  -H "Authorization: Bearer {token}"
```

## 🛠️ API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout  
- `GET /api/auth/me` - Current user info

### Patient Management
- `GET /api/patients` - List patients with search
- `POST /api/patients` - Create patient
- `GET /api/patients/{id}` - Patient details
- `PUT /api/patients/{id}` - Update patient
- `GET /api/patients/{id}/visits` - Patient visit history

### Billing Operations
- `POST /api/visits/{visitId}/invoices` - Create invoice with line items
- `POST /api/invoices/{invoiceId}/payments` - Process payment
- `GET /api/invoices/{id}` - Invoice details
- `GET /api/payments/{id}/status` - Payment status

### Dashboard
- `GET /api/dashboard/stats` - Statistics overview
- `GET /api/dashboard/payment-stats` - Payment analytics

### Webhooks
- `POST /api/webhooks/efichepay` - Mobile money confirmations

## 🌍 Rwanda-Specific Features

### Phone Validation
```php
// Rwanda phone format: +2507xxxxxxxx
'phone' => 'required|string|regex:/^\+2507\d{8}$/'
```

### Currency
- All amounts in **RWF** (Rwandan Franc)
- Decimal format: `50000.00`

### Insurance Providers
- **RSSB** - Rwanda Social Security Board
- **MMI** - Mutuelle de Santé
- **MediCare Rwanda**
- **Prime Insurance**

## 📁 Project Structure

```
app/
├── Http/Controllers/Api/
│   ├── AuthController.php          # Authentication
│   ├── PatientController.php       # Patient CRUD
│   ├── InvoiceController.php       # Billing operations
│   ├── PaymentController.php       # Payment processing
│   └── WebhookController.php       # Webhook handling
├── Models/
│   ├── Patient.php                 # Patient model
│   ├── Invoice.php                 # Invoice model
│   ├── Payment.php                 # Payment model
│   └── User.php                    # User with roles
├── Services/
│   ├── PaymentService.php          # Payment business logic
│   ├── InvoiceService.php          # Invoice operations
│   └── WebhookService.php          # Webhook processing
└── Http/Resources/                 # API response formatting
```

## 🚨 Known Limitations

### V1 Scope Exclusions

1. **Insurance Claims Processing** - Complex approval workflows deferred to V2
2. **Multi-Currency Support** - Rwanda operates exclusively in RWF
3. **Advanced Reporting** - Basic analytics only in V1
4. **Patient Payment Plans** - Single-visit billing focus for V1
5. **Lab System Integration** - External integrations deferred

### Technical Shortcuts

1. **Mobile Money API** - Mock implementation for testing
2. **Email Notifications** - Basic logging only
3. **File Uploads** - Local storage only
4. **Cache Strategy** - No Redis implementation yet

## 🔧 Development

### Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Configure services in .env
APP_NAME="eFiche Billing API"
APP_ENV=local
APP_KEY=base64:your_generated_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=efiche_billing
DB_USERNAME=root
DB_PASSWORD=

# Cache and Session
CACHE_DRIVER=file
SESSION_DRIVER=file

# Queue (optional)
QUEUE_CONNECTION=sync

# Mail (development)
MAIL_MAILER=log
MAIL_HOST=null
MAIL_PORT=null
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@efiche.rw"
MAIL_FROM_NAME="${APP_NAME}"
```

### Database Migrations

```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Individual migrations
php artisan migrate
php artisan db:seed --class=UserSeeder

# Reset specific tables
php artisan migrate:refresh --step=1

# Check migration status
php artisan migrate:status
```

### Common Development Commands

```bash
# Create new migration
php artisan make:migration create_table_name

# Create new model with migration
php artisan make:model ModelName -m

# Create new controller
php artisan make:controller Api/ControllerName

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate application key
php artisan key:generate

# List all routes
php artisan route:list

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### API Documentation

- **Frontend Integration**: `FRONTEND_API_DOCUMENTATION.md`
- **Technical Design**: `TECHNICAL_TEST_DESIGN_DOCUMENT.md`
- **API Reference**: `API_DOCUMENTATION.md`

## 🚀 Production Deployment

### Environment Requirements

- **PHP**: 8.1+ with extensions: mbstring, pdo_mysql, bcmath, json
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Nginx or Apache with mod_rewrite
- **Memory**: Minimum 512MB, Recommended 1GB+
- **Storage**: Minimum 1GB available

### Production Setup Steps

```bash
# 1. Clone and setup production environment
git clone <repository-url> /var/www/efiche-billing
cd /var/www/efiche-billing
composer install --optimize-autoloader --no-dev

# 2. Configure production environment
cp .env.example .env
php artisan key:generate
# Edit .env with production values
APP_ENV=production
APP_DEBUG=false

# 3. Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 4. Setup database
php artisan migrate --force
php artisan db:seed --force

# 5. Setup file permissions
chown -R www-data:www-data /var/www/efiche-billing
chmod -R 755 /var/www/efiche-billing/storage
chmod -R 755 /var/www/efiche-billing/bootstrap/cache

# 6. Create storage link
php artisan storage:link
```

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/efiche-billing/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Apache Configuration Example

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/efiche-billing/public

    <Directory /var/www/efiche-billing>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 🧪 Debugging

### Common Issues & Solutions

**1. Database Connection Errors**
```bash
# Check .env configuration
cat .env | grep DB_

# Test connection manually
php artisan tinker
>>> DB::connection()->getDatabaseName();

# Reset database if needed
php artisan migrate:fresh
```

**2. Migration Errors**
```bash
# Check migration status
php artisan migrate:status

# Rollback and retry
php artisan migrate:rollback
php artisan migrate

# Force fresh start
php artisan migrate:fresh --seed
```

**3. Authentication Issues**
```bash
# Clear Sanctum cache
php artisan cache:clear

# Check user data
php artisan tinker
>>> App\Models\User::count();
>>> $user = App\Models\User::where('email', 'admin@efiche.rw')->first();
>>> $user->tokens;
```

**4. Permission Issues**
```bash
# Fix storage permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# Clear caches
php artisan cache:clear
php artisan config:clear
```

**5. Webhook Testing Issues**
```bash
# Test webhook endpoint directly
curl -X POST http://localhost:8000/api/webhooks/efichepay \
  -H "Content-Type: application/json" \
  -d '{"eventId":"test_123","status":"PAYMENT_COMPLETE"}'

# Check webhook logs
tail -f storage/logs/laravel.log
```

### Debug Commands

```bash
# Check routes
php artisan route:list --name=api

# Test authentication
php artisan tinker
>>> auth()->check();

# Check database
php artisan tinker  
>>> DB::table('users')->count();
>>> DB::table('invoices')->count();

# Check model relationships
php artisan tinker
>>> $patient = App\Models\Patient::first();
>>> $patient->visits->count();
```

### Performance Testing

```bash
# Check query performance
php artisan tinker
>>> DB::enableQueryLog();
>>> App\Models\Invoice::with('payments')->get();
>>> DB::getQueryLog();

# Memory usage check
php artisan tinker
>>> memory_get_usage(true);
>>> memory_get_peak_usage(true);
```

## 📞 Support

### Technical Documentation
- **Design Document**: Comprehensive system architecture
- **API Documentation**: Complete endpoint reference
- **Testing Guide**: Manual and automated testing

### Test Credentials
All test users use password: `password123`

---

## 🇷🇼 Made for Rwanda's Healthcare

Built specifically for the unique challenges of Rwanda's healthcare system, from Kigali hospitals to rural health posts with intermittent connectivity.

**🚀 Production Ready • 🔒 Race Condition Free • 📱 Offline Capable**
