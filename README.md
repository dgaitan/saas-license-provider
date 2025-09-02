# Centralized License Service

A multi-tenant License Service for managing licenses and entitlements across multiple brands in the group.one ecosystem.

## Overview

This service acts as the single source of truth for licenses and entitlements across brands like WP Rocket, Imagify, RankMath, BackWPup, RocketCDN, WP.one, etc. It provides a centralized API for brand systems to provision and manage licenses, while allowing end-user products to activate and validate their licenses.

## Features

- **Multi-tenant Architecture**: Support for multiple brands with isolated data
- **License Management**: Create and manage license keys and licenses
- **Seat Management**: Track and enforce license seat limits
- **License Lifecycle**: Full lifecycle management (create, renew, suspend, resume, cancel)
- **License Activation**: End-user products can activate licenses for specific instances
- **License Status Checking**: Public endpoints for license validation and entitlement checking
- **API-First Design**: RESTful APIs for brand systems and end-user products
- **UUID-based Routing**: Secure and scalable API design
- **Brand Authentication**: Bearer token authentication for brand-facing operations

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

   **Note**: The application runs on port 8002 as specified in the requirements.

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
http://localhost:8002/api/v1
```

### Authentication

#### Brand Authentication (Required for brand-facing operations)
Brand-facing operations require authentication using the brand's API key:
```
Authorization: Bearer {brand-api-key}
```

The API key is automatically generated when a brand is created and can be found in the `brands` table.

#### Public Endpoints (No authentication required)
End-user product endpoints and license status checking endpoints are public and do not require authentication.

### Endpoints

#### License Key Management (Brand-facing - Requires Authentication)

**Create License Key**
```bash
POST /license-keys
Authorization: Bearer {brand-api-key}
Content-Type: application/json

{
    "customer_email": "user@example.com"
}
```

**Get License Key**
```bash
GET /license-keys/{uuid}
Authorization: Bearer {brand-api-key}
```

#### License Management (Brand-facing - Requires Authentication)

**Create License**
```bash
POST /licenses
Authorization: Bearer {brand-api-key}
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
Authorization: Bearer {brand-api-key}
```

#### License Lifecycle Management (Brand-facing - Requires Authentication)

**Renew License**
```bash
PATCH /licenses/{uuid}/renew
Authorization: Bearer {brand-api-key}
Content-Type: application/json

{
    "days": 180
}
```

**Suspend License**
```bash
PATCH /licenses/{uuid}/suspend
Authorization: Bearer {brand-api-key}
Content-Type: application/json
```

**Resume License**
```bash
PATCH /licenses/{uuid}/resume
Authorization: Bearer {brand-api-key}
Content-Type: application/json
```

**Cancel License**
```bash
PATCH /licenses/{uuid}/cancel
Authorization: Bearer {brand-api-key}
Content-Type: application/json
```

#### License Activation (End-User Products - Public)

**Activate License**
```bash
POST /licenses/{uuid}/activate
Content-Type: application/json

{
    "instance_id": "site-123",
    "instance_type": "wordpress",
    "instance_url": "https://example.com",
    "machine_id": "machine-456"
}
```

**Deactivate License**
```bash
POST /licenses/{uuid}/deactivate
Content-Type: application/json

{
    "instance_id": "site-123",
    "instance_type": "wordpress",
    "instance_url": "https://example.com",
    "machine_id": "machine-456"
}
```

**Get Activation Status**
```bash
GET /licenses/{uuid}/activation-status?instance_id=site-123&instance_type=wordpress
```

#### License Status Checking (End-User Products - Public)

**Get License Key Status**
```bash
GET /license-keys/{uuid}/status
```

**Check License Key Validity**
```bash
GET /license-keys/{uuid}/is-valid
```

**Get License Key Entitlements**
```bash
GET /license-keys/{uuid}/entitlements
```

**Get Seat Usage**
```bash
GET /license-keys/{uuid}/seat-usage
```

## User Stories Implementation Status

### ✅ **US1: Brand can provision a license** - FULLY IMPLEMENTED
- **Create License Keys**: Brands can create license keys for customers
- **Create Licenses**: Brands can create licenses and associate them with license keys
- **Multiple Products**: Single license key can unlock multiple products from the same brand
- **Brand Isolation**: Each brand manages their own licenses independently

### ✅ **US2: Brand can change license lifecycle** - FULLY IMPLEMENTED
- **Renew Licenses**: Extend license expiration dates
- **Suspend/Resume**: Temporarily disable and re-enable licenses
- **Cancel Licenses**: Permanently terminate licenses
- **Status Management**: Track license status changes

### ✅ **US3: End-user product can activate a license** - FULLY IMPLEMENTED
- **Instance Activation**: Activate licenses for specific instances (sites, machines)
- **Seat Management**: Track seat usage and enforce limits
- **Multiple Instances**: Support multiple activations per license
- **Deactivation**: Remove activations to free up seats

### ✅ **US4: User can check license status** - FULLY IMPLEMENTED
- **Public Endpoints**: No authentication required for end-users
- **Comprehensive Status**: Overall status, validity, entitlements, and seat usage
- **Multi-Product Support**: Show all products accessible through a license key
- **Real-time Information**: Current seat usage and availability

### 🔄 **US5: End-user product or customer can deactivate a seat** - PARTIALLY IMPLEMENTED
- **Deactivation Endpoint**: Available through US3 implementation
- **Seat Management**: Integrated with activation system
- **Full Implementation**: Would require additional business logic and validation

### 🔄 **US6: Brands can list licenses by customer email across all brands** - DESIGNED ONLY
- **Cross-Brand Queries**: Architecture supports this functionality
- **Multi-Tenancy Service**: Base infrastructure in place
- **Implementation**: Would require additional API endpoints and business logic

## Architecture

### Models
- **Brand**: Multi-tenant brand management with API key authentication
- **Product**: Products within brands with seat management capabilities
- **LicenseKey**: Customer license keys that unlock multiple products
- **License**: Individual product licenses with lifecycle management
- **Activation**: Instance-specific license activations with seat tracking

### Services
- **Repository Pattern**: Base repository interface and implementations
- **Service Layer**: Business logic separation with dependency injection
- **Multi-Tenancy Service**: Centralized multi-tenant operations
- **License Services**: Brand and product-facing license operations

### Controllers
- **API Versioning**: `/api/v1/` prefix for future extensibility
- **Controller Grouping**: Routes grouped by controller for organization
- **Authentication**: Brand authentication middleware for protected endpoints
- **Public Endpoints**: License status checking without authentication

### Middleware
- **Force JSON**: Ensures all API responses are JSON
- **Brand Authentication**: Validates brand API keys for protected operations
- **Multi-Tenancy**: Enforces brand isolation and ownership

## Testing

### Test Coverage
- **Unit Tests**: Model relationships, validation, and business logic
- **Feature Tests**: API endpoint testing with authentication
- **Integration Tests**: Complete user story workflows
- **Multi-Tenancy Tests**: Brand isolation and cross-brand operations

### Test Framework
- **Pest**: Modern PHP testing framework
- **SQLite In-Memory**: Fast and isolated test database
- **Factories**: Comprehensive test data generation
- **Authentication Traits**: Reusable test authentication helpers

## Development

### Code Style
```bash
./vendor/bin/pint
```

### Static Analysis
```bash
./vendor/bin/phpstan analyse
```

### Database Seeding
```bash
php artisan db:seed
```

## Contributing

1. Follow Laravel coding standards
2. Write tests for new features using Pest
3. Use meaningful commit messages
4. Ensure all tests pass before submitting PR
5. Follow the DRY principle and use base classes/traits

## License

This project is proprietary to group.one
