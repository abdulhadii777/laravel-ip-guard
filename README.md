# Laravel IP Guard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abdulhadii777/laravel-ip-guard.svg?style=flat-square)](https://packagist.org/packages/abdulhadii777/laravel-ip-guard)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abdulhadii777/laravel-ip-guard/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abdulhadii777/laravel-ip-guard/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abdulhadii777/laravel-ip-guard/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abdulhadii777/laravel-ip-guard/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abdulhadii777/laravel-ip-guard.svg?style=flat-square)](https://packagist.org/packages/abdulhadii777/laravel-ip-guard)

A powerful Laravel middleware package for IP-based access control with whitelist and blacklist support. Protect your application by allowing or blocking specific IP addresses with a flexible priority system.

**Current Version:** v0.1.1

## Features

- **Priority-Based Access Control**: Blacklist takes highest priority over whitelist
- **Environment Toggle**: Enable/disable IP restrictions via environment variable
- **Whitelist Support**: Allow only specific IPs
- **Blacklist Support**: Block specific IPs (including `*` for all IPs)
- **Exact IP Matching**: Support for exact IP addresses only
- **Proxy Support**: Works behind load balancers and proxies
- **Flexible Configuration**: Easy to configure via config file
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

The package will automatically register itself. You can publish the config file with:

```bash
php artisan vendor:publish --provider="Ahs\LaravelIpGuard\LaravelIpGuardServiceProvider" --tag="config"
```

Or use the package's publish command:

```bash
php artisan vendor:publish --tag="ip-guard-config"
```

## Configuration

After publishing the config file, you can configure the package in `config/ip-guard.php`:

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
    | IP Lists
    |--------------------------------------------------------------------------
    | Configure whitelist and blacklist IPs. Use '*' to match all IPs.
    | Supports exact IPs only.
    |
    | Priority order:
    | 1. Blacklist (highest priority) - if IP matches, block access
    |    - '*' in blacklist blocks ALL IPs regardless of whitelist
    | 2. Whitelist - if IP matches, allow access (only if not blacklisted)
    | 3. null/empty - no restrictions (only if not blacklisted)
    */
    
    'whitelist' => [
        '203.0.113.10',        // Exact IP
        '198.51.100.20',       // Exact IP
    ],
    
    'blacklist' => [
        '192.168.1.100',       // Block specific IP
        '10.0.1.50',           // Block specific IP
    ],

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

## Usage

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
// config/ip-guard.php
'whitelist' => [
    '203.0.113.10',    // Admin office IP
    '198.51.100.20',   // Admin home IP
],
'blacklist' => ['*'],  // Block all other IPs
```

### Environment-Based Control
```env
# .env
IP_GUARD_ENABLED=true   # Enable IP restrictions
# IP_GUARD_ENABLED=false  # Disable IP restrictions entirely
```

### Mixed Blacklist and Whitelist
```php
// config/ip-guard.php
'blacklist' => [
    '192.168.1.100',   // Block specific problematic IP
    '10.0.1.50',       // Block specific IP
],
'whitelist' => [
    '203.0.113.10',    // Allow specific trusted IP
    '198.51.100.20',   // Allow specific trusted IP
],
// Result: Only whitelisted IPs allowed, except blacklisted ones
```

### Block Specific IPs
```php
'blacklist' => [
    '1.2.3.4',         // Block specific IP
    '5.6.7.8',         // Block another IP
],
```

### Development Environment
```php
'whitelist' => [
    '127.0.0.1',       // Localhost
    '192.168.1.100',   // Local development IP
    '10.0.0.50',       // Another local IP
],
```

## Testing

Run the tests with:

```bash
composer test
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
