# Laravel IP Guard

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ahs/laravel-ip-guard.svg?style=flat-square)](https://packagist.org/packages/ahs/laravel-ip-guard)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ahs/laravel-ip-guard/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ahs/laravel-ip-guard/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ahs/laravel-ip-guard/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ahs/laravel-ip-guard/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ahs/laravel-ip-guard.svg?style=flat-square)](https://packagist.org/packages/ahs/laravel-ip-guard)

A powerful Laravel middleware package for IP-based access control with whitelist and blacklist support. Protect your application by allowing or blocking specific IP addresses, IP ranges (CIDR), and wildcard patterns.

## Features

- **Whitelist Support**: Allow only specific IPs or IP ranges
- **Blacklist Support**: Block specific IPs or IP ranges  
- **CIDR Notation**: Support for IP ranges like `192.168.1.0/24`
- **Wildcard Patterns**: Support for patterns like `192.168.*.*`
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
composer require ahs/laravel-ip-guard
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
    | IP Lists
    |--------------------------------------------------------------------------
    | Configure whitelist and blacklist IPs. Use '*' to match all IPs.
    | Supports exact IPs, CIDR notation, and wildcard patterns.
    */
    
    'whitelist' => [
        '203.0.113.10',        // Exact IP
        '10.0.0.0/8',          // CIDR range
        '192.168.*.*',         // Wildcard pattern
    ],
    
    'blacklist' => [
        '192.168.1.100',       // Block specific IP
        '10.0.1.0/24',         // Block IP range
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

The package supports multiple IP matching formats:

### Exact IP Matching
```php
'whitelist' => ['203.0.113.10', '198.51.100.20']
```

### CIDR Notation
```php
'whitelist' => [
    '10.0.0.0/8',      // All IPs from 10.0.0.0 to 10.255.255.255
    '192.168.1.0/24',  // All IPs from 192.168.1.0 to 192.168.1.255
    '172.16.0.0/12',   // All IPs from 172.16.0.0 to 172.31.255.255
]
```

### Wildcard Patterns
```php
'whitelist' => [
    '192.168.*.*',     // All IPs starting with 192.168
    '10.0.*.100',      // All IPs like 10.0.x.100
    '203.0.113.*',     // All IPs like 203.0.113.x
]
```

### Block All IPs
```php
'blacklist' => ['*']  // Block all IPs (useful with whitelist)
```

## Priority Rules

The middleware follows this priority order:

1. **Whitelist Check**: If the client IP matches any whitelist rule, access is **always allowed**
2. **Blacklist Check**: If the client IP matches any blacklist rule, access is **denied**
3. **Default**: If no rules match, access is **allowed**

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
    '198.51.100.0/24', // Admin network
],
'blacklist' => ['*'],  // Block all other IPs
```

### Block Specific Countries
```php
'blacklist' => [
    '1.0.0.0/8',       // Example: Block IP range
    '2.0.0.0/8',       // Add more ranges as needed
],
```

### Development Environment
```php
'whitelist' => [
    '127.0.0.1',       // Localhost
    '192.168.*.*',     // Local network
    '10.0.0.0/8',      // Private network
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
3. **Blocked legitimate users**: Review your whitelist/blacklist rules
4. **CIDR not working**: Verify the CIDR notation is correct

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
