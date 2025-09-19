# Laravel IP Guard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abdulhadii777/laravel-ip-guard.svg?style=flat-square)](https://packagist.org/packages/abdulhadii777/laravel-ip-guard)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abdulhadii777/laravel-ip-guard/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abdulhadii777/laravel-ip-guard/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abdulhadii777/laravel-ip-guard/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abdulhadii777/laravel-ip-guard/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abdulhadii777/laravel-ip-guard.svg?style=flat-square)](https://packagist.org/packages/abdulhadii777/laravel-ip-guard)

A powerful Laravel middleware package for IP-based access control with whitelist and blacklist support. Protect your application by allowing or blocking specific IP addresses with dynamic database management and a flexible priority system.

**Current Version:** v0.1.1

## Features

- **Dynamic Database Management**: Add/remove IPs without code changes
- **Priority-Based Access Control**: Blacklist takes highest priority over whitelist
- **Environment Toggle**: Enable/disable IP restrictions via environment variable
- **Whitelist Support**: Allow only specific IPs
- **Blacklist Support**: Block specific IPs (including `*` for all IPs)
- **Exact IP Matching**: Support for exact IP addresses only
- **Soft Toggle Control**: Enable/disable IPs without deleting records
- **Artisan Commands**: Full CLI management interface
- **Facade Support**: Easy programmatic access
- **Proxy Support**: Works behind load balancers and proxies
- **Fallback Support**: Falls back to config if database unavailable
- **Custom Error Responses**: JSON or plain text error responses
- **Laravel Integration**: Seamless integration with Laravel middleware

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-ip-guard.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-ip-guard)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require abdulhadii777/laravel-ip-guard
```

The package will automatically register itself. You can publish the config file and migration with:

```bash
php artisan vendor:publish --provider="Ahs\LaravelIpGuard\LaravelIpGuardServiceProvider" --tag="config"
php artisan vendor:publish --provider="Ahs\LaravelIpGuard\LaravelIpGuardServiceProvider" --tag="migrations"
```

Or use the package's publish commands:

```bash
php artisan vendor:publish --tag="ip-guard-config"
php artisan vendor:publish --tag="ip-guard-migrations"
```

Then run the migration:

```bash
php artisan migrate
```

## Configuration

After publishing the config file, you can configure the package in `config/ip-guard.php`. The package now uses **database-driven IP management** by default, with config as fallback:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Enable/Disable IP Guard
    |--------------------------------------------------------------------------
    | Set to false to disable IP restrictions entirely.
    | You can also use IP_GUARD_ENABLED environment variable.
    */
    
    'enabled' => env('IP_GUARD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | IP Lists (Fallback Configuration)
    |--------------------------------------------------------------------------
    | These are used as fallback when database is unavailable.
    | Use '*' to match all IPs. Supports exact IPs only.
    |
    | Priority order:
    | 1. Blacklist (highest priority) - if IP matches, block access
    |    - '*' in blacklist blocks ALL IPs regardless of whitelist
    | 2. Whitelist - if IP matches, allow access (only if not blacklisted)
    | 3. null/empty - no restrictions (only if not blacklisted)
    |
    | Example: blacklist=['*'] + whitelist=['1.2.3.4'] = NO ACCESS (all blocked)
    */
    
    'whitelist' => null,   // Fallback whitelist (use database for dynamic management)
    'blacklist' => null,   // Fallback blacklist (use database for dynamic management)

    /*
    |--------------------------------------------------------------------------
    | Client IP Resolution
    |--------------------------------------------------------------------------
    | Set the header to use for client IP when behind a proxy/load balancer.
    | Set to null to use Laravel's default IP detection.
    */
    
    'ip_header' => 'X-Forwarded-For', // or 'X-Real-IP', 'CF-Connecting-IP', etc.

    /*
    |--------------------------------------------------------------------------
    | Error Response
    |--------------------------------------------------------------------------
    | Configure the response when access is denied.
    */
    
    'error' => [
        'status'  => 403,
        'message' => 'Access denied from your IP address.',
        'json'    => true, // Return JSON response if true, plain text if false
    ],
];
```

## Database Management

The package now uses **database-driven IP management** by default. IPs are stored in the `ip_guards` table with the following structure:

- `id`: Primary key
- `ip_address`: The IP address (exact match only)
- `type`: Either 'whitelist' or 'blacklist'
- `description`: Optional description for the IP
- `is_active`: Boolean flag to enable/disable the IP
- `created_at` / `updated_at`: Timestamps

### Database Schema

```sql
CREATE TABLE ip_guards (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(255) NOT NULL,
    type ENUM('whitelist', 'blacklist') NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_ip_type (ip_address, type),
    INDEX idx_type_active (type, is_active),
    UNIQUE KEY unique_ip_type (ip_address, type)
);
```

## Usage

### Dynamic IP Management

#### Via Facade

```php
use Ahs\LaravelIpGuard\Facades\LaravelIpGuard;

// Add IPs to whitelist
LaravelIpGuard::addToWhitelist('192.168.1.100', 'Office IP');
LaravelIpGuard::addToWhitelist('203.0.113.10', 'Admin IP');

// Add IPs to blacklist
LaravelIpGuard::addToBlacklist('10.0.0.50', 'Blocked IP');
LaravelIpGuard::addToBlacklist('*', 'Block all IPs');

// Check IP status
$isWhitelisted = LaravelIpGuard::isWhitelisted('192.168.1.100');
$isBlacklisted = LaravelIpGuard::isBlacklisted('10.0.0.50');

// Get IP lists
$whitelistIps = LaravelIpGuard::getWhitelistIps();
$blacklistIps = LaravelIpGuard::getBlacklistIps();

// Remove IPs
LaravelIpGuard::removeFromWhitelist('192.168.1.100');
LaravelIpGuard::removeFromBlacklist('10.0.0.50');

// Bulk operations
$ips = ['192.168.1.101', '192.168.1.102', '192.168.1.103'];
LaravelIpGuard::bulkAddToWhitelist($ips, 'Office Network');

// Get statistics
$stats = LaravelIpGuard::getStats();
// Returns: ['whitelist_count' => 5, 'blacklist_count' => 2, 'total_count' => 7]
```

#### Via Artisan Commands

```bash
# Add IPs
php artisan ip-guard:manage add whitelist 192.168.1.100 --description="Office IP"
php artisan ip-guard:manage add blacklist 10.0.0.50 --description="Blocked IP"

# List IPs
php artisan ip-guard:manage list                    # List all IPs
php artisan ip-guard:manage list whitelist          # List whitelist only
php artisan ip-guard:manage list blacklist          # List blacklist only

# Remove IPs
php artisan ip-guard:manage remove whitelist 192.168.1.100
php artisan ip-guard:manage remove blacklist 10.0.0.50

# Toggle IP status (enable/disable without deleting)
php artisan ip-guard:manage toggle --id=1

# Clear IPs
php artisan ip-guard:manage clear whitelist         # Clear all whitelist IPs
php artisan ip-guard:manage clear blacklist         # Clear all blacklist IPs
php artisan ip-guard:manage clear                   # Clear all IPs

# Show statistics
php artisan ip-guard:manage stats
```

#### Via Model

```php
use Ahs\LaravelIpGuard\Models\IpGuard;

// Direct model usage
IpGuard::addToWhitelist('192.168.1.100', 'Office IP');
IpGuard::addToBlacklist('10.0.0.50', 'Blocked IP');

// Query IPs
$whitelistIps = IpGuard::whitelist()->active()->get();
$blacklistIps = IpGuard::blacklist()->active()->get();

// Toggle IP status
$ip = IpGuard::find(1);
$ip->toggleActive(); // Toggle between active/inactive

// Check if IP is in list
$isWhitelisted = IpGuard::isWhitelisted('192.168.1.100');
$isBlacklisted = IpGuard::isBlacklisted('10.0.0.50');
```

### Basic Middleware Usage

Apply the middleware to routes in your `routes/web.php` or `routes/api.php`:

```php
// Apply to specific routes
Route::get('/admin', function () {
    return view('admin');
})->middleware('ip.guard');

// Apply to route groups
Route::middleware(['ip.guard'])->group(function () {
    Route::get('/dashboard', 'DashboardController@index');
    Route::get('/profile', 'ProfileController@index');
});

// Apply to controllers
Route::get('/admin', 'AdminController@index')->middleware('ip.guard');
```

### Global Middleware

Add the middleware globally in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... other middleware
    \Ahs\LaravelIpGuard\Middleware\IpGuard::class,
];
```

### Controller Middleware

Apply in your controller constructor:

```php
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('ip.guard');
    }
}
```

## IP Matching Rules

The package supports exact IP matching only:

### Exact IP Matching
```php
'whitelist' => ['203.0.113.10', '198.51.100.20']
```

### Block All IPs
```php
'blacklist' => ['*']  // Block all IPs (useful with whitelist)
```

## Priority Rules

The middleware follows this priority order:

1. **Environment Check**: If `IP_GUARD_ENABLED=false`, all IPs are **allowed** (no restrictions)
2. **Blacklist Check** (Highest Priority): If the client IP matches any blacklist rule, access is **denied**
   - `*` in blacklist blocks **ALL IPs** regardless of whitelist
3. **Whitelist Check**: If whitelist is configured and IP matches, access is **allowed**
4. **Whitelist Enforcement**: If whitelist is configured but IP doesn't match, access is **denied**
5. **Default**: If whitelist is null/empty and IP is not blacklisted, access is **allowed**

### Examples:

**Scenario 1: Blacklist with `*`**
```php
'blacklist' => ['*'],
'whitelist' => ['1.2.3.4', '5.6.7.8']
// Result: NO ACCESS (all IPs blocked by blacklist)
```

**Scenario 2: Normal priority**
```php
'blacklist' => ['192.168.1.100'],
'whitelist' => ['1.2.3.4', '5.6.7.8']
// Result: Only 1.2.3.4 and 5.6.7.8 allowed, 192.168.1.100 blocked, others blocked
```

**Scenario 3: No restrictions**
```php
'blacklist' => null,
'whitelist' => null
// Result: ALL ACCESS (no restrictions)
```

**Scenario 4: Disabled**
```env
IP_GUARD_ENABLED=false
// Result: ALL ACCESS (IP guard disabled)
```

## Proxy and Load Balancer Support

When your application is behind a proxy or load balancer, configure the `ip_header` option:

```php
'ip_header' => 'X-Forwarded-For',  // Most common
// or
'ip_header' => 'X-Real-IP',        // Nginx
// or  
'ip_header' => 'CF-Connecting-IP', // Cloudflare
```

Make sure your Laravel application trusts the proxy by configuring `TrustProxies` middleware.

## Error Responses

### JSON Response (default)
```json
{
    "message": "Access denied from your IP address."
}
```

### Plain Text Response
```
Access denied from your IP address.
```

Configure the response format in the config:

```php
'error' => [
    'status'  => 403,
    'message' => 'Custom access denied message',
    'json'    => false, // Use plain text instead of JSON
],
```

## Advanced Examples

### Admin Panel Protection
```php
// Via Artisan Commands
php artisan ip-guard:manage add whitelist 203.0.113.10 --description="Admin Office"
php artisan ip-guard:manage add whitelist 198.51.100.20 --description="Admin Home"
php artisan ip-guard:manage add blacklist "*" --description="Block all others"

// Via Facade
LaravelIpGuard::addToWhitelist('203.0.113.10', 'Admin Office');
LaravelIpGuard::addToWhitelist('198.51.100.20', 'Admin Home');
LaravelIpGuard::addToBlacklist('*', 'Block all others');
```

### Environment-Based Control
```env
# .env
IP_GUARD_ENABLED=true   # Enable IP restrictions
# IP_GUARD_ENABLED=false  # Disable IP restrictions entirely
```

### Mixed Blacklist and Whitelist
```php
// Via Artisan Commands
php artisan ip-guard:manage add blacklist 192.168.1.100 --description="Problematic IP"
php artisan ip-guard:manage add blacklist 10.0.1.50 --description="Blocked IP"
php artisan ip-guard:manage add whitelist 203.0.113.10 --description="Trusted IP"
php artisan ip-guard:manage add whitelist 198.51.100.20 --description="Trusted IP"

// Via Facade
LaravelIpGuard::addToBlacklist('192.168.1.100', 'Problematic IP');
LaravelIpGuard::addToBlacklist('10.0.1.50', 'Blocked IP');
LaravelIpGuard::addToWhitelist('203.0.113.10', 'Trusted IP');
LaravelIpGuard::addToWhitelist('198.51.100.20', 'Trusted IP');
// Result: Only whitelisted IPs allowed, except blacklisted ones
```

### Block Specific IPs
```php
// Via Artisan Commands
php artisan ip-guard:manage add blacklist 1.2.3.4 --description="Blocked IP"
php artisan ip-guard:manage add blacklist 5.6.7.8 --description="Blocked IP"

// Via Facade
LaravelIpGuard::addToBlacklist('1.2.3.4', 'Blocked IP');
LaravelIpGuard::addToBlacklist('5.6.7.8', 'Blocked IP');
```

### Development Environment
```php
// Via Artisan Commands
php artisan ip-guard:manage add whitelist 127.0.0.1 --description="Localhost"
php artisan ip-guard:manage add whitelist 192.168.1.100 --description="Dev IP"
php artisan ip-guard:manage add whitelist 10.0.0.50 --description="Local IP"

// Via Facade
LaravelIpGuard::addToWhitelist('127.0.0.1', 'Localhost');
LaravelIpGuard::addToWhitelist('192.168.1.100', 'Dev IP');
LaravelIpGuard::addToWhitelist('10.0.0.50', 'Local IP');
```

### Temporary IP Management
```php
// Temporarily disable an IP without deleting
$ip = IpGuard::find(1);
$ip->toggleActive(); // Disable
// Later...
$ip->toggleActive(); // Re-enable

// Or via command
php artisan ip-guard:manage toggle --id=1
```

## Testing

The package includes comprehensive tests using Pest 4 with MySQL database testing.

### Prerequisites

1. **MySQL Database**: Ensure MySQL is running and accessible
2. **Test Database**: Create a test database (or run the setup script)

```bash
# Create test database
mysql -u root -p < tests/setup-test-db.sql
```

### Running Tests

```bash
# Run all tests
composer test

# Run tests with coverage
composer test -- --coverage

# Run specific test file
./vendor/bin/pest tests/Unit/Models/IpGuardTest.php

# Run tests with verbose output
./vendor/bin/pest --verbose
```

### Test Structure

The test suite includes:

- **Unit Tests** (`tests/Unit/`):
  - `Models/IpGuardTest.php` - Model functionality and scopes
  - `Services/LaravelIpGuardTest.php` - Service class methods

- **Feature Tests** (`tests/Feature/`):
  - `Middleware/IpGuardTest.php` - Middleware behavior and IP matching
  - `Commands/IpGuardCommandTest.php` - Artisan command functionality
  - `Integration/IpGuardIntegrationTest.php` - End-to-end workflows

### Test Configuration

Tests use MySQL database with the following configuration:
- **Database**: `laravel_ip_guard_test`
- **Host**: `127.0.0.1`
- **Port**: `3306`
- **Username**: `root` (configurable)
- **Password**: Empty (configurable)

### Test Coverage

The test suite covers:
- ✅ Model creation, updates, and deletion
- ✅ Database scopes and relationships
- ✅ Service facade methods
- ✅ Middleware IP matching logic
- ✅ Priority system (blacklist > whitelist)
- ✅ Artisan command functionality
- ✅ Error handling and validation
- ✅ Custom headers and configuration
- ✅ Fallback to config when database unavailable
- ✅ Integration workflows
- ✅ Bulk operations
- ✅ Statistics and reporting

### Custom Test Configuration

You can customize test database settings in `phpunit.xml.dist`:

```xml
<php>
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE" value="your_test_database"/>
    <env name="DB_USERNAME" value="your_username"/>
    <env name="DB_PASSWORD" value="your_password"/>
    <env name="DB_HOST" value="127.0.0.1"/>
    <env name="DB_PORT" value="3306"/>
</php>
```

## Security Considerations

- Always test your IP rules in a staging environment
- Consider using both whitelist and blacklist for maximum security
- Regularly review and update your IP lists
- Monitor access logs for blocked attempts
- Use HTTPS to prevent IP spoofing

## Troubleshooting

### Common Issues

1. **Middleware not working**: Ensure the middleware is properly registered and applied
2. **Wrong IP detected**: Check your `ip_header` configuration and proxy setup
3. **Blocked legitimate users**: Review your whitelist/blacklist rules and priority order
4. **IP guard not working**: Check if `IP_GUARD_ENABLED` is set to `true` in your environment
5. **All IPs blocked**: Check if `*` is in your blacklist (this blocks all IPs)
6. **Whitelist not working**: Remember blacklist takes priority - check if IP is blacklisted first
7. **Invalid IP format**: Ensure all IPs in your lists are valid IPv4 or IPv6 addresses
8. **Database connection issues**: Check if migration has been run and database is accessible
9. **IP not found in database**: Use `php artisan ip-guard:manage list` to check if IP exists
10. **Inactive IPs**: Check if IP is disabled using `php artisan ip-guard:manage toggle --id=X`

### Debug Mode

Enable Laravel's debug mode to see detailed error messages:

```php
// config/app.php
'debug' => true,
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Abdul Hadi](https://github.com/ahs_777)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
