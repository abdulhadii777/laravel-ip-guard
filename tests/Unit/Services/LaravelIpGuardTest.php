<?php

use Ahs\LaravelIpGuard\Facades\LaravelIpGuard;
use Ahs\LaravelIpGuard\Models\IpGuard;

describe('LaravelIpGuard Service', function () {

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
    });

    test('addToWhitelist adds ip to whitelist', function () {
        $result = LaravelIpGuard::addToWhitelist('192.168.1.100', 'Test IP');

        expect($result)->toBeInstanceOf(\Ahs\LaravelIpGuard\Models\IpGuard::class);
        expect($result->ip_address)->toBe('192.168.1.100');
        expect($result->type)->toBe('whitelist');
        expect($result->description)->toBe('Test IP');
        expect($result->is_active)->toBeTrue();
    });

    test('addToBlacklist adds ip to blacklist', function () {
        $result = LaravelIpGuard::addToBlacklist('10.0.0.100', 'Blocked IP');

        expect($result)->toBeInstanceOf(\Ahs\LaravelIpGuard\Models\IpGuard::class);
        expect($result->ip_address)->toBe('10.0.0.100');
        expect($result->type)->toBe('blacklist');
        expect($result->description)->toBe('Blocked IP');
        expect($result->is_active)->toBeTrue();
    });

    test('removeFromWhitelist removes ip from whitelist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->withIp('192.168.1.100')->create();

        $result = LaravelIpGuard::removeFromWhitelist('192.168.1.100');

        expect($result)->toBeTrue();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '192.168.1.100')->where('type', 'whitelist')->exists())->toBeFalse();
    });

    test('removeFromBlacklist removes ip from blacklist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->withIp('10.0.0.100')->create();

        $result = LaravelIpGuard::removeFromBlacklist('10.0.0.100');

        expect($result)->toBeTrue();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '10.0.0.100')->where('type', 'blacklist')->exists())->toBeFalse();
    });

    test('getWhitelistIps returns active whitelist ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->inactive()->withIp('192.168.1.101')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.102')->create();

        $whitelistIps = LaravelIpGuard::getWhitelistIps();

        expect($whitelistIps)->toHaveCount(1);
        expect($whitelistIps)->toContain('192.168.1.100');
    });

    test('getBlacklistIps returns active blacklist ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('10.0.0.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->inactive()->withIp('10.0.0.101')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('10.0.0.102')->create();

        $blacklistIps = LaravelIpGuard::getBlacklistIps();

        expect($blacklistIps)->toHaveCount(1);
        expect($blacklistIps)->toContain('10.0.0.100');
    });

    test('isWhitelisted checks if ip is whitelisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();

        expect(LaravelIpGuard::isWhitelisted('192.168.1.100'))->toBeTrue();
        expect(LaravelIpGuard::isWhitelisted('192.168.1.101'))->toBeFalse();
    });

    test('isBlacklisted checks if ip is blacklisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('10.0.0.100')->create();

        expect(LaravelIpGuard::isBlacklisted('10.0.0.100'))->toBeTrue();
        expect(LaravelIpGuard::isBlacklisted('10.0.0.101'))->toBeFalse();
    });

    test('getIpsByType returns ips of specific type', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->count(3)->create();

        $whitelistIps = LaravelIpGuard::getIpsByType('whitelist');
        $blacklistIps = LaravelIpGuard::getIpsByType('blacklist');

        expect($whitelistIps)->toHaveCount(2);
        expect($blacklistIps)->toHaveCount(3);
    });

    test('getAllIps returns all ips grouped by type', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->count(3)->create();

        $allIps = LaravelIpGuard::getAllIps();

        expect($allIps)->toHaveKey('whitelist');
        expect($allIps)->toHaveKey('blacklist');
        expect($allIps['whitelist'])->toHaveCount(2);
        expect($allIps['blacklist'])->toHaveCount(3);
    });

    test('toggleIpStatus toggles ip active status', function () {
        $ipGuard = \Ahs\LaravelIpGuard\Models\IpGuard::factory()->active()->create();

        $result = LaravelIpGuard::toggleIpStatus($ipGuard->id);

        expect($result)->toBeTrue();
        expect($ipGuard->fresh()->is_active)->toBeFalse();
    });

    test('clearType removes all ips of specific type', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(2)->create();

        $removedCount = LaravelIpGuard::clearType('whitelist');

        expect($removedCount)->toBe(3);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->count())->toBe(0);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->count())->toBe(2);
    });

    test('clearAll removes all ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->count(5)->create();

        $removedCount = LaravelIpGuard::clearAll();

        expect($removedCount)->toBe(5);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::count())->toBe(0);
    });

    test('bulkAddToWhitelist adds multiple ips to whitelist', function () {
        $ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102'];

        $addedCount = LaravelIpGuard::bulkAddToWhitelist($ips, 'Office Network');

        expect($addedCount)->toBe(3);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->whereIn('ip_address', $ips)->count())->toBe(3);
    });

    test('bulkAddToBlacklist adds multiple ips to blacklist', function () {
        $ips = ['10.0.0.100', '10.0.0.101', '10.0.0.102'];

        $addedCount = LaravelIpGuard::bulkAddToBlacklist($ips, 'Blocked Network');

        expect($addedCount)->toBe(3);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->whereIn('ip_address', $ips)->count())->toBe(3);
    });

    test('bulkAddToWhitelist filters invalid ips', function () {
        $ips = ['192.168.1.100', 'invalid-ip', '192.168.1.101'];

        $addedCount = LaravelIpGuard::bulkAddToWhitelist($ips, 'Office Network');

        expect($addedCount)->toBe(2);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->whereIn('ip_address', ['192.168.1.100', '192.168.1.101'])->count())->toBe(2);
    });

    test('getStats returns correct statistics', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->inactive()->count(1)->create();

        $stats = LaravelIpGuard::getStats();

        expect($stats)->toBe([
            'whitelist_count' => 3,
            'blacklist_count' => 2,
            'total_count' => 5,
        ]);
    });
});
