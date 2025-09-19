<?php

namespace Ahs\LaravelIpGuard\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IpGuard
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $config = config('ip-guard');
        
        // Check if IP guard is enabled
        if (!($config['enabled'] ?? true)) {
            return $next($request);
        }

        $whitelist = self::normalizeList($config['whitelist'] ?? null);
        $blacklist = self::normalizeList($config['blacklist'] ?? null);
        $ipHeader  = $config['ip_header'] ?? null;

        $clientIp = self::getClientIp($request, $ipHeader);

        // 1) Highest priority: Check blacklist first
        // If IP is blacklisted (including '*' which blocks all IPs), 
        // block access regardless of whitelist
        if (self::matches($clientIp, $blacklist)) {
            return self::deny($config);
        }

        // 2) Check whitelist only if not blacklisted
        // If whitelist is configured and IP matches, allow access
        if (!empty($whitelist) && self::matches($clientIp, $whitelist)) {
            return $next($request);
        }

        // 3) If whitelist is configured but IP doesn't match, block access
        if (!empty($whitelist)) {
            return self::deny($config);
        }

        // 4) If whitelist is null/empty and not blacklisted, allow access
        return $next($request);
    }

    private static function getClientIp(Request $request, ?string $header): string
    {
        if ($header) {
            $raw = $request->headers->get($header);
            if ($raw) {
                // X-Forwarded-For may contain a chain: client, proxy1, proxy2...
                // Take the first, trimmed.
                $first = trim(explode(',', $raw)[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }

        return $request->ip();
    }

    private static function normalizeList($list): array
    {
        if ($list === null) return [];
        if (is_string($list)) return [trim($list)];
        if (is_array($list)) return array_values(array_filter(array_map('trim', $list)));
        return [];
    }

    private static function matches(string $ip, array $list): bool
    {
        if (empty($list)) return false;

        // '*' in list means "match all"
        if (in_array('*', $list, true)) return true;

        foreach ($list as $rule) {
            if (self::matchExact($ip, $rule)) return true;
        }
        return false;
    }

    private static function matchExact(string $ip, string $rule): bool
    {
        return filter_var($rule, FILTER_VALIDATE_IP) && $ip === $rule;
    }


    private static function deny(array $config): Response|JsonResponse
    {
        $status  = (int) data_get($config, 'error.status', 403);
        $message = (string) data_get($config, 'error.message', 'Access denied.');
        $asJson  = (bool) data_get($config, 'error.json', true);

        if ($asJson) {
            return response()->json(['message' => $message], $status);
        }

        return response($message, $status);
    }
}
