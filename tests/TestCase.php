<?php

namespace Ahs\LaravelIpGuard\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Ahs\LaravelIpGuard\LaravelIpGuardServiceProvider;

class TestCase extends Orchestra
{

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Ahs\\LaravelIpGuard\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Run migrations
        $this->artisan('migrate', ['--database' => 'mysql', '--path' => __DIR__ . '/../database/migrations']);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelIpGuardServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Set up database configuration
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel_ip_guard_test'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'Abcd1234'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Set up IP Guard configuration
        $app['config']->set('ip-guard', [
            'enabled' => true,
            'whitelist' => null,
            'blacklist' => null,
            'ip_header' => null,
            'error' => [
                'status' => 403,
                'message' => 'Access denied from your IP address.',
                'json' => true,
            ],
        ]);

    }
}
