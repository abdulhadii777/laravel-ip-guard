<?php

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
    | Lists
    |--------------------------------------------------------------------------
    | Use '*' to match all IPs or exact IPs (e.g., '1.2.3.4').
    | null or [] means "no specific list".
    |
    | Priority order:
    | 1. Blacklist (highest priority) - if IP matches, block access
    |    - '*' in blacklist blocks ALL IPs regardless of whitelist
    | 2. Whitelist - if IP matches, allow access (only if not blacklisted)
    | 3. null/empty - no restrictions (only if not blacklisted)
    |
    | Example: blacklist=['*'] + whitelist=['1.2.3.4'] = NO ACCESS (all blocked)
    */

    'blacklist' => null,   // e.g. ['*'] to block everyone except explicit whitelist
    'whitelist' => null,   // e.g. ['203.0.113.10', '198.51.100.20']

    /*
    |--------------------------------------------------------------------------
    | Client IP resolution
    |--------------------------------------------------------------------------
    | If you're behind a proxy/load balancer, trust proxies at app level and
    | set which header to prefer. Otherwise request()->ip() is used.
    */

    'ip_header' => null, // e.g. 'X-Forwarded-For' or null to use request()->ip()

    /*
    |--------------------------------------------------------------------------
    | Error response
    |--------------------------------------------------------------------------
    */

    'error' => [
        'status'  => 403,
        'message' => 'Access denied from your IP address.',
        'json'    => true, // return JSON if true, otherwise plain text
    ],
];
