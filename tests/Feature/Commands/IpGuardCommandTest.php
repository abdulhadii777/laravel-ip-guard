<?php

use Ahs\LaravelIpGuard\Models\IpGuard;

describe('IpGuard Command', function () {

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

    test('add command adds ip to whitelist', function () {
        $this->artisan('ip-guard:manage', [
            'action' => 'add',
            'type' => 'whitelist',
            'ip' => '192.168.1.100',
            '--description' => 'Test IP'
        ])->assertExitCode(0);

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '192.168.1.100')
            ->where('type', 'whitelist')
            ->exists())->toBeTrue();
    });

    test('add command adds ip to blacklist', function () {
        $this->artisan('ip-guard:manage', [
            'action' => 'add',
            'type' => 'blacklist',
            'ip' => '10.0.0.100',
            '--description' => 'Blocked IP'
        ])->assertExitCode(0);

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '10.0.0.100')
            ->where('type', 'blacklist')
            ->exists())->toBeTrue();
    });

    test('add command validates ip format', function () {
        $this->artisan('ip-guard:manage', [
            'action' => 'add',
            'type' => 'whitelist',
            'ip' => 'invalid-ip'
        ])->assertExitCode(1);
    });

    test('add command validates type', function () {
        $this->artisan('ip-guard:manage', [
            'action' => 'add',
            'type' => 'invalid-type',
            'ip' => '192.168.1.100'
        ])->assertExitCode(1);
    });

    test('remove command removes ip from whitelist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->withIp('192.168.1.100')->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'remove',
            'type' => 'whitelist',
            'ip' => '192.168.1.100'
        ])->assertExitCode(0);

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '192.168.1.100')
            ->where('type', 'whitelist')
            ->exists())->toBeFalse();
    });

    test('remove command removes ip from blacklist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->withIp('10.0.0.100')->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'remove',
            'type' => 'blacklist',
            'ip' => '10.0.0.100'
        ])->assertExitCode(0);

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::where('ip_address', '10.0.0.100')
            ->where('type', 'blacklist')
            ->exists())->toBeFalse();
    });

    test('list command shows all ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(3)->create();

        $this->artisan('ip-guard:manage', ['action' => 'list'])
            ->assertExitCode(0)
            ->expectsOutput('All IPs:');
    });

    test('list command shows whitelist ips only', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(3)->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'list',
            'type' => 'whitelist'
        ])->assertExitCode(0)
            ->expectsOutput('whitelist IPs:');
    });

    test('list command shows blacklist ips only', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(3)->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'list',
            'type' => 'blacklist'
        ])->assertExitCode(0)
            ->expectsOutput('blacklist IPs:');
    });

    test('list command shows no ips message when empty', function () {
        $this->artisan('ip-guard:manage', ['action' => 'list'])
            ->assertExitCode(0)
            ->expectsOutput('No IPs found');
    });

    test('clear command clears whitelist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(2)->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'clear',
            'type' => 'whitelist'
        ])->assertExitCode(0)
            ->expectsOutput('Cleared 3 IPs from whitelist');

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->count())->toBe(0);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->count())->toBe(2);
    });

    test('clear command clears blacklist', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->count(3)->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'clear',
            'type' => 'blacklist'
        ])->assertExitCode(0)
            ->expectsOutput('Cleared 3 IPs from blacklist');

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::whitelist()->count())->toBe(2);
        expect(\Ahs\LaravelIpGuard\Models\IpGuard::blacklist()->count())->toBe(0);
    });

    test('clear command clears all ips', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->count(5)->create();

        $this->artisan('ip-guard:manage', ['action' => 'clear'])
            ->assertExitCode(0)
            ->expectsOutput('Cleared 5 IPs from all lists');

        expect(\Ahs\LaravelIpGuard\Models\IpGuard::count())->toBe(0);
    });

    test('stats command shows statistics', function () {
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->whitelist()->active()->count(3)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->blacklist()->active()->count(2)->create();
        \Ahs\LaravelIpGuard\Models\IpGuard::factory()->inactive()->count(1)->create();

        $this->artisan('ip-guard:manage', ['action' => 'stats'])
            ->assertExitCode(0)
            ->expectsOutput('IP Guard Statistics:')
            ->expectsOutput('  Whitelist Count: 3')
            ->expectsOutput('  Blacklist Count: 2')
            ->expectsOutput('  Total Active: 5')
            ->expectsOutput('  Total Inactive: 1');
    });

    test('toggle command toggles ip status', function () {
        $ipGuard = \Ahs\LaravelIpGuard\Models\IpGuard::factory()->active()->create();

        $this->artisan('ip-guard:manage', [
            'action' => 'toggle',
            '--id' => $ipGuard->id
        ])->assertExitCode(0)
            ->expectsOutput("IP {$ipGuard->ip_address} ({$ipGuard->type}) deactivated");

        expect($ipGuard->fresh()->is_active)->toBeFalse();
    });

    test('toggle command fails with invalid id', function () {
        $this->artisan('ip-guard:manage', [
            'action' => 'toggle',
            '--id' => 999
        ])->assertExitCode(1)
            ->expectsOutput('IP not found');
    });

    test('command fails with invalid action', function () {
        $this->artisan('ip-guard:manage', ['action' => 'invalid'])
            ->assertExitCode(1)
            ->expectsOutput('Invalid action. Use: add, remove, list, clear, stats, toggle');
    });

    test('add command fails without required arguments', function () {
        $this->artisan('ip-guard:manage', ['action' => 'add'])
            ->assertExitCode(1)
            ->expectsOutput('Type and IP are required for add action');
    });

    test('remove command fails without required arguments', function () {
        $this->artisan('ip-guard:manage', ['action' => 'remove'])
            ->assertExitCode(1)
            ->expectsOutput('Type and IP are required for remove action');
    });

    test('toggle command fails without id', function () {
        $this->artisan('ip-guard:manage', ['action' => 'toggle'])
            ->assertExitCode(1)
            ->expectsOutput('ID is required for toggle action');
    });
});
