<?php

use Ahs\LaravelIpGuard\Models\IpGuard;

describe('IpGuard Model', function () {

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

    test('can create ip guard record', function () {
        $ipGuard = \Ahs\LaravelIpGuard\Models\IpGuard::create([
            'ip_address' => '192.168.1.100',
            'type' => 'whitelist',
            'description' => 'Test IP',
            'is_active' => true,
        ]);

        expect($ipGuard)->toBeInstanceOf(\Ahs\LaravelIpGuard\Models\IpGuard::class);
        expect($ipGuard->ip_address)->toBe('192.168.1.100');
        expect($ipGuard->type)->toBe('whitelist');
        expect($ipGuard->description)->toBe('Test IP');
        expect($ipGuard->is_active)->toBeTrue();
    });

    test('whitelist scope works correctly', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(2)->create();

        $whitelistIps = \Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->get();

        expect($whitelistIps)->toHaveCount(3);
        expect($whitelistIps->every(fn($ip) => $ip->type === 'whitelist'))->toBeTrue();
    });

    test('blacklist scope works correctly', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(4)->create();

        $blacklistIps = \Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->get();

        expect($blacklistIps)->toHaveCount(4);
        expect($blacklistIps->every(fn($ip) => $ip->type === 'blacklist'))->toBeTrue();
    });

    test('active scope works correctly', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->active()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->inactive()->count(2)->create();

        $activeIps = \Ahs\LaravelIpGuard\Models\IpGuard::active()->get();

        expect($activeIps)->toHaveCount(3);
        expect($activeIps->every(fn($ip) => $ip->is_active === true))->toBeTrue();
    });

    test('getWhitelistIps returns only active whitelist ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->inactive()->withIp('192.168.1.101')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.102')->create();

        $whitelistIps = \Ahs\LaravelIpGuard\Models\IpGuard::getWhitelistIps();

        expect($whitelistIps)->toHaveCount(1);
        expect($whitelistIps)->toContain('192.168.1.100');
        expect($whitelistIps)->not->toContain('192.168.1.101');
        expect($whitelistIps)->not->toContain('192.168.1.102');
    });

    test('getBlacklistIps returns only active blacklist ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('10.0.0.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->inactive()->withIp('10.0.0.101')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('10.0.0.102')->create();

        $blacklistIps = \Ahs\LaravelIpGuard\Models\IpGuard::getBlacklistIps();

        expect($blacklistIps)->toHaveCount(1);
        expect($blacklistIps)->toContain('10.0.0.100');
        expect($blacklistIps)->not->toContain('10.0.0.101');
        expect($blacklistIps)->not->toContain('10.0.0.102');
    });

    test('addToWhitelist creates or updates whitelist ip', function () {
        $ipGuard = \Ahs\LaravelIpGuard\Models\IpGuard::addToWhitelist('192.168.1.100', 'Test IP');

        expect($ipGuard)->toBeInstanceOf(\Ahs\LaravelIpGuard\Models\IpGuard::class);
        expect($ipGuard->ip_address)->toBe('192.168.1.100');
        expect($ipGuard->type)->toBe('whitelist');
        expect($ipGuard->description)->toBe('Test IP');
        expect($ipGuard->is_active)->toBeTrue();

        // Test update existing
        $updatedIpGuard = \Ahs\LaravelIpGuard\Models\IpGuard::addToWhitelist('192.168.1.100', 'Updated Test IP');
        expect($updatedIpGuard->id)->toBe($ipGuard->id);
        expect($updatedIpGuard->description)->toBe('Updated Test IP');
    });

    test('addToBlacklist creates or updates blacklist ip', function () {
        $ipGuard = \Ahs\LaravelIpGuard\Models\IpGuard::addToBlacklist('10.0.0.100', 'Blocked IP');

        expect($ipGuard)->toBeInstanceOf(\Ahs\LaravelIpGuard\Models\IpGuard::class);
        expect($ipGuard->ip_address)->toBe('10.0.0.100');
        expect($ipGuard->type)->toBe('blacklist');
        expect($ipGuard->description)->toBe('Blocked IP');
        expect($ipGuard->is_active)->toBeTrue();
    });

    test('removeFromWhitelist removes whitelist ip', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->withIp('192.168.1.100')->create();

        $removed = \Ahs\LaravelIpGuard\Models\IpGuard::removeFromWhitelist('192.168.1.100');

        expect($removed)->toBeTrue();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '192.168.1.100')->where('type', 'whitelist')->exists())->toBeFalse();
    });

    test('removeFromBlacklist removes blacklist ip', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->withIp('10.0.0.100')->create();

        $removed = \Ahs\LaravelIpGuard\Models\IpGuard::removeFromBlacklist('10.0.0.100');

        expect($removed)->toBeTrue();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '10.0.0.100')->where('type', 'blacklist')->exists())->toBeFalse();
    });

    test('isWhitelisted checks if ip is whitelisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->inactive()->withIp('192.168.1.101')->create();

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::isWhitelisted('192.168.1.100'))->toBeTrue();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::isWhitelisted('192.168.1.101'))->toBeFalse();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::isWhitelisted('192.168.1.102'))->toBeFalse();
    });

    test('isBlacklisted checks if ip is blacklisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('10.0.0.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->inactive()->withIp('10.0.0.101')->create();

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::isBlacklisted('10.0.0.100'))->toBeTrue();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::isBlacklisted('10.0.0.101'))->toBeFalse();
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::isBlacklisted('10.0.0.102'))->toBeFalse();
    });

    test('toggleActive toggles ip active status', function () {
        $ipGuard = \Ahs\LaravelIpGuard\Models\IpGuard::factory()->active()->create();

        expect($ipGuard->is_active)->toBeTrue();

        $result = $ipGuard->toggleActive();
        expect($result)->toBeTrue();
        expect($ipGuard->fresh()->is_active)->toBeFalse();

        $result = $ipGuard->toggleActive();
        expect($result)->toBeTrue();
        expect($ipGuard->fresh()->is_active)->toBeTrue();
    });

    test('clearType removes all ips of specific type', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(2)->create();

        $removedCount = \Ahs\LaravelIpGuard\Models\IpGuard::clearType('whitelist');

        expect($removedCount)->toBe(3);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->count())->toBe(0);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->count())->toBe(2);
    });

    test('clearAll removes all ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->count(5)->create();

        $removedCount = \Ahs\LaravelIpGuard\Models\IpGuard::clearAll();

        expect($removedCount)->toBe(5);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::count())->toBe(0);
    });
});
