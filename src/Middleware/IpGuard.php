<?php

namespace Ahs\LaravelIpGuard\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class IpGuard
{
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $config     = config('ip-guard');
        $whitelist  = self::normalizeList($config['whitelist'] ?? null);
        $blacklist  = self::normalizeList($config['blacklist'] ?? null);
        $ipHeader   = $config['ip_header'] ?? null;

        $clientIp = self::getClientIp($request, $ipHeader);

        // 1) If whitelisted, always allow
        if (self::matches($clientIp, $whitelist)) {
            return $next($request);
        }

        // 2) If blacklisted (including '*'), block
        if (self::matches($clientIp, $blacklist)) {
            return self::deny($config);
        }

        // 3) Otherwise allow
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
            if (self::matchExact($ip, $rule))      return true;
            if (self::matchWildcard($ip, $rule))   return true;
            if (self::matchCidr($ip, $rule))       return true;
        }
        return false;
    }

    private static function matchExact(string $ip, string $rule): bool
    {
        return filter_var($rule, FILTER_VALIDATE_IP) && $ip === $rule;
    }

    private static function matchWildcard(string $ip, string $rule): bool
    {
        // crude wildcard support like 192.168.*.* using Str::is
        if (str_contains($rule, '*')) {
            // Str::is treats pattern as simple wildcard
            return Str::is($rule, $ip);
        }
        return false;
    }

    private static function matchCidr(string $ip, string $rule): bool
    {
        if (!str_contains($rule, '/')) return false;

        [$subnet, $mask] = explode('/', $rule, 2);
        if (!filter_var($subnet, FILTER_VALIDATE_IP)) return false;
        $mask = (int) $mask;

        // Only IPv4 CIDR here; extend to IPv6 if needed
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
            filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
            $mask >= 0 && $mask <= 32) {

            $ipLong     = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong   = -1 << (32 - $mask);

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        return false;
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
