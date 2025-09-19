<?php

use Ahs\LaravelIpGuard\Models\IpGuard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ahs\LaravelIpGuard\Middleware\IpGuard as IpGuardMiddleware;

describe('IpGuard Middleware', function () {
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

    test('allows access when ip guard is disabled', function () {
        config(['ip-guard.enabled' => false]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('allows access when no restrictions are configured', function () {
        config(['ip-guard.enabled' => true]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('blocks access when ip is blacklisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
        expect($response->getContent())->toContain('Access denied');
    });

    test('allows access when ip is whitelisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('blocks access when whitelist is configured but ip is not whitelisted', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.101')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
        expect($response->getContent())->toContain('Access denied');
    });

    test('blacklist takes priority over whitelist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
        expect($response->getContent())->toContain('Access denied');
    });

    test('blocks all ips when wildcard is in blacklist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('*')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
        expect($response->getContent())->toContain('Access denied');
    });

    test('ignores inactive ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->inactive()->withIp('192.168.1.100')->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->inactive()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('uses custom ip header when configured', function () {
        config(['ip-guard.ip_header' => 'X-Forwarded-For']);
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('203.0.113.10')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('X-Forwarded-For', '203.0.113.10');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('handles multiple ips in forwarded header', function () {
        config(['ip-guard.ip_header' => 'X-Forwarded-For']);
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->withIp('203.0.113.10')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('X-Forwarded-For', '203.0.113.10, 10.0.0.1, 172.16.0.1');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('falls back to config when database is unavailable', function () {
        // Mock database connection failure
        $this->mock('db', function ($mock) {
            $mock->shouldReceive('connection')->andThrow(new Exception('Database connection failed'));
        });

        config([
            'ip-guard.enabled' => true,
            'ip-guard.whitelist' => ['192.168.1.100'],
        ]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toBe('OK');
    });

    test('returns json response when configured', function () {
        config(['ip-guard.error.json' => true]);
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
        expect($response->headers->get('Content-Type'))->toContain('application/json');
        expect(json_decode($response->getContent(), true))->toHaveKey('message');
    });

    test('returns plain text response when configured', function () {
        config(['ip-guard.error.json' => false]);
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(403);
        expect($response->headers->get('Content-Type'))->toBe('text/plain');
        expect($response->getContent())->toBe('Access denied from your IP address.');
    });

    test('uses custom error message when configured', function () {
        config(['ip-guard.error.message' => 'Custom access denied message']);
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getContent())->toContain('Custom access denied message');
    });

    test('uses custom error status when configured', function () {
        config(['ip-guard.error.status' => 401]);
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->withIp('192.168.1.100')->create();

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        expect($response->getStatusCode())->toBe(401);
    });
});
