<?php

use Ahs\LaravelIpGuard\Facades\LaravelIpGuard;
use Ahs\LaravelIpGuard\Models\IpGuard;
use Illuminate\Http\Request;
use Ahs\LaravelIpGuard\Middleware\IpGuard as IpGuardMiddleware;

describe('IP Guard Integration Tests', function () {
    beforeEach(function () {
        // Drop and recreate the table for each test
        \Illuminate\Support\Facades\Schema::dropIfExists('ip_guards');
        \Illuminate\Support\Facades\Schema::create('ip_guards', function ($table) {
            $table->id();
            $table->string('ip_address');
            $table->enum('type', ['whitelist', 'blacklist']);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['ip_address', 'type']);
            $table->index(['ip_address', 'type']);
            $table->index(['type', 'is_active']);
        });
        
        $this->middleware = new IpGuardMiddleware();
    });

    test('complete workflow: add whitelist ip and allow access', function () {
        // Add IP to whitelist via facade

        LaravelIpGuard::addToWhitelist('192.168.1.100', 'Office IP');

        // Verify IP is in database
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '192.168.1.100')
            ->where('type', 'whitelist')
            ->where('is_active', true)
            ->exists())->toBeTrue();

        // Test middleware allows access
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('complete workflow: add blacklist ip and block access', function () {
        // Add IP to blacklist via facade
        LaravelIpGuard::addToBlacklist('10.0.0.100', 'Blocked IP');

        // Verify IP is in database
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '10.0.0.100')
            ->where('type', 'blacklist')
            ->where('is_active', true)
            ->exists())->toBeTrue();

        // Test middleware blocks access
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '10.0.0.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
    });

    test('complete workflow: bulk add ips and verify access control', function () {
        // Bulk add IPs to whitelist
        $whitelistIps = ['192.168.1.100', '192.168.1.101', '192.168.1.102'];
        $addedCount = LaravelIpGuard::bulkAddToWhitelist($whitelistIps, 'Office Network');

        expect($addedCount)->toBe(3);

        // Bulk add IPs to blacklist
        $blacklistIps = ['10.0.0.100', '10.0.0.101'];
        $addedCount = LaravelIpGuard::bulkAddToBlacklist($blacklistIps, 'Blocked Network');

        expect($addedCount)->toBe(2);

        // Test whitelist access
        foreach ($whitelistIps as $ip) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', $ip);

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            expect($response->getContent())->toBe('OK');
        }

        // Test blacklist blocking
        foreach ($blacklistIps as $ip) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', $ip);

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            expect($response->getStatusCode())->toBe(403);
        }
    });

    test('complete workflow: toggle ip status and verify behavior', function () {
        // Add IP to whitelist
        $ipGuard = LaravelIpGuard::addToWhitelist('192.168.1.100', 'Office IP');

        // Verify access is allowed
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');

        // Toggle IP to inactive
        $toggleResult = LaravelIpGuard::toggleIpStatus($ipGuard->id);
        expect($toggleResult)->toBeTrue();
        
        // Verify IP is now inactive
        $ipGuard->refresh();
        expect($ipGuard->is_active)->toBeFalse();

        // Verify access is now allowed (since whitelist is empty, no restrictions)
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('OK');

        // Toggle IP back to active
        LaravelIpGuard::toggleIpStatus($ipGuard->id);

        // Verify access is allowed again
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('complete workflow: priority system with mixed lists', function () {
        // Add same IP to both whitelist and blacklist
        LaravelIpGuard::addToWhitelist('192.168.1.100', 'Office IP');
            LaravelIpGuard::addToBlacklist('192.168.1.100', 'Blocked IP');

        // Verify blacklist takes priority
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
    });

    test('complete workflow: wildcard blacklist blocks all', function () {
        // Add wildcard to blacklist
        LaravelIpGuard::addToBlacklist('*', 'Block all IPs');

        // Add some whitelist IPs
        LaravelIpGuard::addToWhitelist('192.168.1.100', 'Office IP');
        LaravelIpGuard::addToWhitelist('192.168.1.101', 'Admin IP');

        // Test that all IPs are blocked despite whitelist
        $testIps = ['192.168.1.100', '192.168.1.101', '10.0.0.100', '203.0.113.10'];

        foreach ($testIps as $ip) {
            $request = Request::create('/test', 'GET');
            $request->server->set('REMOTE_ADDR', $ip);

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            expect($response->getStatusCode())->toBe(403);
        }
    });

    test('complete workflow: statistics and management', function () {
        // Add various IPs
        LaravelIpGuard::addToWhitelist('192.168.1.100', 'Office IP');
        LaravelIpGuard::addToWhitelist('192.168.1.101', 'Admin IP');
        LaravelIpGuard::addToBlacklist('10.0.0.100', 'Blocked IP');
        LaravelIpGuard::addToBlacklist('10.0.0.101', 'Blocked IP');

        // Get statistics
        $stats = LaravelIpGuard::getStats();

        expect($stats)->toBe([
            'whitelist_count' => 2,
            'blacklist_count' => 2,
            'total_count' => 4,
        ]);

        // Get all IPs
        $allIps = LaravelIpGuard::getAllIps();

        expect($allIps)->toHaveKey('whitelist');
        expect($allIps)->toHaveKey('blacklist');
        expect($allIps['whitelist'])->toHaveCount(2);
        expect($allIps['blacklist'])->toHaveCount(2);

        // Clear whitelist
        $removedCount = LaravelIpGuard::clearType('whitelist');

        expect($removedCount)->toBe(2);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->count())->toBe(0);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->count())->toBe(2);
    });

    test('complete workflow: error handling and fallback', function () {
        // Test with invalid IP format
        $result = LaravelIpGuard::bulkAddToWhitelist(['192.168.1.100', 'invalid-ip', '192.168.1.101'], 'Test');

        expect($result)->toBe(2); // Only valid IPs added

        // Test remove non-existent IP
        $result = LaravelIpGuard::removeFromWhitelist('999.999.999.999');

        expect($result)->toBeFalse();

        // Test toggle non-existent IP
        $result = LaravelIpGuard::toggleIpStatus(999);

        expect($result)->toBeFalse();
    });

    test('complete workflow: custom headers and configuration', function () {
        // Configure custom IP header
        config(['ip-guard.ip_header' => 'X-Real-IP']);

        // Add IP to whitelist
        LaravelIpGuard::addToWhitelist('203.0.113.10', 'Proxy IP');

        // Test with custom header
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('X-Real-IP', '203.0.113.10');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });
});
