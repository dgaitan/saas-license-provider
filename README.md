# Centralized License Service

A multi-tenant License Service for managing licenses and entitlements across multiple brands in the group.one ecosystem.

## Overview

This service acts as the single source of truth for licenses and entitlements across brands like WP Rocket, Imagify, RankMath, BackWPup, RocketCDN, WP.one, etc.

## Features

- **Multi-tenant Architecture**: Support for multiple brands with isolated data
- **License Management**: Create and manage license keys and licenses
- **Seat Management**: Track and enforce license seat limits
- **API-First Design**: RESTful APIs for brand systems and end-user products
- **UUID-based Routing**: Secure and scalable API design

## Requirements

- PHP 8.1+
- Laravel 11
- SQLite (for development) / PostgreSQL (for production)
- Composer

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd saas-global
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Start the development server**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8002
   ```

## Testing

### Run Tests Locally
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Api/V1/Brand/LicenseControllerTest.php

# Run tests with coverage
php artisan test --coverage
```

### Code Quality Tools
```bash
# Run Laravel Pint (code style)
./vendor/bin/pint

# Run all quality checks
./vendor/bin/pint && php artisan test
```

## Continuous Integration

This project uses GitHub Actions for continuous integration. The workflow runs on every pull request to the `master` branch and includes:

- **PHP 8.2** setup
- **Dependency installation** via Composer
- **Code style checking** with Laravel Pint
- **Test execution** with Pest
- **Artifact upload** for test results

### Workflow Status
The CI pipeline will automatically run when you:
- Create a pull request to `master` or `main`
- Push commits to `master` or `main`

### Required Status Checks
Before merging, ensure:
- ✅ All tests pass
- ✅ Code style checks pass
- ✅ No merge conflicts

## API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication
Currently using placeholder authentication. In production, use Bearer token authentication:
```
Authorization: Bearer {brand-api-key}
```

### Endpoints

#### License Key Management

**Create License Key**
```bash
POST /license-keys
Content-Type: application/json

{
    "customer_email": "user@example.com"
}
```

**Get License Key**
```bash
GET /license-keys/{uuid}
```

#### License Management

**Create License**
```bash
POST /licenses
Content-Type: application/json

{
    "license_key_uuid": "license-key-uuid",
    "product_uuid": "product-uuid",
    "expires_at": "2026-12-31",
    "max_seats": 5
}
```

**Get License**
```bash
GET /licenses/{uuid}
```

#### License Lifecycle Management

**Renew License**
```bash
PATCH /licenses/{uuid}/renew
Content-Type: application/json

{
    "days": 180
}
```

**Suspend License**
```bash
PATCH /licenses/{uuid}/suspend
Content-Type: application/json
```

**Resume License**
```bash
PATCH /licenses/{uuid}/resume
Content-Type: application/json
```

**Cancel License**
```bash
PATCH /licenses/{uuid}/cancel
Content-Type: application/json
```

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test files:
```bash
php artisan test tests/Feature/Api/V1/Brand/
```

## Development

### Code Style
```bash
./vendor/bin/pint
```

### Static Analysis
```bash
./vendor/bin/phpstan analyse
```

## User Stories Implemented

- ✅ **US1**: Brand can provision a license
- ✅ **US2**: Brand can change license lifecycle
- 🔄 **US3**: End-user product can activate a license (designed)
- 🔄 **US4**: User can check license status (designed)
- 🔄 **US5**: End-user product or customer can deactivate a seat (designed)
- 🔄 **US6**: Brands can list licenses by customer email across all brands (designed)

## Architecture

- **Models**: Brand, Product, LicenseKey, License, Activation
- **Controllers**: API controllers with versioning
- **Requests**: Form validation classes
- **Tests**: Pest-based test suite with comprehensive coverage

## Contributing

1. Follow Laravel coding standards
2. Write tests for new features
3. Use meaningful commit messages
4. Ensure all tests pass before submitting PR

## License

This project is proprietary to group.one
