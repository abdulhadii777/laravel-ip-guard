<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lists
    |--------------------------------------------------------------------------
    | Use '*' to match all IPs, exact IPs (e.g., '1.2.3.4'),
    | CIDR (e.g., '10.0.0.0/8'), or wildcards (e.g., '192.168.*.*').
    | null or [] means "no specific list".
    */

    'whitelist' => null,   // e.g. ['203.0.113.10', '10.0.0.0/8']
    'blacklist' => null,   // e.g. ['*'] to block everyone except explicit whitelist

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
