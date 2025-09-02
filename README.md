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

### Create Test Data for API Testing
```bash
# Create test data interactively
php artisan app:create-test-data

# Create test data with specific brand name
php artisan app:create-test-data --brand-name="RankMath"
```

This command creates a complete test environment with:
- **Brand** with authentication token
- **3 Products** (RankMath SEO, Content AI, Analytics Pro)
- **3 License Keys** for different customer emails
- **3 Licenses** with various statuses
- **3 Activations** with different instance types

The command returns the **Brand Auth Token** that can be used to test authenticated API endpoints.

### Testing API Endpoints with Generated Data
After running the command, you'll get:
- **Brand Auth Token** for authentication
- **Sample UUIDs** for all created entities
- **Ready-to-use cURL commands** for testing

Example workflow:
1. Run `php artisan app:create-test-data --brand-name="MyBrand"`
2. Copy the generated **Auth Token**
3. Use the token in the **Authorization header**: `Authorization: Bearer {token}`
4. Test API endpoints using the provided cURL examples
5. Use the generated UUIDs to test specific resources

### Code Quality Tools
```bash
# Run Laravel Pint (code style)
./vendor/bin/pint

# Run all quality checks
./vendor/bin/pint && php artisan test

## API Documentation

### Interactive API Documentation
The project includes comprehensive API documentation generated using Laravel Scramble:

- **🌐 View Documentation**: Visit `/docs/api` in your browser
- **📄 JSON Specification**: Download OpenAPI spec at `/docs/api.json`
- **🔄 Regenerate Docs**: Run `php artisan docs:generate` to update documentation

### Documentation Features
- **Interactive UI**: Try API endpoints directly from the documentation
- **Request/Response Examples**: See exact data structures and validation rules
- **Authentication**: Clear documentation of required headers and tokens
- **Error Handling**: Comprehensive error response documentation
- **Schema Definitions**: Detailed model and enum documentation

### Regenerating Documentation
```bash
# Generate fresh API documentation
php artisan docs:generate

# View the documentation
# Open http://localhost:8002/docs/api in your browser
```

### Documentation Structure
The generated documentation includes:
- **All API Endpoints**: Complete coverage of all implemented endpoints
- **Request Validation**: Form request rules and validation messages
- **Response Schemas**: API Resource structures and data formats
- **Authentication**: Bearer token requirements and usage
- **Error Codes**: HTTP status codes and error message formats
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

## API Authentication

The License Service uses a custom authentication system for brand-facing endpoints:

- **Brand API Key**: Each brand has a unique API key for authentication
- **Header Format**: `X-Tenant: {BRAND_API_KEY}`
- **Usage**: Add this header to all brand-facing API requests

### Example Usage

```bash
# List products for a brand
curl -H "X-Tenant: brand_abc123def456" \
     http://localhost:8002/api/v1/products

# Create a new license
curl -X POST \
     -H "X-Tenant: brand_abc123def456" \
     -H "Content-Type: application/json" \
     -d '{"license_key_uuid":"uuid","product_uuid":"uuid"}' \
     http://localhost:8002/api/v1/licenses
```

### Endpoint Types

- **Brand-facing endpoints**: Require `X-Tenant` header with valid brand API key
- **Product-facing endpoints**: No authentication required (public access)

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

**Get Seat Usage (US5)**
```bash
GET /licenses/{uuid}/seat-usage
```

**Force Deactivate All Seats (US5 - Brand Only)**
```bash
POST /licenses/{uuid}/force-deactivate-seats
Authorization: Bearer {brand-api-key}
Content-Type: application/json

{
    "reason": "Administrative deactivation"
}
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

#### Cross-Brand License Listing (Brand-facing - Requires Authentication)

**List Customer Licenses Across All Brands (US6)**
```bash
GET /customers/licenses?customer_email=user@example.com
Authorization: Bearer {brand-api-key}
```

**List Customer Licenses Within Brand (US6)**
```bash
GET /customers/licenses/brand?customer_email=user@example.com
Authorization: Bearer {brand-api-key}
```

**Response Example (Cross-Brand)**
```json
{
  "success": true,
  "message": "Successfully retrieved license information for customer user@example.com",
  "data": {
    "customer_email": "user@example.com",
    "total_license_keys": 2,
    "total_licenses": 3,
    "brands_count": 2,
    "brands": [
      {
        "uuid": "brand-uuid-1",
        "name": "RankMath",
        "slug": "rankmath",
        "domain": "rankmath.com"
      }
    ],
    "license_keys": [...],
    "licenses_summary": {
      "total_active": 2,
      "total_suspended": 0,
      "total_cancelled": 0,
      "total_expired": 1
    },
    "products_summary": [...]
  }
}
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

### ✅ **US5: End-user product or customer can deactivate a seat** - FULLY IMPLEMENTED
- **Seat Deactivation**: End-users can deactivate specific license activations
- **Seat Usage Monitoring**: Check current seat usage and availability
- **Force Deactivation**: Brands can force deactivate all seats for administrative purposes
- **Audit Logging**: All seat deactivations are logged for compliance
- **Seat Management**: Comprehensive seat tracking and management system

### ✅ **US6: Brands can list licenses by customer email across all brands** - FULLY IMPLEMENTED
- **Cross-Brand License Listing**: Brands can see customer licenses across the entire ecosystem
- **Brand-Specific Queries**: Filter customer licenses within specific brands
- **Comprehensive Customer Summary**: Complete overview of customer's license ecosystem
- **Multi-Brand Support**: Access to customer data across all brands with proper authentication
- **API Endpoints**: Two new authenticated endpoints for cross-brand operations

**Current Progress: 6 out of 6 User Stories (100%) are fully implemented**

## Architecture

### Models
- **Brand**: Multi-tenant brand management with API key authentication
- **Product**: Products within brands with seat management capabilities
- **LicenseKey**: Customer license keys that unlock multiple products
- **License**: Individual product licenses with lifecycle management
- **Activation**: Instance-specific license activations with seat tracking

### Services
- **Repository Pattern**: Fully implemented base repository interface and implementations
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
